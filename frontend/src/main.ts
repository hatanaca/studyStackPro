import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'
import { SESSION_NOT_READY } from '@/api/client'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import Aura from '@primeuix/themes/aura'
import App from './App.vue'
import router from './router'
import './assets/styles/main.css'
import 'primeicons/primeicons.css'
import 'viewerjs/dist/viewer.css'
import VueViewer from 'v-viewer'

/**
 * Bootstrap da aplicação Vue.
 * Plugins: Pinia, Vue Query, Router, PrimeVue (Aura), Confirmation, Toast, v-viewer.
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

function defaultQueryRetry(failureCount: number, error: unknown): boolean {
  if (error instanceof Error && error.message === SESSION_NOT_READY) return false
  const status = (error as { response?: { status?: number } })?.response?.status
  if (status === 401 || status === 403) return false
  return failureCount < 2
}

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 60 * 1000,
      refetchOnWindowFocus: false,
      retry: defaultQueryRetry,
    },
  },
})

/** Instância principal da aplicação */
const app = createApp(App)
app.use(createPinia())
app.use(VueQueryPlugin, { queryClient })
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
app.use(VueViewer, {
  defaultOptions: {
    zIndex: 12000,
  },
})
app.mount('#app')
