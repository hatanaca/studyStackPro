import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/styles/main.css'

// Aplicar tema salvo antes do primeiro render (login e app)
const savedTheme = (() => {
  try {
    const t = localStorage.getItem('studytrack.theme')
    if (t === 'dark' || t === 'light') return t
  } catch {
    // ignore
  }
  return 'light'
})()
document.documentElement.setAttribute('data-theme', savedTheme)

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
