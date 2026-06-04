<script setup>
/* computed: se recalcule automatiquement quand enseignants change
  Cette variable dépend d'une autre : enseignants change alors total se recalcule tout seul*/
import { ref, onMounted, computed } from 'vue'
import { Chart, registerables } from 'chart.js' //tableau qui contient tous les composants de Chart.js
Chart.register(...registerables) // active tous les types de graphiques

const enseignants = ref([])
const monCanvas = ref(null) //ref pour accéder à l'élément canvas dans le template
const message = ref('')

async function chargerEnseignants() {
  try {
  const response = await fetch('http://localhost/backend/enseignant.php')
  if (!response.ok) { message.value = 'Erreur serveur'; return; }
  enseignants.value = await response.json()
  creerGraphique()
  } catch (error) {
    message.value = 'Impossible de contacter le serveur';
  }
}

function creerGraphique() {
  new Chart(monCanvas.value, {
    type: 'bar',
    data: {
      labels: enseignants.value.map((e) => e.nom), //noms des enseignants
      datasets: [
        {
          label: 'Prestation (Ar)',
          data: enseignants.value.map((e) => e.prestation), //prestations des enseignants
          backgroundColor: '#42b883',
        },
      ],
    },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true } },
    },
  })
}

// reduce:parcourt le tableau et accumule une valeur en fonction de la prestation de chaque enseignant
// .toFixed:Arrondit à 2 décimales
const total = computed(() => enseignants.value.length === 0 ? '-' : enseignants.value.reduce((acc, e) => acc + e.prestation, 0).toFixed(2))
const min = computed(() => enseignants.value.length === 0 ? '-' : Math.min(...enseignants.value.map(e => e.prestation)).toFixed(2))
const max = computed(() => enseignants.value.length === 0 ? '-' : Math.max(...enseignants.value.map(e => e.prestation)).toFixed(2))

onMounted(chargerEnseignants)
</script>

<template>
  <form @submit.prevent="chargerEnseignants">
    <div>
      <h1>Bilan</h1>

    <!-- stat -->
    <p>Total : {{ total }}</p>
    <p>Minimum : {{ min }}</p>
    <p>Maximum : {{ max }}</p>
    <p v-if="message">{{ message }}</p>

    <!-- Histogramme -->
    <canvas ref="monCanvas"></canvas>
    </div>
  </form>
</template>
