<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

// localStorage persiste même après F5 — c'est la mémoire du navigateur
const isLoggedIn = ref(localStorage.getItem('isLoggedIn') === 'true') //lire le js
const router = useRouter()

function connecter() {
  isLoggedIn.value = true
  localStorage.setItem('isLoggedIn', 'true') //sauvegarder le js
  router.push('/ajout')
  resetTimer()
}

function seDeconnecter() {
  isLoggedIn.value = false
  localStorage.removeItem('isLoggedIn')
  clearTimeout(timer)
  router.push('/')
}

let timer
function resetTimer() {
  clearTimeout(timer)
  timer = setTimeout(
    () => {
      seDeconnecter()
    },
    5 * 60 * 1000,
  ) // 5 minutes d'inactivité
}

onMounted(() => {
  window.addEventListener('mousemove', resetTimer)
  window.addEventListener('keydown', resetTimer)
  window.addEventListener('click', resetTimer)
})
</script>

<template>
  <nav v-if="isLoggedIn">
    <RouterLink to="/ajout">Ajout</RouterLink>
    <RouterLink to="/liste">Liste</RouterLink>
    <RouterLink to="/bilan">Bilan</RouterLink>
    <button @click="seDeconnecter">Se déconnecter</button>
  </nav>
  <RouterView @connexion-reussie="connecter" />
</template>

<style scoped></style>
