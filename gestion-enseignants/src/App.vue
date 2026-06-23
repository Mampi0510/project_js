<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { API_URL } from '@/config.js'

// sessionStorage persiste pendant la session — c'est la mémoire du navigateur
const isLoggedIn = ref(false)
const router = useRouter()

async function verifierSession() {
  try {
    const response = await fetch(`${API_URL}/auth.php?check=1`, { credentials: 'include' })
    const data = await response.json()
    isLoggedIn.value = data.connecte
  } catch {
    isLoggedIn.value = false
  }
}

function connecter() {
  isLoggedIn.value = true
  router.push('/ajout')
}

async function seDeconnecter() {
  if (!confirm('Voulez-vous vraiment vous déconnecter ?')) return
  await fetch(`${API_URL}/auth.php`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'logout' })
  })
  isLoggedIn.value = false
  router.push('/')
}

onMounted(verifierSession)

</script>

<template>
  <nav v-if="isLoggedIn">
    <div class="nav-brand"> Gestion Enseignants</div>
    <div class="nav-links">
      <RouterLink to="/ajout">Ajout</RouterLink>
      <RouterLink to="/liste">Liste</RouterLink>
      <RouterLink to="/bilan">Bilan</RouterLink>
    </div>
    <button class="btn-deconnexion" @click="seDeconnecter">Se déconnecter</button>
  </nav>
  <main :class="{ 'with-nav': isLoggedIn}">
    <RouterView @connexion-reussie="connecter" />
  </main>
</template>

<style scoped>
nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 60px;
  background: #2c3e50;
  display: flex;
  align-items: center;
  padding: 0 2rem;
  gap: 2rem;
  z-index: 100;
}

.nav-brand {
  color: white;
  font-weight: 600;
  font-size: 1.5rem;
  margin-right: auto;
}

.nav-links {
  display: flex;
  gap: 1.5rem;
}

.nav-links a {
  color: #bdc3c7;
  text-decoration: none;
  font-size: 0.95rem;
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
  transition: color 0.2s;
}

.nav-links a:hover,
.nav-links a.router-link-active {
  color: white;
  background: rgba(255,255,255,0.1);
}

.btn-deconnexion {
  background: transparent;
  border: 1px solid #e74c3c;
  color: #e74c3c;
  padding: 0.3rem 0.8rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.btn-deconnexion:hover {
  background: #e74c3c;
  color: white;
}

main {
  padding: 2rem;
}

main.with-nav {
  padding-top: 60px;
}
</style>