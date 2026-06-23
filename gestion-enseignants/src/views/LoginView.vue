<script setup>
    import ToastMessage from './ToastMessage.vue'
    import { API_URL } from '@/config.js'
    import { ref } from 'vue';
    const username = ref('');
    const password = ref('');
    const message = ref('')
    const messageType = ref('error')

    const emit = defineEmits(['connexion-reussie'])

  async function seConnecter() {
    try {
      const response = await fetch(`${API_URL}/auth.php`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'login', username: username.value, password: password.value })
      })
      const data = await response.json()

      if (data.success) {
        message.value = 'Connexion réussie'
        messageType.value = 'success'
        setTimeout(() => {
          message.value = ''
          emit('connexion-reussie')
        }, 1000)
      } else {
        message.value = data.message
        messageType.value = 'error'
        setTimeout(() => { message.value = '' }, 3000)
      }
    } catch {
      message.value = 'Impossible de contacter le serveur'
      messageType.value = 'error'
      setTimeout(() => { message.value = '' }, 3000)
    }
  }
</script>

<template>
    <div class="login-page">
        <div class="login-card">
            <h1>Connexion</h1>
            <form @submit.prevent="seConnecter">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input v-model="username" placeholder="Entrez votre nom">
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input v-model="password" type="password" placeholder="Entrez votre mot de passe">
                </div>
                <button type="submit">Se connecter</button>
            </form>    
        </div>
    </div>
    <ToastMessage :message="message" :type="messageType" />
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-card {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.314);
  width: 100%;
  max-width: 380px;
}

h1 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 2rem;
  text-align: center;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  margin-bottom: 1.2rem;
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
  transition: border-color 0.2s;
  outline: none;
}

input:focus {
  border-color: #2c3e50;
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

.erreur {
  color: #e74c3c;
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
}
</style>