<script setup>
  import {API_URL} from '@/config.js'
  import ToastMessage from './ToastMessage.vue'
  import { ref,onMounted,computed } from 'vue';
  /*onMounted: exécuter du code au chargement de la page*/

  const recherche = ref(''); 
  const triColonne = ref(''); 
  const triOrdre = ref('asc');
  const enseignants = ref([]); //ref pour rendre la variable réactive
  const message = ref('');
  const messageType = ref('success')

  function trier(colonne) {
    if (triColonne.value === colonne) {
      triOrdre.value = triOrdre.value === 'asc' ? 'desc' : 'asc'
    } else {
      triColonne.value = colonne
      triOrdre.value = 'asc'
    }
  }

  const enseignantsAffiches = computed(() => {
  let resultat = enseignants.value.filter(e =>
    e.nom.toLowerCase().includes(recherche.value.toLowerCase()) ||
    e.matricule.toLowerCase().includes(recherche.value.toLowerCase())
  )

  if (triColonne.value) {
    resultat = [...resultat].sort((a, b) => {
      const valA = a[triColonne.value]
      const valB = b[triColonne.value]
      if (typeof valA === 'string') {
        return triOrdre.value === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA)
      }
      return triOrdre.value === 'asc' ? valA - valB : valB - valA
    })
  }

  return resultat
  })

  function afficherMessage(texte, succes) {
    message.value = texte
    messageType.value = succes ? 'success' : 'error'
    setTimeout(() => { message.value = '' }, 3000)
  }

  async function chargerEnseignants() {
    try {
    const response = await fetch(`${API_URL}/enseignant.php`, { credentials: 'include' }); //fetch pour faire une requête http
    enseignants.value = await response.json(); //mettre à jour la variable réactive avec les données reçues
  } catch (error) {
    afficherMessage('Impossible de contacter le serveur', false)
    }
  }

  async function supprimerEnseignant(id) {
    const confirmation = confirm("Êtes-vous sûr de vouloir supprimer cet enseignant ?");
    if (!confirmation) {
    return;
    }
  try {
    const response = await fetch(`${API_URL}/enseignant.php`, {
      method: 'DELETE',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const data = await response.json();
    afficherMessage(data.message, data.message.includes('réussie'));   
    chargerEnseignants();
  } catch (error) {
    afficherMessage('Impossible de contacter le serveur', false);
  }
}
  const showModal = ref(false);
  const enseignantSelectionne = ref(null);
  const erreurMatriculeModif = ref('')
  const erreurNomModif = ref('')


  function modifier(enseignant) {
    enseignantSelectionne.value = { ...enseignant }; //copier les données de l'enseignant sélectionné
    erreurMatriculeModif.value = ''
    erreurNomModif.value = ''
    showModal.value = true; //afficher le modal de modification
  }

  async function verifierMatriculeModif() {
    if (!enseignantSelectionne.value.matricule) return
    try {
      const response = await fetch(
        `${API_URL}/enseignant.php?check_matricule=${enseignantSelectionne.value.matricule}&exclude_id=${enseignantSelectionne.value.id}`,
        { credentials: 'include' }
      )
      const data = await response.json()
      erreurMatriculeModif.value = data.existe ? 'Ce matricule existe déjà' : ''
    } catch {
      erreurMatriculeModif.value = ''
    }
  }

  async function sauvegarder() {
    erreurMatriculeModif.value = !enseignantSelectionne.value.matricule ? 'Ce champ est obligatoire' : ''
    erreurNomModif.value = !enseignantSelectionne.value.nom ? 'Ce champ est obligatoire' : ''

    if (erreurMatriculeModif.value || erreurNomModif.value) {
    return
    }

    await verifierMatriculeModif()

    if (erreurMatriculeModif.value) {
    return
    }

    const confirmation = confirm("Voulez-vous vraiment modifier cet enseignant ?")
    if (!confirmation) {
    return
  }
    try {
      const response = await fetch(`${API_URL}/enseignant.php`, {
        credentials: 'include',
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(enseignantSelectionne.value) //envoyer les données modifiées de l'enseignant
      });
      if (!response.ok) { afficherMessage('Erreur serveur', false); return; }
      const data = await response.json();
      afficherMessage(data.message, data.message.includes('réussie')); //afficher le message de succès ou d'erreur
      showModal.value = false; //cacher le modal après sauvegarde
      chargerEnseignants(); //recharger la liste après modification
  } catch {
    afficherMessage('Impossible de contacter le serveur', false);
  }
}

  onMounted(chargerEnseignants); //exécuter la fonction après le rendu du composant
</script>

<template>
  <div>
    <h1>Liste des enseignants</h1>
    <input v-model="recherche" placeholder="Rechercher par nom ou matricule..." class="recherche" />
    <table>
      <thead>
        <tr>
          <th @click="trier('matricule')">Matricule {{ triColonne === 'matricule' ? (triOrdre === 'asc' ? '↑' : '↓') : '' }}</th>
          <th @click="trier('nom')">Nom {{ triColonne === 'nom' ? (triOrdre === 'asc' ? '↑' : '↓') : '' }}</th>
          <th @click="trier('prenom')">Prénom {{ triColonne === 'prenom' ? (triOrdre === 'asc' ? '↑' : '↓') : '' }}</th>
          <th @click="trier('nombre_heures')">Nombre d'heures {{ triColonne === 'nombre_heures' ? (triOrdre === 'asc' ? '↑' : '↓') : '' }}</th>
          <th @click="trier('taux_horaire')">Taux horaire (Ar) {{ triColonne === 'taux_horaire' ? (triOrdre === 'asc' ? '↑' : '↓') : '' }}</th>
          <th @click="trier('prestation')">Prestation (Ar) {{ triColonne === 'prestation' ? (triOrdre === 'asc' ? '↑' : '↓') : '' }}</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="enseignant in enseignantsAffiches" :key="enseignant.id">
          <td>{{ enseignant.matricule }}</td>
          <td>{{ enseignant.nom.toUpperCase() }}</td>
          <td>{{ enseignant.prenom.charAt(0).toUpperCase() + enseignant.prenom.slice(1).toLowerCase() }}</td>
          <td>{{ enseignant.nombre_heures }}</td>
          <td>{{ enseignant.taux_horaire }}</td>
          <td>{{ enseignant.prestation }}</td>
          <td class="actions">
            <button class="btn-modifier" @click="modifier(enseignant)">Modifier</button>
            <button class="btn-supprimer" @click="supprimerEnseignant(enseignant.id)">Supprimer</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Modal de modification -->
  <div v-if="showModal" class="overlay">
    <div class="modal">
    <h3>Modifier l'enseignant</h3>
    <form @submit.prevent="sauvegarder">
      <div class="form-group">
        <label for="matricule">Matricule</label>
        <input id="matricule" v-model="enseignantSelectionne.matricule" placeholder="Matricule" @blur="verifierMatriculeModif" />
        <span v-if="erreurMatriculeModif" class="erreur-champ">{{ erreurMatriculeModif }}</span>
      </div>
      <div class="form-group">
        <label for="nom">Nom</label>
        <input id="nom" v-model="enseignantSelectionne.nom" placeholder="Nom" />
        <span v-if="erreurNomModif" class="erreur-champ">{{ erreurNomModif }}</span>
      </div>
      <div class="form-group">
        <label for="prenom">Prénom</label>
        <input id="prenom" v-model="enseignantSelectionne.prenom" placeholder="Prénom" />
      </div>
      <div class="form-group">
        <label for="nombre_heures">Nombre d'heures</label>
        <input id="nombre_heures" v-model="enseignantSelectionne.nombre_heures" type="number" step="0.01" />
      </div>
      <div class="form-group">
        <label for="taux_horaire">Taux horaire</label>
        <input id="taux_horaire" v-model="enseignantSelectionne.taux_horaire" type="number" step="0.01" />
      </div>
    <div class="modal-btns">
      <button class="btn-sauvegarder" type="submit">Sauvegarder</button>
      <button type="button" class="btn-annuler" @click="showModal = false">Annuler</button>
    </div>
    </form>
    </div>
  </div>
  <ToastMessage :message="message" :type="messageType" />
</template>

<style scoped>
.page {
  max-width: 900px;
  margin: 0 auto;
}

h1 {
  font-size: 1.8rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1.5rem;
}

.recherche {
  width: 100%;
  max-width: 320px;
  padding: 0.6rem 0.8rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

th {
  cursor: pointer;
  user-select: none;
}

.message {
  font-size: 0.85rem;
  color: #27ae60;
  margin-bottom: 1rem;
}

.table-wrapper {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  overflow: hidden;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: #2c3e50;
  color: white;
}

th {
  padding: 0.9rem 1rem;
  text-align: left;
  font-size: 0.85rem;
  font-weight: 500;
}

td {
  padding: 0.8rem 1rem;
  font-size: 0.9rem;
  color: #333;
  border-bottom: 1px solid #f0f2f5;
}

tbody tr:hover {
  background: #f8f9fa;
}

tbody tr:last-child td {
  border-bottom: none;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.erreur-champ {
  color: #e74c3c;
  font-size: 0.8rem;
  margin-top: 0.2rem;
}

.btn-modifier {
  padding: 0.35rem 0.8rem;
  background: #2c3e50;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 0.8rem;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-modifier:hover {
  background: #3d5166;
}

.btn-supprimer {
  padding: 0.35rem 0.8rem;
  background: transparent;
  color: #e74c3c;
  border: 1px solid #e74c3c;
  border-radius: 4px;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-supprimer:hover {
  background: #e74c3c;
  color: white;
}

/* Modal */
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 200;
}

.modal {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  width: 100%;
  max-width: 400px;
}

.modal h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  margin-bottom: 1rem;
}

label {
  font-size: 0.85rem;
  color: #555;
  font-weight: 500;
}

input {
  padding: 0.6rem 0.8rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 0.2s;
}

input:focus {
  border-color: #2c3e50;
}

.modal-btns {
  display: flex;
  gap: 0.8rem;
  margin-top: 1.5rem;
  justify-content: flex-end;
}

.btn-annuler {
  padding: 0.5rem 1.2rem;
  background: white;
  border: 1px solid #ddd;
  border-radius: 6px;
  color: #555;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-annuler:hover {
  background: #f0f2f5;
}

.btn-sauvegarder {
  padding: 0.5rem 1.2rem;
  background: #2c3e50;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-sauvegarder:hover {
  background: #3d5166;
}
</style>