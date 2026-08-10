import { createRouter, createWebHistory } from 'vue-router'
import LandingView from './views/LandingView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: LandingView },
    { path: '/:pathMatch(.*)*', redirect: '/' }
  ]
})

export default router
