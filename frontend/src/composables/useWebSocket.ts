import { ref } from 'vue'
import axios from 'axios'
import { useQueryClient } from '@tanstack/vue-query'
import { useAuthStore } from '@/stores/auth.store'
import { useAnalyticsStore } from '@/stores/analytics.store'
import { useSessionsStore } from '@/stores/sessions.store'
import { queryKeys } from '@/api/queryKeys'
import type { ActiveSessionResponse } from '@/api/modules/sessions.api'
import type { MetricsUpdatedEvent, SessionStartedEvent } from '@/types/websocket.types'

/**
 * Composables de WebSocket (Laravel Reverb).
 * Conecta ao canal privado dashboard.{userId}. Escuta: metrics.updated, metrics.recalculating,
 * session.started, session.ended. Atualiza analyticsStore e sessionsStore.
 *
 * Conexão é global (uma por app logado): use `connectWebSocket` / `disconnectWebSocket` sem
 * instanciar consumidores — evita vazamento de ref-count quando `useWebSocket()` era chamado
 * após `await` ou só para `disconnect()` no logout.
 */
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

/** Estado global de conexão WS (compartilhado entre instâncias) */
export const isConnected = ref(false)
let echo: EchoInstance | null = null
/** Se o broadcast de fim de recálculo falhar (ex.: payload grande), libera o spinner após este tempo */
const RECALC_FALLBACK_MS = 45_000
let recalcFallbackTimer: ReturnType<typeof setTimeout> | null = null

function clearRecalcFallbackTimer() {
  if (recalcFallbackTimer) {
    clearTimeout(recalcFallbackTimer)
    recalcFallbackTimer = null
  }
}

/** Conecta ao Reverb e subscreve ao canal privado dashboard.{userId} */
export async function connectWebSocket(userId: string): Promise<void> {
  const authStore = useAuthStore()
  const analyticsStore = useAnalyticsStore()
  let queryClient: ReturnType<typeof useQueryClient> | null = null
  try {
    queryClient = useQueryClient()
  } catch {
    /* outside query context */
  }

  if (typeof window === 'undefined') return
  if (import.meta.env.VITE_REVERB_ENABLED === 'false') return
  if (!authStore.sessionValidated) return

  const expectedUserId = authStore.user?.id
  if (!expectedUserId || String(expectedUserId) !== String(userId)) {
    return
  }

  disconnectWebSocket()

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
  const broadcastingAuthUrl = `${apiUrl.replace(/\/+$/, '')}/api/broadcasting/auth`

  /**
   * O transporte XHR do pusher-js não activa `withCredentials`; com API noutro host a sessão
   * Sanctum não chegava ao `/broadcasting/auth`. Axios envia cookies + CSRF como o resto da SPA.
   */
  const channelAuthorization = {
    /** Satisfaz tipos do Echo 2.x / pusher-js; a autorização real usa `customHandler` (cookies + CSRF). */
    transport: 'ajax' as const,
    endpoint: broadcastingAuthUrl,
    customHandler: (
      params: { socketId: string; channelName: string },
      callback: (error: Error | null, data: { auth: string; channel_data?: string } | null) => void
    ) => {
      const body = new URLSearchParams()
      body.set('socket_id', params.socketId)
      body.set('channel_name', params.channelName)
      axios
        .post(broadcastingAuthUrl, body, {
          withCredentials: true,
          withXSRFToken: true,
          headers: { Accept: 'application/json' },
        })
        .then((res) => {
          callback(null, res.data as { auth: string; channel_data?: string })
        })
        .catch((err: unknown) => {
          const msg =
            axios.isAxiosError(err) && err.response?.data && typeof err.response.data === 'object'
              ? JSON.stringify(err.response.data)
              : err instanceof Error
                ? err.message
                : 'Falha na autorização do canal.'
          callback(new Error(msg), null)
        })
    },
  }

  echo = new Echo({
    broadcaster: 'reverb',
    key,
    wsHost: host,
    wsPort: parseInt(port, 10),
    wssPort: scheme === 'https' ? 443 : parseInt(port, 10),
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
    channelAuthorization,
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

  echo
    .private(`dashboard.${userId}`)
    .listen('.metrics.updated', (e: unknown) => {
      clearRecalcFallbackTimer()
      const ev = e as MetricsUpdatedEvent
      if (ev.dashboard) analyticsStore.updateFromWebSocket(ev.dashboard)
      else analyticsStore.setRecalculating(false)
      queryClient?.invalidateQueries({ queryKey: queryKeys.analytics.dashboard() })
    })
    .listen('.metrics.recalculating', () => {
      analyticsStore.setRecalculating(true)
      clearRecalcFallbackTimer()
      recalcFallbackTimer = setTimeout(() => {
        analyticsStore.setRecalculating(false)
        recalcFallbackTimer = null
      }, RECALC_FALLBACK_MS)
    })
    .listen('.session.started', (e: unknown) => {
      const ev = e as SessionStartedEvent
      if (ev.session) {
        const s = ev.session
        const payload: ActiveSessionResponse = {
          id: s.id,
          user_id: userId,
          technology_id: s.technology?.id ?? '',
          technology: s.technology ? { ...s.technology, is_active: true } : undefined,
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

/** Desconecta do Reverb e limpa referências */
export function disconnectWebSocket(): void {
  clearRecalcFallbackTimer()
  if (echo) {
    echo.disconnect()
    echo = null
  }
  isConnected.value = false
}

/**
 * Acesso reativo ao estado da conexão + mesmas funções globais.
 * Não incrementa ref-count; não desconecta ao desmontar o componente.
 */
export function useWebSocket() {
  return {
    connect: connectWebSocket,
    disconnect: disconnectWebSocket,
    isConnected,
  }
}
