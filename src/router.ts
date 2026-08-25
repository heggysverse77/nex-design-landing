import { createRouter, createWebHistory } from 'vue-router'
import LandingView from './views/LandingView.vue'
import AdminDashboardView from './views/AdminDashboardView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: LandingView },
    { path: '/admin', component: AdminDashboardView },
    { path: '/:pathMatch(.*)*', redirect: '/' }
  ]
})

export default router

