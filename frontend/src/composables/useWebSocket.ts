import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useAnalyticsStore } from '@/stores/analytics.store'
import type { MetricsUpdatedEvent } from '@/types/websocket.types'

declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}

if (typeof window !== 'undefined') {
  window.Pusher = Pusher
}

const isConnected = ref(false)
let echo: Echo<'reverb'> | null = null

export function useWebSocket() {
  const authStore = useAuthStore()
  const analyticsStore = useAnalyticsStore()

  function connect(userId: string) {
    if (typeof window === 'undefined') return

    disconnect()

    const scheme = import.meta.env.VITE_REVERB_SCHEME || 'http'
    const host = import.meta.env.VITE_REVERB_HOST || 'localhost'
    const port = import.meta.env.VITE_REVERB_PORT || '8080'
    const key = import.meta.env.VITE_REVERB_APP_KEY || 'local-key'
    const apiUrl = import.meta.env.VITE_API_URL || ''

    echo = new Echo({
      broadcaster: 'reverb',
      key,
      wsHost: host,
      wsPort: parseInt(port, 10),
      wssPort: scheme === 'https' ? 443 : 443,
      forceTLS: scheme === 'https',
      enabledTransports: ['ws', 'wss'],
      authEndpoint: `${apiUrl || ''}/api/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${authStore.token}`,
          Accept: 'application/json'
        }
      }
    })

    echo.connector.pusher.connection.bind('connected', () => {
      isConnected.value = true
    })
    echo.connector.pusher.connection.bind('disconnected', () => {
      isConnected.value = false
    })
    echo.connector.pusher.connection.bind('failed', () => {
      isConnected.value = false
    })

    echo.private(`dashboard.${userId}`)
      .listen('.metrics.updated', (e: MetricsUpdatedEvent) => {
        if (e.dashboard) analyticsStore.updateFromWebSocket(e.dashboard)
      })
      .listen('.metrics.recalculating', () => {
        analyticsStore.setRecalculating(true)
      })
  }

  function disconnect() {
    if (echo) {
      echo.disconnect()
      echo = null
    }
    isConnected.value = false
  }

  return { connect, disconnect, isConnected }
}
