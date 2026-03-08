import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useAnalyticsStore } from '@/stores/analytics.store'
import { useSessionsStore } from '@/stores/sessions.store'
import type { ActiveSessionResponse } from '@/api/modules/sessions.api'
import type { MetricsUpdatedEvent, SessionStartedEvent } from '@/types/websocket.types'

/** Interface mínima do Laravel Echo usada neste composable (evita dependência de tipos do pacote). */
interface EchoChannel {
  listen: (event: string, callback: (e: unknown) => void) => EchoChannel
}
interface EchoInstance {
  disconnect: () => void
  private: (channel: string) => EchoChannel
  connector: {
    pusher: {
      connection: { bind: (event: string, callback: () => void) => void }
    }
  }
}

const isConnected = ref(false)
let echo: EchoInstance | null = null

export function useWebSocket() {
  const authStore = useAuthStore()
  const analyticsStore = useAnalyticsStore()

  async function connect(userId: string) {
    if (typeof window === 'undefined') return
    if (import.meta.env.VITE_REVERB_ENABLED === 'false') return

    disconnect()

    const [{ default: Echo }, { default: Pusher }] = await Promise.all([
      import('laravel-echo'),
      import('pusher-js'),
    ])

    window.Pusher = Pusher

    const sessionsStore = useSessionsStore()
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
    }) as EchoInstance

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
      .listen('.metrics.updated', (e: unknown) => {
        const ev = e as MetricsUpdatedEvent
        if (ev.dashboard) analyticsStore.updateFromWebSocket(ev.dashboard)
      })
      .listen('.metrics.recalculating', () => {
        analyticsStore.setRecalculating(true)
      })
      .listen('.session.started', (e: unknown) => {
        const ev = e as SessionStartedEvent
        if (ev.session) {
          const s = ev.session
          const payload: ActiveSessionResponse = {
            id: s.id,
            user_id: userId,
            technology_id: s.technology?.id ?? '',
            technology: s.technology
              ? { ...s.technology, slug: s.technology.id, is_active: true }
              : undefined,
            started_at: s.started_at,
            ended_at: null,
            duration_min: null,
            created_at: s.started_at,
            elapsed_seconds: s.elapsed_seconds ?? 0,
            notes: null,
            mood: null,
          }
          sessionsStore.setActiveSession(payload)
        }
      })
      .listen('.session.ended', () => {
        sessionsStore.clearActiveSession()
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
