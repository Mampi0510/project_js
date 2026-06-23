<script setup>
defineProps(['message', 'type'])
</script>

<template>
  <transition name="toast">
    <div v-if="message" class="toast" :class="type">
      <span>{{ message }}</span>
    </div>
  </transition>
</template>

<style scoped>
.toast {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.8rem 1.2rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  z-index: 1000;
  max-width: 320px;
}

.toast.success {
  background: #eafaf1;
  color: #27ae60;
  border-left: 4px solid #27ae60;
}

.toast.error {
  background: #fdedec;
  color: #e74c3c;
  border-left: 4px solid #e74c3c;
}

.icon {
  font-size: 1.1rem;
}

.toast-enter-active, .toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
</style>