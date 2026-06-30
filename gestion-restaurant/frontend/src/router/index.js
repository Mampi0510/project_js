// Configuration du routage. Une seule route pour l'instant (Menu) --
// les autres (Table, Commande, Reservation, Statistiques) seront ajoutées
// une fois ce premier câblage validé

import { createRouter, createWebHistory } from 'vue-router';
import MenuView from '../views/MenuView.vue';
import TableView from '../views/TableView.vue';
import CommandeView from '../views/CommandeView.vue';
import ReservationView from '../views/ReservationView.vue';
import StatistiquesView from '../views/StatistiquesView.vue';

const routes = [
  { path: '/', redirect: '/menu' },
  { path: '/menu', name: 'menu', component: MenuView },
  { path: '/tables', name: 'tables', component: TableView },
  { path: '/commandes', name: 'commandes', component: CommandeView },
  { path: '/reservations', name: 'reservations', component: ReservationView },
  { path: '/statistiques', name: 'statistiques', component: StatistiquesView },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
