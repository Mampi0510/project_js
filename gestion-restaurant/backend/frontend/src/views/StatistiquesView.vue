<script setup>
import { ref, onMounted, computed } from 'vue';
import { statistiqueApi } from '../services/api.js';

const recetteTotale = ref(0);
const histogramme = ref([]);
const top10 = ref([]);
const chargement = ref(true);
const erreur = ref('');

// La plus grande recette mensuelle sert à calculer la hauteur relative des barres
// de l'histogramme (barre la plus haute = 100% de hauteur disponible)
const recetteMax = computed(() => {
  if (histogramme.value.length === 0) return 0;
  return Math.max(...histogramme.value.map((m) => m.recette));
});
// Réorganise chronologiquement les mois (ex: "2026-01", "2026-02", "2026-03")
const histogrammeTrie = computed(() => {
  return [...histogramme.value].sort((a, b) => a.mois.localeCompare(b.mois));
});

function hauteurBarre(recette) {
  if (recetteMax.value === 0) return 0;
  return Math.round((recette / recetteMax.value) * 100);
}

// Affiche "2026-06" sous une forme plus lisible, ex: "Juin 2026"
function formaterMois(cleMois) {
  const [annee, mois] = cleMois.split('-');
  const noms = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
  ];
  return `${noms[Number(mois) - 1]} ${annee}`;
}

async function chargerStatistiques() {
  chargement.value = true;
  erreur.value = '';
  try {
    const [recette, hist, top] = await Promise.all([
      statistiqueApi.getRecetteTotale(),
      statistiqueApi.getHistogramme6Mois(),
      statistiqueApi.getTop10Plats(),
    ]);
    recetteTotale.value = recette.recetteTotale;
    histogramme.value = hist;
    top10.value = top;
  } catch (e) {
    erreur.value = e.message;
  } finally {
    chargement.value = false;
  }
}

onMounted(chargerStatistiques);
</script>

<template>
  <div class="statistiques-view">
    <h1>Statistiques</h1>

    <p v-if="erreur" class="erreur">{{ erreur }}</p>
    <p v-if="chargement">Chargement...</p>

    <template v-else>
      <section class="bloc-recette">
        <h2>Recette totale accumulée</h2>
        <p class="montant-recette">{{ Number(recetteTotale).toLocaleString('fr-FR') }} Ar</p>
        <p class="note">(commandes payées uniquement)</p>
      </section>

      <section class="bloc-histogramme">
        <h2>Recettes des 6 derniers mois</h2>
        <div class="histogramme">
          <div v-for="m in histogrammeTrie" :key="m.mois" class="barre-conteneur">
            <div class="barre" :style="{ height: hauteurBarre(m.recette) + '%' }"></div>
            <span class="valeur-barre">{{ Number(m.recette).toLocaleString('fr-FR') }}</span>
            <span class="label-mois">{{ formaterMois(m.mois) }}</span>
          </div>
        </div>
      </section>

      <section class="bloc-top10">
        <h2>Top 10 des plats les plus vendus</h2>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Plat</th>
              <th>Quantité vendue</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(plat, index) in top10" :key="plat.idplat">
              <td>{{ index + 1 }}</td>
              <td>{{ plat.nomplat }}</td>
              <td>{{ plat.quantiteTotale }}</td>
            </tr>
            <tr v-if="top10.length === 0">
              <td colspan="3">Aucune donnée disponible.</td>
            </tr>
          </tbody>
        </table>
      </section>
    </template>
  </div>
</template>

<style scoped>
.statistiques-view {
  max-width: 900px;
  margin: 0 auto;
  padding: 1.5rem;
}

section {
  margin-bottom: 2.5rem;
}

.bloc-recette {
  color: #ffffff;
  text-align: center;
  padding: 2rem 1.5rem;
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(13, 27, 50, 0.2);
}

.montant-recette {
  font-size: 2.5rem;
  font-weight: 700;
  color: #069e29;
  margin: 0.3rem 0;
}

.note {
  color: #8d8c8c;
  font-size: 0.85rem;
}

.histogramme {
  display: flex;
  align-items: flex-end;
  gap: 1rem;
  height: 220px;
  padding: 1rem 0;
  border-bottom: 2px solid #ddd;
}

.barre-conteneur {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  height: 100%;
}

.barre {
  width: 60%;
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);  border-radius: 12px;
  border-radius: 6px 6px 0 0;
  transition: height 0.3s ease;
  min-height: 2px;
}

.valeur-barre {
  font-size: 0.75rem;
  margin-top: 0.3rem;
  color: #444;
}

.label-mois {
  font-size: 0.8rem;
  margin-top: 0.2rem;
  color: #666;
}

.bloc-top10 table {
  width: 100%;
  border-collapse: collapse;
}

.bloc-top10 th,
.bloc-top10 td {
  border: 1px solid #ddd;
  padding: 0.5rem;
  text-align: left;
}

.erreur {
  color: #c0392b;
  background: #fdecea;
  padding: 0.6rem 1rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}
</style>