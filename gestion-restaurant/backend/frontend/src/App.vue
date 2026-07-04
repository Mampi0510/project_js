<script setup>
// App.vue est le composant racine : il affiche une nav simple
// et la zone <router-view> où s'affiche la page active.
</script>

<template>
  <nav class="nav-principale">
    <router-link to="/menu">Menu</router-link>
    <router-link to="/tables">Tables</router-link>
    <router-link to="/commandes">Commandes</router-link>
    <router-link to="/reservations">Réservations</router-link>
    <router-link to="/statistiques">Statistiques</router-link>
  </nav>

  <router-view />
</template>

<style scoped>
.nav-principale {
  display: flex;
  gap: 1.5rem;
  padding: 1rem 1.5rem;
  background: #2c3e50;
}

.nav-principale a {
  color: #bdc3c7;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
  transition: color 0.2s;
}

.nav-principale a.router-link-active {
  text-decoration:none;

}

.nav-principale a:hover,
.nav-principale a.router-link-active{
  color: white;
  background: rgba(255,255,255,0.1);
}
</style>