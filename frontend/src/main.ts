import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { VueQueryPlugin } from '@tanstack/vue-query'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import Aura from '@primeuix/themes/aura'
import App from './App.vue'
import router from './router'
import './assets/styles/main.css'
import 'primeicons/primeicons.css'

/**
 * Bootstrap da aplicação Vue.
 * Plugins: Pinia, Vue Query, Router, PrimeVue (Aura), Confirmation, Toast.
 * Tema (light/dark) aplicado no document antes do primeiro render.
 */
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

/** Instância principal da aplicação */
const app = createApp(App)
app.use(createPinia())
app.use(VueQueryPlugin)
app.use(router)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: '[data-theme="dark"]',
      cssLayer: false,
    },
  },
})
app.use(ConfirmationService)
app.use(ToastService)
app.mount('#app')
