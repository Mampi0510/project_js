<script setup>
import { ref, onMounted } from 'vue';
import { tableApi } from '../services/api.js';
import ModalFormulaire from '../components/ModalFormulaire.vue';

const tables = ref([]);
const chargement = ref(false);
const erreur = ref('');
const filtreOccupation = ref(''); // '' = toutes, '0' = libres, '1' = occupées

const modalOuverte = ref(false);
const modeEdition = ref(false);
const formulaire = ref({ idtable: '', designation: '', occupation: 0 });

async function chargerTables() {
  chargement.value = true;
  erreur.value = '';
  try {
    if (filtreOccupation.value === '') {
      tables.value = await tableApi.getAll();
    } else {
      tables.value = await tableApi.getByOccupation(filtreOccupation.value);
    }
  } catch (e) {
    erreur.value = e.message;
  } finally {
    chargement.value = false;
  }
}

function ouvrirCreation() {
  modeEdition.value = false;
  formulaire.value = { idtable: '', designation: '', occupation: 0 };
  modalOuverte.value = true;
}

function ouvrirEdition(table) {
  modeEdition.value = true;
  formulaire.value = { idtable: table.idtable, designation: table.designation, occupation: table.occupation };
  modalOuverte.value = true;
}

function fermerModal() {
  modalOuverte.value = false;
}

async function soumettre() {
  erreur.value = '';
  try {
    if (modeEdition.value) {
      await tableApi.update(formulaire.value);
    } else {
      await tableApi.create(formulaire.value);
    }
    fermerModal();
    await chargerTables();
  } catch (e) {
    erreur.value = e.message;
  }
}

// Bascule rapide occupée <-> libre directement depuis le tableau, sans passer par la modale
async function basculerOccupation(table) {
  erreur.value = '';
  try {
    const nouvelEtat = table.occupation === 1 ? 0 : 1;
    await tableApi.changerOccupation(table.idtable, nouvelEtat);
    await chargerTables();
  } catch (e) {
    erreur.value = e.message;
  }
}

async function supprimerTable(idtable) {
  if (!confirm(`Supprimer la table ${idtable} ?`)) return;
  erreur.value = '';
  try {
    await tableApi.delete(idtable);
    await chargerTables();
  } catch (e) {
    erreur.value = e.message;
  }
}

onMounted(chargerTables);
</script>

<template>
  <div class="table-view">
    <div class="entete-page">
      <h1>Gestion des Tables</h1>
      <button @click="ouvrirCreation">+ Ajouter une table</button>
    </div>

    <div class="filtre">
      <label>
        Filtrer :
        <select v-model="filtreOccupation" @change="chargerTables">
          <option value="">Toutes</option>
          <option value="0">Libres</option>
          <option value="1">Occupées</option>
        </select>
      </label>
    </div>

    <p v-if="erreur && !modalOuverte" class="erreur">{{ erreur }}</p>

    <p v-if="chargement">Chargement...</p>
    <table v-else class="tableau-tables">
      <thead>
        <tr>
          <th>Code</th>
          <th>Désignation</th>
          <th>État</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="table in tables" :key="table.idtable">
          <td>{{ table.idtable }}</td>
          <td>{{ table.designation }}</td>
          <td>
            <span :class="table.occupation === 1 ? 'badge-occupee' : 'badge-libre'">
              {{ table.occupation === 1 ? 'Occupée' : 'Libre' }}
            </span>
          </td>
          <td>
            <button @click="basculerOccupation(table)">
              {{ table.occupation === 1 ? 'Libérer' : 'Occuper' }}
            </button>
            <button @click="ouvrirEdition(table)">Modifier</button>
            <button @click="supprimerTable(table.idtable)">Supprimer</button>
          </td>
        </tr>
        <tr v-if="tables.length === 0">
          <td colspan="4">Aucune table trouvée.</td>
        </tr>
      </tbody>
    </table>

    <ModalFormulaire
      :ouvert="modalOuverte"
      :titre="modeEdition ? 'Modifier la table' : 'Ajouter une table'"
      @fermer="fermerModal"
    >
      <p v-if="erreur" class="erreur">{{ erreur }}</p>
      <form class="formulaire" @submit.prevent="soumettre">
        <input
          v-model="formulaire.idtable"
          type="text"
          placeholder="Code table (ex: 07)"
          :disabled="modeEdition"
          required
        />
        <input v-model="formulaire.designation" type="text" placeholder="Désignation" required />
        <div class="actions-formulaire">
          <button type="submit">{{ modeEdition ? 'Enregistrer' : 'Ajouter' }}</button>
          <button type="button" @click="fermerModal">Annuler</button>
        </div>
      </form>
    </ModalFormulaire>
  </div>
</template>

<style scoped>
.table-view {
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

.filtre {
  margin-bottom: 1rem;
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

.tableau-tables {
  width: 100%;
  border-collapse: collapse;
}

.tableau-tables th,
.tableau-tables td {
  border: 1px solid #ddd;
  padding: 0.5rem;
  text-align: left;
}

.tableau-tables button {
  margin-right: 0.4rem;
}

.badge-libre {
  color: #1e7e34;
  font-weight: 600;
}

.badge-occupee {
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