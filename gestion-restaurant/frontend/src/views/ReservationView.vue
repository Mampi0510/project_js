<script setup>
import { ref, onMounted } from 'vue';
import { reservationApi, tableApi } from '../services/api.js';
import { dateAujourdhuiLocale, combinerDateEtHeure, extraireHeure, formaterDateHeure } from '../services/dateUtils.js';
import ModalFormulaire from '../components/ModalFormulaire.vue';

const reservations = ref([]);
const tablesDisponibles = ref([]); // toutes les tables, pour le <select> (pas seulement les libres :
                                     // une réservation future ne dépend pas de l'occupation actuelle)
const chargement = ref(false);
const erreur = ref('');
const termeRecherche = ref('');

const modalOuverte = ref(false);
const modeEdition = ref(false);
const formulaire = ref({
  idreserv: '',
  idtable: '',
  heureDebut: '',
  heureFin: '',
  nomcli: '',
});

async function chargerReservations() {
  chargement.value = true;
  erreur.value = '';
  try {
    reservations.value = await reservationApi.getAll();
  } catch (e) {
    erreur.value = e.message;
  } finally {
    chargement.value = false;
  }
}

async function chargerTables() {
  tablesDisponibles.value = await tableApi.getAll();
}

async function rechercher() {
  erreur.value = '';
  try {
    if (termeRecherche.value.trim() === '') {
      await chargerReservations();
    } else {
      reservations.value = await reservationApi.searchByClient(termeRecherche.value);
    }
  } catch (e) {
    erreur.value = e.message;
  }
}

function ouvrirCreation() {
  modeEdition.value = false;
  formulaire.value = { idreserv: '', idtable: '', heureDebut: '', heureFin: '', nomcli: '' };
  modalOuverte.value = true;
}

// Pré-remplit le formulaire avec les heures déjà extraites du DATETIME existant
function ouvrirEdition(reservation) {
  modeEdition.value = true;
  formulaire.value = {
    idreserv: reservation.idreserv,
    idtable: reservation.idtable,
    nomcli: reservation.nomcli,
    heureDebut: extraireHeure(reservation.date_de_reserv),
    heureFin: extraireHeure(reservation.date_reserve),
  };
  modalOuverte.value = true;
}

function fermerModal() {
  modalOuverte.value = false;
}

async function soumettre() {
  erreur.value = '';

  if (formulaire.value.heureDebut >= formulaire.value.heureFin) {
    erreur.value = "L'heure de début doit être avant l'heure de fin.";
    return;
  }

  try {
    const aujourdhui = dateAujourdhuiLocale();
    const donnees = {
      idreserv: formulaire.value.idreserv,
      idtable: formulaire.value.idtable,
      nomcli: formulaire.value.nomcli,
      // On reconstruit des DATETIME complets pour le backend, qui ne change pas :
      // il continue de travailler avec des dates/heures classiques.
      date_de_reserv: combinerDateEtHeure(aujourdhui, formulaire.value.heureDebut),
      date_reserve: combinerDateEtHeure(aujourdhui, formulaire.value.heureFin),
    };

    if (modeEdition.value) {
      await reservationApi.update(donnees);
    } else {
      await reservationApi.create(donnees);
    }

    fermerModal();
    await chargerReservations();
  } catch (e) {
    // Le backend renvoie ici un message explicite type
    // "Cette table est déjà réservée sur ce créneau" -- on l'affiche directement dans la modale
    erreur.value = e.message;
  }
}

async function supprimerReservation(idreserv) {
  if (!confirm(`Supprimer la réservation ${idreserv} ?`)) return;
  erreur.value = '';
  try {
    await reservationApi.delete(idreserv);
    await chargerReservations();
  } catch (e) {
    erreur.value = e.message;
  }
}

onMounted(async () => {
  await chargerTables();
  await chargerReservations();
});
</script>

<template>
  <div class="reservation-view">
    <div class="entete-page">
      <h1>Gestion des Réservations</h1>
      <button @click="ouvrirCreation">+ Nouvelle réservation</button>
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

    <p v-if="chargement">Chargement...</p>
    <table v-else class="tableau-reservations">
      <thead>
        <tr>
          <th>Code</th>
          <th>Table</th>
          <th>Client</th>
          <th>Début</th>
          <th>Fin</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="r in reservations" :key="r.idreserv">
          <td>{{ r.idreserv }}</td>
          <td>{{ r.idtable }}</td>
          <td>{{ r.nomcli }}</td>
          <td>{{ formaterDateHeure(r.date_de_reserv) }}</td>
          <td>{{ formaterDateHeure(r.date_reserve) }}</td>
          <td>
            <button @click="ouvrirEdition(r)">Modifier</button>
            <button @click="supprimerReservation(r.idreserv)">Supprimer</button>
          </td>
        </tr>
        <tr v-if="reservations.length === 0">
          <td colspan="6">Aucune réservation trouvée.</td>
        </tr>
      </tbody>
    </table>

    <ModalFormulaire
      :ouvert="modalOuverte"
      :titre="modeEdition ? 'Modifier la réservation' : 'Nouvelle réservation'"
      @fermer="fermerModal"
    >
      <p v-if="erreur" class="erreur">{{ erreur }}</p>
      <form class="formulaire" @submit.prevent="soumettre">
        <input
          v-model="formulaire.idreserv"
          type="text"
          placeholder="Code réservation (ex: R004)"
          :disabled="modeEdition"
          required
        />
        <input v-model="formulaire.nomcli" type="text" placeholder="Nom du client" required />
        <select v-model="formulaire.idtable" required>
          <option value="" disabled>Choisir une table</option>
          <option v-for="t in tablesDisponibles" :key="t.idtable" :value="t.idtable">
            {{ t.idtable }} - {{ t.designation }}
          </option>
        </select>
        <div class="ligne-formulaire">
          <label>
            Heure début :
            <input v-model="formulaire.heureDebut" type="time" required />
          </label>
          <label>
            Heure fin :
            <input v-model="formulaire.heureFin" type="time" required />
          </label>
        </div>
        <div class="actions-formulaire">
          <button type="submit">{{ modeEdition ? 'Enregistrer' : 'Réserver' }}</button>
          <button type="button" @click="fermerModal">Annuler</button>
        </div>
      </form>
    </ModalFormulaire>
  </div>
</template>

<style scoped>
.reservation-view {
  max-width: 900px;
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
  gap: 0.7rem;
}

.ligne-formulaire {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
  align-items: center;
}

.actions-formulaire {
  display: flex;
  gap: 0.5rem;
}

.tableau-reservations {
  width: 100%;
  border-collapse: collapse;
}

.tableau-reservations th,
.tableau-reservations td {
  border: 1px solid #ddd;
  padding: 0.5rem;
  text-align: left;
}

.erreur {
  color: #c0392b;
  background: #fdecea;
  padding: 0.6rem 1rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}
</style>