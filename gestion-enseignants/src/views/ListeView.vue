<script setup>
  import { ref,onMounted } from 'vue';
  /*onMounted: exécuter du code au chargement de la page*/
  
  const enseignants = ref([]); //ref pour rendre la variable réactive
  const message = ref('');

  async function chargerEnseignants() {
    try {
    const response = await fetch('http://localhost/backend/enseignant.php'); //fetch pour faire une requête http
    enseignants.value = await response.json(); //mettre à jour la variable réactive avec les données reçues
  } catch (error) {
    message.value = 'Impossible de contacter le serveur'; //afficher un message d'erreur en cas de problème de connexion
    }
  }
  async function supprimerEnseignant(id) {
    try {
    const response = await fetch(`http://localhost/backend/enseignant.php`, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }) //envoyer l'id de l'enseignant à supprimer
    });
    const data = await response.json();
    message.value = data.message; //afficher le message de succès ou d'erreur
    chargerEnseignants(); //recharger la liste après suppression
  } catch (error) {
    message.value = 'Impossible de contacter le serveur'; 
  }
  }
  const showModal = ref(false);
  const enseignantSelectionne = ref(null);

  function modifier(enseignant) {
    enseignantSelectionne.value = { ...enseignant }; //copier les données de l'enseignant sélectionné
    showModal.value = true; //afficher le modal de modification
  }

  async function sauvegarder() {
    try {
      const response = await fetch(`http://localhost/backend/enseignant.php`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(enseignantSelectionne.value) //envoyer les données modifiées de l'enseignant
      });
      if (!response.ok) { message.value = 'Erreur serveur'; return; }
      const data = await response.json();
      message.value = data.message; //afficher le message de succès ou d'erreur
      showModal.value = false; //cacher le modal après sauvegarde
      chargerEnseignants(); //recharger la liste après modification
  } catch {
    message.value = 'Impossible de contacter le serveur'; 
  }
}

  onMounted(chargerEnseignants); //exécuter la fonction après le rendu du composant
</script>

<template>
  <div>
    <h1>Liste des enseignants</h1>
    <table>
      <thead>
        <tr>
          <th>Matricule</th>
          <th>Nom</th>
          <th>Nombre d'heures</th>
          <th>Taux horaire</th>
          <th>Prestation</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="enseignant in enseignants" :key="enseignant.id">
          <td>{{ enseignant.matricule }}</td>
          <td>{{ enseignant.nom }}</td>
          <td>{{ enseignant.nombre_heures }}</td>
          <td>{{ enseignant.taux_horaire }}</td>
          <td>{{ enseignant.prestation }}</td>
          <td>
            <button @click="modifier(enseignant)">Modifier</button>
            <button @click="supprimerEnseignant(enseignant.id)">Supprimer</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  <form v-if="showModal" @submit.prevent="sauvegarder">
    <h3>Modifier</h3>
    <input v-model="enseignantSelectionne.matricule" placeholder="Matricule" />
    <input v-model="enseignantSelectionne.nom" placeholder="Nom" />
    <input v-model="enseignantSelectionne.nombre_heures" type="number" />
    <input v-model="enseignantSelectionne.taux_horaire" type="number" />
    <button type="submit">Sauvegarder</button>
    <button @click="showModal = false">Annuler</button>
  </form>
</template>
