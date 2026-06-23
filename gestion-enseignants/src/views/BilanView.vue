<script setup>
  import {API_URL} from '@/config.js'
  /* computed: se recalcule automatiquement quand enseignants change
    Cette variable dépend d'une autre : enseignants change alors total se recalcule tout seul*/
  import { ref, onMounted, computed } from 'vue'
  import { Chart, registerables } from 'chart.js' //tableau qui contient tous les composants de Chart.js
  Chart.register(...registerables) // active tous les types de graphiques

  const enseignants = ref([])
  const monCanvas = ref(null) //ref pour accéder à l'élément canvas dans le template
  const monPie = ref(null)
  const message = ref('')

async function chargerEnseignants() {
  try {
  const response = await fetch(`${API_URL}/enseignant.php`, { credentials: 'include' })
  if (!response.ok) { message.value = 'Erreur serveur'; return; }
  enseignants.value = (await response.json()).map(e => ({ ...e, prestation: Number(e.prestation) }))  
  creerGraphique()
  } catch (error) {
    message.value = 'Impossible de contacter le serveur';
  }
}

function formaterNom(nom) {
  return nom.charAt(0).toUpperCase() + nom.slice(1).toLowerCase()
}

function creerGraphique() {
  // histogramme des prestations par enseignant
  new Chart(monCanvas.value, {
    type: 'bar',
    data: {
      labels: enseignants.value.map((e) => formaterNom(e.nom)), //noms des enseignants
      datasets: [
        {
          label: 'Prestation (Ar)',
          data: enseignants.value.map((e) => e.prestation), //prestations des enseignants
          backgroundColor: '#2c3e50',
          borderRadius: 4,
          borderSkipped: false,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, grid: { color: '#f0f2f5' }, ticks: { color: '#888' } },
        x: { grid: { display: false }, ticks: { color: '#888' } }
      }    
    },
  })

  // camembert de la répartition des prestations
  new Chart(monPie.value, {
    type: 'pie',
    data: {
      labels: enseignants.value.map(e => formaterNom(e.nom)),
      datasets: [{
        label: 'Prestation (Ar)',
        data: enseignants.value.map(e => e.prestation),
        backgroundColor: [
          '#2c3e50', '#e74c3c', '#27ae60', '#f39c12',
          '#8e44ad', '#16a085', '#2980b9', '#d35400',
          '#c0392b', '#2ecc71', '#34495e', '#f1c40f',
          '#9b59b6', '#1abc9c', '#e67e22', '#3498db'
        ]
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'right' }
      }
    }
  })
}


// reduce:parcourt le tableau et accumule une valeur en fonction de la prestation de chaque enseignant
// .toFixed:Arrondit à 2 décimales
const total = computed(() => enseignants.value.length === 0 ? '-' : enseignants.value.reduce((acc, e) => acc + e.prestation, 0).toFixed(2))
const enseignantMin = computed(() => enseignants.value.length === 0 ? null : enseignants.value.reduce((min, e) => e.prestation < min.prestation ? e : min))
const enseignantMax = computed(() => enseignants.value.length === 0 ? null : enseignants.value.reduce((max, e) => e.prestation > max.prestation ? e : max))
onMounted(chargerEnseignants)
</script>

<template>
 <div class="page">
    <h2>Bilan</h2>

    <p v-if="message" class="message-erreur">{{ message }}</p>

    <div class="stats">
      <div class="stat-card">
        <span class="stat-label">Total</span>
        <span class="stat-value">{{ total }} Ar</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Minimum</span>
        <span class="stat-value">{{ enseignantMin ? enseignantMin.prestation.toFixed(2) : '-' }} Ar</span>
        <span class="stat-nom" v-if="enseignantMin">({{ formaterNom(enseignantMin.nom) }})</span>
      </div>
      <div class="stat-card">
        <span class="stat-label">Maximum</span>
        <span class="stat-value">{{ enseignantMax ? enseignantMax.prestation.toFixed(2) : '-' }} Ar</span>
        <span class="stat-nom" v-if="enseignantMax">({{ formaterNom(enseignantMax.nom) }})</span>
      </div>
    </div>
    <div class="charts"> 
      <div class="chart-card">
        <h3>Répartition en histogramme</h3>
        <canvas ref="monCanvas"></canvas>
      </div>

      <div class="chart-card">
        <h3>Répartition en camembert</h3>
        <canvas ref="monPie"></canvas>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page {
  max-width: 1200px;
  margin: 0 auto;
}

h2 {
  font-size: 1.8rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1.5rem;
}

.message-erreur {
  color: #e74c3c;
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

/* Cartes stats */
.stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-label {
  font-size: 0.8rem;
  color: #888;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.stat-nom {
  font-size: 0.85rem;
  color: #888;
  margin-top: 0.2rem;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2c3e50;
}

/* Graphique */
.charts {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.chart-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
</style>