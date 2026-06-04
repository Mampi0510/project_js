<script setup>
  import { ref } from 'vue';
  /*ref: rendre une variable réactive, 
  c'est à dire que le composant se met à jour automatiquement quand la variable change*/

  const matricule = ref('');
  const nom = ref('');
  const nombre_heures = ref('');
  const taux_horaire = ref('');
  const message = ref('');

  async function ajouterEnseignant() {
    try {
    const response = await fetch ('http://localhost/backend/enseignant.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        matricule: matricule.value,
        nom: nom.value,
        nombre_heures: nombre_heures.value,
        taux_horaire: taux_horaire.value
      })
    });

    if (!response.ok) { message.value = 'Erreur serveur'; return; }
    const data = await response.json();
    message.value = data.message;
  } catch (error) {
    message.value = 'Impossible de contacter le serveur';
    }
  }
</script>
<template>
  <form @submit.prevent="ajouterEnseignant">
    <h2>Ajouter un enseignant</h2>
    <input v-model="matricule" placeholder="Matricule">
    <input v-model="nom" placeholder="Nom">
    <input v-model="nombre_heures" type="number" placeholder="Nombre d'heures">
    <input v-model="taux_horaire" type="number" placeholder="Taux horaire">
    <button type="submit">Ajouter</button>
    <p v-if="message">{{ message }}</p>
  </form>
</template>
