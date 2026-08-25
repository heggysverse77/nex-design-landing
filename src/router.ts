import { createRouter, createWebHistory } from 'vue-router'
import LandingView from './views/LandingView.vue'
import AuthView from './views/AuthView.vue'
import AdminDashboardView from './views/AdminDashboardView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: LandingView },
    { path: '/early-access', component: AuthView },
    { path: '/signup', component: AuthView },
    { path: '/register', component: AuthView },
    { path: '/login', component: AuthView },
    { path: '/signin', component: AuthView },
    { path: '/account', component: AuthView },
    { path: '/admin', component: AdminDashboardView },
    { path: '/:pathMatch(.*)*', redirect: '/' }
  ],
  scrollBehavior(to, from, savedPosition) {
    if (to.hash) {
      return { el: to.hash, behavior: 'smooth' }
    }
    if (savedPosition) {
      return savedPosition
    }
    return { top: 0 }
  }
})

export default router


