<script setup>
import { ref, onMounted } from 'vue';
import { menuApi } from '../services/api.js';
import ModalFormulaire from '../components/ModalFormulaire.vue';

// État réactif de la page
const plats = ref([]);
const chargement = ref(false);
const erreur = ref('');
const termeRecherche = ref('');

// Pilotage de la modale : ouverte/fermée, et mode création vs modification
const modalOuverte = ref(false);
const modeEdition = ref(false);
const formulaire = ref({ idplat: '', nomplat: '', pu: '' });

// Charge la liste complète des plats depuis l'API
async function chargerPlats() {
  chargement.value = true;
  erreur.value = '';
  try {
    plats.value = await menuApi.getAll();
  } catch (e) {
    erreur.value = e.message;
  } finally {
    chargement.value = false;
  }
}

// Recherche par nom (LIKE côté backend)
async function rechercher() {
  erreur.value = '';
  try {
    if (termeRecherche.value.trim() === '') {
      await chargerPlats();
    } else {
      plats.value = await menuApi.search(termeRecherche.value);
    }
  } catch (e) {
    erreur.value = e.message;
  }
}

// Ouvre la modale en mode création (formulaire vide)
function ouvrirCreation() {
  modeEdition.value = false;
  formulaire.value = { idplat: '', nomplat: '', pu: '' };
  modalOuverte.value = true;
}

// Ouvre la modale en mode modification (formulaire pré-rempli)
function ouvrirEdition(plat) {
  modeEdition.value = true;
  formulaire.value = { idplat: plat.idplat, nomplat: plat.nomplat, pu: plat.pu };
  modalOuverte.value = true;
}

function fermerModal() {
  modalOuverte.value = false;
}

// Soumission du formulaire : crée ou modifie selon le mode actuel
async function soumettre() {
  erreur.value = '';
  try {
    if (modeEdition.value) {
      await menuApi.update(formulaire.value);
    } else {
      await menuApi.create(formulaire.value);
    }
    fermerModal();
    await chargerPlats();
  } catch (e) {
    // L'erreur s'affiche aussi dans la modale (cf template) pour rester visible
    // pendant que l'utilisateur corrige sa saisie, sans fermer la popup.
    erreur.value = e.message;
  }
}

// Suppression avec confirmation simple
async function supprimerPlat(idplat) {
  if (!confirm(`Supprimer le plat ${idplat} ?`)) return;
  erreur.value = '';
  try {
    await menuApi.delete(idplat);
    await chargerPlats();
  } catch (e) {
    erreur.value = e.message;
  }
}

onMounted(chargerPlats);
</script>

<template>
  <div class="menu-view">
    <div class="entete-page">
      <h1>Gestion du Menu</h1>
      <button @click="ouvrirCreation">+ Ajouter un plat</button>
    </div>

    <div class="barre-recherche">
      <input
        v-model="termeRecherche"
        type="text"
        placeholder="Rechercher un plat..."
        @keyup.enter="rechercher"
      />
      <button @click="rechercher">Rechercher</button>
    </div>

    <!-- Erreur liée au chargement/recherche/suppression (hors modale) -->
    <p v-if="erreur && !modalOuverte" class="erreur">{{ erreur }}</p>

    <p v-if="chargement">Chargement...</p>
    <table v-else class="tableau-plats">
      <thead>
        <tr>
          <th>Code</th>
          <th>Nom</th>
          <th>Prix (Ar)</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="plat in plats" :key="plat.idplat">
          <td>{{ plat.idplat }}</td>
          <td>{{ plat.nomplat }}</td>
          <td>{{ Number(plat.pu).toLocaleString('fr-FR') }}</td>
          <td>
            <button @click="ouvrirEdition(plat)">Modifier</button>
            <button @click="supprimerPlat(plat.idplat)">Supprimer</button>
          </td>
        </tr>
        <tr v-if="plats.length === 0">
          <td colspan="4">Aucun plat trouvé.</td>
        </tr>
      </tbody>
    </table>

    <!-- Modale de création / modification -->
    <ModalFormulaire
      :ouvert="modalOuverte"
      :titre="modeEdition ? 'Modifier le plat' : 'Ajouter un plat'"
      @fermer="fermerModal"
    >
      <p v-if="erreur" class="erreur">{{ erreur }}</p>
      <form class="formulaire" @submit.prevent="soumettre">
        <input
          v-model="formulaire.idplat"
          type="text"
          placeholder="Code plat (ex: P009)"
          :disabled="modeEdition"
          required
        />
        <input v-model="formulaire.nomplat" type="text" placeholder="Nom du plat" required />
        <input v-model="formulaire.pu" type="number" placeholder="Prix unitaire (Ar)" min="0" required />
        <div class="actions-formulaire">
          <button type="submit">{{ modeEdition ? 'Enregistrer' : 'Ajouter' }}</button>
          <button type="button" @click="fermerModal">Annuler</button>
        </div>
      </form>
    </ModalFormulaire>
  </div>
</template>

<style scoped>
.menu-view {
  max-width: 800px;
  margin: 0 auto;
  padding: 1.5rem;
}

.entete-page {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
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

.actions-formulaire {
  display: flex;
  gap: 0.5rem;
}

.tableau-plats {
  width: 100%;
  border-collapse: collapse;
}

.tableau-plats th,
.tableau-plats td {
  border: 1px solid #ddd;
  padding: 0.5rem;
  text-align: left;
}

.tableau-plats button {
  margin-right: 0.4rem;
}

.erreur {
  color: #c0392b;
  background: #fdecea;
  padding: 0.6rem 1rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}
</style>