import { createHead } from '@unhead/vue/client'
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { vReveal } from './directives/reveal'
import './style.css'

const head = createHead()
const app = createApp(App)

app.directive('reveal', vReveal)
app.use(router).use(head).mount('#app')
