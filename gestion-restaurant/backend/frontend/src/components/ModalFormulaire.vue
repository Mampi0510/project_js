<script setup>
// Modale générique réutilisable : fournit la coquille visuelle (fond sombre, boîte centrée,
// titre, bouton fermer) et laisse le contenu du formulaire être injecté par la vue parente.
// Usage : <ModalFormulaire :ouvert="afficherModal" titre="..." @fermer="afficherModal = false">
//           ... le formulaire ...
//         </ModalFormulaire>

defineProps({
  ouvert: { type: Boolean, required: true },
  titre: { type: String, default: '' },
});

const emit = defineEmits(['fermer']);

function fermer() {
  emit('fermer');
}

// Cliquer sur le fond sombre (hors de la boîte) ferme la modale, comme une UX standard
function fermerSiClicSurFond(evenement) {
  if (evenement.target === evenement.currentTarget) {
    fermer();
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="ouvert" class="fond-modal" @click="fermerSiClicSurFond" @keydown.esc="fermer">
      <div class="boite-modal" role="dialog" aria-modal="true">
        <div class="en-tete-modal">
          <h2>{{ titre }}</h2>
          <button type="button" class="bouton-fermer" @click="fermer" aria-label="Fermer">×</button>
        </div>
        <div class="contenu-modal">
          <slot />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
/* z-index élevé pour passer au-dessus de tout le reste de l'application,
   y compris la nav principale (qui n'a pas de z-index explicite, donc empile à 0/auto) */
.fond-modal {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.boite-modal {
  background: white;
  border-radius: 10px;
  width: 100%;
  max-width: 480px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 35px rgba(0, 0, 0, 0.25);
}

.en-tete-modal {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #eee;
}

.en-tete-modal h2 {
  margin: 0;
  font-size: 1.15rem;
}

.bouton-fermer {
  background: none;
  border: none;
  color: #555;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
  padding: 0 0.3rem;
}

.bouton-fermer:hover {
  color: #000;
  background: none;
}

.contenu-modal {
  padding: 1.25rem;
}
</style>