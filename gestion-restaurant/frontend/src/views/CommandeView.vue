<script setup>
import { ref, onMounted, computed } from 'vue';
import { commandeApi, menuApi, reservationApi } from '../services/api.js';
import { dateAujourdhuiLocale, extraireDate } from '../services/dateUtils.js';
import ModalFormulaire from '../components/ModalFormulaire.vue';

const commandes = ref([]);
const platsDisponibles = ref([]); // pour le <select> de choix de plat
const reservations = ref([]); // toutes les réservations chargées -- on filtre par date via un computed
const chargement = ref(false);
const erreur = ref('');
const termeRecherche = ref('');

// Commande en cours de détail (affichée dans une modale quand on clique "Voir détail")
const commandeDetail = ref(null);
const modalDetailOuverte = ref(false);

// Pilotage de la modale de création (pas de modification d'en-tête de commande
// pour l'instant -- seule la création est proposée, comme dans la version précédente)
const modalOuverte = ref(false);

// État du formulaire de création.
// idreservSelectionnee est utilisé uniquement quand typecom = 'table' :
// nomcli et idtable sont alors déduits automatiquement de la réservation choisie.
const formulaire = ref({
  idcom: '',
  idreservSelectionnee: '',
  nomcli: '',
  typecom: 'table',
  idtable: '',
  datecom: dateAujourdhuiLocale(), // date du jour par défaut, en heure locale fiable
});

// Lignes en cours de construction pour la nouvelle commande
const lignesEnCours = ref([]);
const platSelectionne = ref('');
const quantiteSelectionnee = ref(1);

// Calcule le total de la commande en cours de construction (avant envoi),
// en croisant les lignes choisies avec les prix du menu déjà chargé.
const totalEnCours = computed(() => {
  return lignesEnCours.value.reduce((somme, ligne) => {
    const plat = platsDisponibles.value.find((p) => p.idplat === ligne.idplat);
    return somme + (plat ? plat.pu * ligne.quantite : 0);
  }, 0);
});

// Ne propose que les réservations du JOUR de la commande en cours de création.
// Réactif : si l'utilisateur change formulaire.datecom, la liste se met à jour automatiquement.
const reservationsDuJour = computed(() => {
  return reservations.value.filter((r) => extraireDate(r.date_de_reserv) === formulaire.value.datecom);
});

async function chargerCommandes() {
  chargement.value = true;
  erreur.value = '';
  try {
    commandes.value = await commandeApi.getAll();
  } catch (e) {
    erreur.value = e.message;
  } finally {
    chargement.value = false;
  }
}

async function chargerDonneesAnnexes() {
  // Plats et réservations nécessaires pour construire le formulaire de commande
  platsDisponibles.value = await menuApi.getAll();
  reservations.value = await reservationApi.getAll();
}

// Quand l'utilisateur choisit un client dans la liste des réservations,
// on remplit automatiquement nomcli et idtable à partir de cette réservation.
function selectionnerReservation() {
  const idreserv = formulaire.value.idreservSelectionnee;
  const resa = reservationsDuJour.value.find((r) => r.idreserv === idreserv);
  if (resa) {
    formulaire.value.nomcli = resa.nomcli;
    formulaire.value.idtable = resa.idtable;
  } else {
    formulaire.value.nomcli = '';
    formulaire.value.idtable = '';
  }
}

// Si la date de la commande change, l'ancienne réservation choisie peut ne plus
// correspondre à ce jour-là -- on vide la sélection pour éviter un état incohérent
// (ex: une table "assignée" qui ne figure plus dans la nouvelle liste affichée).
function reinitialiserSelectionReservation() {
  formulaire.value.idreservSelectionnee = '';
  formulaire.value.nomcli = '';
  formulaire.value.idtable = '';
}

// Ouvre la modale de création avec un formulaire vierge
function ouvrirCreation() {
  formulaire.value = {
    idcom: '',
    idreservSelectionnee: '',
    nomcli: '',
    typecom: 'table',
    idtable: '',
    datecom: dateAujourdhuiLocale(),
  };
  lignesEnCours.value = [];
  erreur.value = '';
  modalOuverte.value = true;
}

function fermerModal() {
  modalOuverte.value = false;
}

async function rechercher() {
  erreur.value = '';
  try {
    if (termeRecherche.value.trim() === '') {
      await chargerCommandes();
    } else {
      commandes.value = await commandeApi.searchByClient(termeRecherche.value);
    }
  } catch (e) {
    erreur.value = e.message;
  }
}

// Ajoute une ligne (plat + quantité) à la commande en cours de construction
function ajouterLigne() {
  if (!platSelectionne.value || quantiteSelectionnee.value < 1) return;

  // Si le plat est déjà dans la liste, on augmente juste la quantité plutôt que dupliquer
  const ligneExistante = lignesEnCours.value.find((l) => l.idplat === platSelectionne.value);
  if (ligneExistante) {
    ligneExistante.quantite += Number(quantiteSelectionnee.value);
  } else {
    lignesEnCours.value.push({
      idplat: platSelectionne.value,
      quantite: Number(quantiteSelectionnee.value),
    });
  }

  platSelectionne.value = '';
  quantiteSelectionnee.value = 1;
}

function retirerLigne(idplat) {
  lignesEnCours.value = lignesEnCours.value.filter((l) => l.idplat !== idplat);
}

function nomDuPlat(idplat) {
  const plat = platsDisponibles.value.find((p) => p.idplat === idplat);
  return plat ? plat.nomplat : idplat;
}

// Création complète de la commande (en-tête + lignes en une seule requête,
// gérée en transaction côté backend)
async function creerCommande() {
  erreur.value = '';

  if (lignesEnCours.value.length === 0) {
    erreur.value = 'Ajoutez au moins un plat à la commande.';
    return;
  }

  try {
    await commandeApi.create({
      idcom: formulaire.value.idcom,
      nomcli: formulaire.value.nomcli,
      typecom: formulaire.value.typecom,
      idtable: formulaire.value.typecom === 'table' ? formulaire.value.idtable : null,
      datecom: formulaire.value.datecom,
      lignes: lignesEnCours.value,
    });

    // Réinitialisation complète et fermeture de la modale après succès
    formulaire.value = {
      idcom: '',
      idreservSelectionnee: '',
      nomcli: '',
      typecom: 'table',
      idtable: '',
      datecom: dateAujourdhuiLocale(),
    };
    lignesEnCours.value = [];
    fermerModal();

    await chargerCommandes();
    await chargerDonneesAnnexes(); // les tables libres et réservations ont pu changer
  } catch (e) {
    erreur.value = e.message;
  }
}

// Affiche le détail complet d'une commande (avec ses lignes) dans le panneau latéral
async function voirDetail(idcom) {
  erreur.value = '';
  try {
    commandeDetail.value = await commandeApi.getById(idcom);
    modalDetailOuverte.value = true;
  } catch (e) {
    erreur.value = e.message;
  }
}

async function marquerPayee(idcom) {
  erreur.value = '';
  try {
    await commandeApi.marquerPayee(idcom);
    await chargerCommandes();
    if (commandeDetail.value && commandeDetail.value.idcom === idcom) {
      await voirDetail(idcom); // rafraîchit la modale de détail si elle est ouverte
    }
  } catch (e) {
    erreur.value = e.message;
  }
}

async function supprimerCommande(idcom) {
  if (!confirm(`Supprimer la commande ${idcom} ?`)) return;
  erreur.value = '';
  try {
    await commandeApi.delete(idcom);
    await chargerCommandes();
    if (commandeDetail.value && commandeDetail.value.idcom === idcom) {
      commandeDetail.value = null;
      modalDetailOuverte.value = false;
    }
  } catch (e) {
    erreur.value = e.message;
  }
}

function ouvrirAddition(idcom) {
  // Ouvre le PDF dans un nouvel onglet -- pas un fetch JSON, juste une navigation directe
  window.open(commandeApi.urlAdditionPdf(idcom), '_blank');
}

onMounted(async () => {
  await chargerDonneesAnnexes();
  await chargerCommandes();
});
</script>

<template>
  <div class="commande-view">
    <div class="entete-page">
      <h1>Gestion des Commandes</h1>
      <button @click="ouvrirCreation">+ Nouvelle commande</button>
    </div>

    <div class="barre-recherche">
      <input
        v-model="termeRecherche"
        type="text"
        placeholder="Rechercher par client..."
        @keyup.enter="rechercher"
      />
      <button @click="rechercher">Rechercher</button>
    </div>

    <p v-if="erreur && !modalOuverte" class="erreur">{{ erreur }}</p>

    <!-- Modale de création d'une commande complète -->
    <ModalFormulaire :ouvert="modalOuverte" titre="Nouvelle commande" @fermer="fermerModal">
      <p v-if="erreur" class="erreur">{{ erreur }}</p>
      <form class="formulaire" @submit.prevent="creerCommande">
        <div class="ligne-formulaire">
          <input v-model="formulaire.idcom" type="text" placeholder="Code commande (ex: A0057)" required />
          <input v-model="formulaire.datecom" type="date" required @change="reinitialiserSelectionReservation" />
        </div>

        <div class="ligne-formulaire">
          <label>
            <input type="radio" v-model="formulaire.typecom" value="table" /> Sur table
          </label>
          <label>
            <input type="radio" v-model="formulaire.typecom" value="emporter" /> À emporter
          </label>
        </div>

        <!-- Sur table : le client doit avoir une réservation existante CE JOUR-LÀ.
             On choisit directement le client réservé, la table se déduit automatiquement. -->
        <div v-if="formulaire.typecom === 'table'" class="ligne-formulaire">
          <select v-model="formulaire.idreservSelectionnee" @change="selectionnerReservation" required>
            <option value="" disabled>Choisir un client ayant réservé ce jour</option>
            <option v-for="r in reservationsDuJour" :key="r.idreserv" :value="r.idreserv">
              {{ r.nomcli }} (table {{ r.idtable }})
            </option>
          </select>
          <span v-if="formulaire.idtable" class="table-deduite">
            Table assignée : {{ formulaire.idtable }}
          </span>
          <p v-if="reservationsDuJour.length === 0" class="note-info">
            Aucune réservation enregistrée pour le {{ formulaire.datecom }}.
          </p>
        </div>

        <!-- À emporter : pas de réservation nécessaire, nom du client saisi librement -->
        <div v-else class="ligne-formulaire">
          <input v-model="formulaire.nomcli" type="text" placeholder="Nom du client" required />
        </div>

        <!-- Construction des lignes de plats -->
        <div class="bloc-lignes">
          <h3>Plats commandés</h3>
          <div class="ligne-formulaire">
            <select v-model="platSelectionne">
              <option value="" disabled>Choisir un plat</option>
              <option v-for="p in platsDisponibles" :key="p.idplat" :value="p.idplat">
                {{ p.nomplat }} ({{ Number(p.pu).toLocaleString('fr-FR') }} Ar)
              </option>
            </select>
            <input v-model.number="quantiteSelectionnee" type="number" min="1" style="width: 70px" />
            <button type="button" @click="ajouterLigne">Ajouter le plat</button>
          </div>

          <ul class="liste-lignes">
            <li v-for="ligne in lignesEnCours" :key="ligne.idplat">
              {{ nomDuPlat(ligne.idplat) }} × {{ ligne.quantite }}
              <button type="button" @click="retirerLigne(ligne.idplat)">Retirer</button>
            </li>
          </ul>

          <p v-if="lignesEnCours.length > 0" class="total-en-cours">
            Total : {{ totalEnCours.toLocaleString('fr-FR') }} Ar
          </p>
        </div>

        <div class="actions-formulaire">
          <button type="submit">Créer la commande</button>
          <button type="button" @click="fermerModal">Annuler</button>
        </div>
      </form>
    </ModalFormulaire>

    <!-- Liste des commandes existantes -->
    <p v-if="chargement">Chargement...</p>
    <table v-else class="tableau-commandes">
      <thead>
        <tr>
          <th>Code</th>
          <th>Client</th>
          <th>Type</th>
          <th>Date</th>
          <th>Payée</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="cmd in commandes" :key="cmd.idcom">
          <td>{{ cmd.idcom }}</td>
          <td>{{ cmd.nomcli }}</td>
          <td>{{ cmd.typecom === 'table' ? 'Table ' + cmd.idtable : 'À emporter' }}</td>
          <td>{{ cmd.datecom }}</td>
          <td>
            <span :class="cmd.paye == 1 ? 'badge-payee' : 'badge-non-payee'">
              {{ cmd.paye == 1 ? 'Payée' : 'Non payée' }}
            </span>
          </td>
          <td>
            <button @click="voirDetail(cmd.idcom)">Détail</button>
            <button v-if="cmd.paye != 1" @click="marquerPayee(cmd.idcom)">Marquer payée</button>
            <button @click="supprimerCommande(cmd.idcom)">Supprimer</button>
          </td>
        </tr>
        <tr v-if="commandes.length === 0">
          <td colspan="6">Aucune commande trouvée.</td>
        </tr>
      </tbody>
    </table>

    <!-- Modale de détail d'une commande -->
    <ModalFormulaire
      v-if="commandeDetail"
      :ouvert="modalDetailOuverte"
      :titre="'Détail de la commande ' + commandeDetail.idcom"
      @fermer="modalDetailOuverte = false"
    >
      <p><strong>Client :</strong> {{ commandeDetail.nomcli }}</p>
      <p>
        <strong>Type :</strong>
        {{ commandeDetail.typecom === 'table' ? 'Table ' + commandeDetail.idtable : 'À emporter' }}
      </p>
      <p><strong>Date :</strong> {{ commandeDetail.datecom }}</p>
      <p>
        <strong>Statut :</strong>
        <span :class="commandeDetail.paye == 1 ? 'badge-payee' : 'badge-non-payee'">
          {{ commandeDetail.paye == 1 ? 'Payée' : 'Non payée' }}
        </span>
      </p>

      <table class="tableau-detail">
        <thead>
          <tr>
            <th>Plat</th>
            <th>PU (Ar)</th>
            <th>Qté</th>
            <th>Sous-total (Ar)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ligne in commandeDetail.lignes" :key="ligne.idligne">
            <td>{{ ligne.nomplat }}</td>
            <td>{{ Number(ligne.pu).toLocaleString('fr-FR') }}</td>
            <td>{{ ligne.quantite }}</td>
            <td>{{ Number(ligne.sousTotal).toLocaleString('fr-FR') }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3"><strong>TOTAL</strong></td>
            <td>
              <strong>
                {{
                  Number(
                    commandeDetail.lignes.reduce((s, l) => s + Number(l.sousTotal), 0)
                  ).toLocaleString('fr-FR')
                }} Ar
              </strong>
            </td>
          </tr>
        </tfoot>
      </table>

      <div class="actions-formulaire" style="margin-top: 1rem;">
        <button
          v-if="commandeDetail.paye != 1"
          @click="marquerPayee(commandeDetail.idcom)"
        >
          Marquer payée
        </button>
        <button @click="ouvrirAddition(commandeDetail.idcom)">Addition PDF</button>
        <button @click="modalDetailOuverte = false">Fermer</button>
      </div>
    </ModalFormulaire>
  </div>
</template>

<style scoped>
.commande-view {
  max-width: 1000px;
  margin: 0 auto;
  padding: 1.5rem;
}

.entete-page {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.entete-page h1 {
  margin: 0;
}

.barre-recherche {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.barre-recherche input {
  flex: 1;
}

.formulaire {
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.actions-formulaire {
  display: flex;
  gap: 0.5rem;
}

.ligne-formulaire {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}

.bloc-lignes {
  border-top: 1px dashed #ccc;
  padding-top: 0.8rem;
}

.liste-lignes {
  list-style: none;
  padding: 0;
  margin: 0.5rem 0;
}

.liste-lignes li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.3rem 0;
  border-bottom: 1px solid #eee;
  max-width: 400px;
}

.total-en-cours {
  font-weight: 600;
}

.table-deduite {
  font-size: 0.9rem;
  color: #1e7e34;
  font-weight: 600;
}

.note-info {
  font-size: 0.85rem;
  color: #888;
  margin: 0;
}

.tableau-commandes,
.tableau-detail {
  width: 100%;
  border-collapse: collapse;
}

.tableau-commandes th,
.tableau-commandes td,
.tableau-detail th,
.tableau-detail td {
  border: 1px solid #ddd;
  padding: 0.5rem;
  text-align: left;
}

.tableau-detail tfoot td {
  background: #f7f7f7;
}

.badge-payee {
  color: #1e7e34;
  font-weight: 600;
}

.badge-non-payee {
  color: #c0392b;
  font-weight: 600;
}

.erreur {
  color: #c0392b;
  background: #fdecea;
  padding: 0.6rem 1rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}
</style>