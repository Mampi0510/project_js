<script setup>
  import {API_URL} from '@/config.js'
  import ToastMessage from './ToastMessage.vue'
  import { useRouter } from 'vue-router';
  import { ref } from 'vue';
  /*ref: rendre une variable réactive, 
  c'est à dire que le composant se met à jour automatiquement quand la variable change*/

  const router = useRouter()
  const matricule = ref('');
  const nom = ref('');
  const prenom = ref('');
  const nombre_heures = ref('');
  const taux_horaire = ref('');
  const message = ref('');
  const messageType = ref('success')
  const chargement = ref(false)
  const erreurMatricule = ref('')
  const erreurNom = ref('')

  async function ajouterEnseignant() {
    erreurMatricule.value = !matricule.value ? 'Ce champ est obligatoire' : ''
    erreurNom.value = !nom.value ? 'Ce champ est obligatoire' : ''

    if (erreurMatricule.value || erreurNom.value) {
      return
    }
    chargement.value = true
    try {
    const response = await fetch (`${API_URL}/enseignant.php`, {
      credentials: 'include',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        matricule: matricule.value,
        nom: nom.value,
        prenom: prenom.value,
        nombre_heures: nombre_heures.value,
        taux_horaire: taux_horaire.value
      })
    });

    const data = await response.json()
    message.value = data.message
    messageType.value = data.message.includes('réussie') ? 'success' : 'error'

    if (data.message.includes('réussie')) {
      matricule.value = ''
      nom.value = ''
      prenom.value = ''
      nombre_heures.value = ''
      taux_horaire.value = ''
      setTimeout(() => {
        router.push('/liste')
      }, 1000)
    } else {
      chargement.value = false
      setTimeout(() => { message.value = '' }, 3000)
    }
  } catch {
    message.value = 'Impossible de contacter le serveur'
    messageType.value = 'error'
    chargement.value = false
    setTimeout(() => { message.value = '' }, 3000)
  } 
}
</script>
<template>
  <div class="page">
    <h1 class="bienvenue">Bienvenue</h1>
    <div class="card">
      <h2>Ajouter un enseignant</h2>
      <form @submit.prevent="ajouterEnseignant">
        <div class="form-group">
          <label for="matricule">Matricule</label>
          <input id="matricule" v-model="matricule" placeholder="TC1" maxlength="10">
          <span v-if="erreurMatricule" class="erreur-champ">{{ erreurMatricule }}</span>
        </div>
        <div class="form-group">
          <label for="nom">Nom</label>
          <input id="nom" v-model="nom" placeholder="Rakoto">
          <span v-if="erreurNom" class="erreur-champ">{{ erreurNom }}</span>
        </div>
        <div class="form-group">
          <label for="prenom">Prénom</label>
          <input id="prenom" v-model="prenom" placeholder="Jean">
        </div>
        <div class="form-group">
          <label for="nombre_heures">Nombre d'heures</label>
          <input id="nombre_heures" v-model="nombre_heures" type="number" placeholder="12">
        </div>
        <div class="form-group">
          <label for="taux_horaire">Taux horaire</label>
          <input id="taux_horaire" v-model="taux_horaire" type="number" placeholder="5000">
        </div>
        <button type="submit" :disabled="chargement">
          {{ chargement ? 'Ajout en cours...' : 'Ajouter' }}
        </button>
      </form>
    </div>
  </div>
  <ToastMessage :message="message" :type="messageType" />
</template>
<style scoped>
.page {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-bottom: 2rem;
}

.bienvenue {
  text-align: center;
  font-size: 1.8rem;
  font-weight: 600;
  color: #2c3e50;
  margin-top: 0;
  margin-bottom: 1rem;
}

.card {
  background: white;
  padding: 1.8rem 2.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.27);
  width: 100%;
  max-width: 420px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  margin-bottom: 0.9rem;
}

h2 {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 2rem;
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

.erreur-champ {
  color: #e74c3c;
  font-size: 0.8rem;
  margin-top: 0.2rem;
}

button {
  width: 100%;
  padding: 0.7rem;
  background: #2c3e50;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  cursor: pointer;
  margin-top: 0.5rem;
  transition: background 0.2s;
}

button:hover {
  background: #3d5166;
}

.message {
  font-size: 0.85rem;
  color: #27ae60;
  margin-bottom: 0.5rem;
}
</style>
