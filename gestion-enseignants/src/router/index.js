import AjoutView from '@/views/AjoutView.vue'
import BilanView from '@/views/BilanView.vue'
import ListeView from '@/views/ListeView.vue'
import LoginView from '@/views/LoginView.vue'
import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/ajout',
      name: 'ajout',
      component: AjoutView,
      meta: { requiresAuth: true },
    },
    {
      path: '/bilan',
      name: 'bilan',
      component: BilanView,
      meta: { requiresAuth: true },
    },
    {
      path: '/liste',
      name: 'liste',
      component: ListeView,
      meta: { requiresAuth: true },
    },
    {
      path: '/',
      name: 'login',
      component: LoginView,
    },
  ],
})

router.beforeEach((to) => {
  const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true'

  if (to.meta.requiresAuth && !isLoggedIn) {
    return { name: 'login' }
  }
  if (!to.meta.requiresAuth && isLoggedIn) {
    return { name: 'ajout' }
  }
  // to   = route destination
  // from = route d'où on vient
  // retourner false ou une route = bloquer/rediriger
})

export default router
