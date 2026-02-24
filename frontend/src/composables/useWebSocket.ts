import { ref, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useAnalyticsStore } from '@/stores/analytics.store'
import { useSessionsStore } from '@/stores/sessions.store'
import type { MetricsUpdatedEvent } from '@/types/websocket.types'

export function useWebSocket() {
  const authStore = useAuthStore()
  const analyticsStore = useAnalyticsStore()
  const sessionsStore = useSessionsStore()
  const isConnected = ref(false)
  let echo: { disconnect: () => void; private: (ch: string) => { listen: (ev: string, cb: (e: unknown) => void) => void } } | null = null

  function connect(userId: string) {
    if (typeof window === 'undefined') return
    // Laravel Echo + Pusher: configurar quando dependências estiverem instaladas
    // echo = new Echo({ broadcaster: 'reverb', ... })
    // echo.private(`dashboard.${userId}`)
    //   .listen('.metrics.updated', (e: MetricsUpdatedEvent) => analyticsStore.updateFromWebSocket(e.dashboard))
    //   .listen('.session.started', (e) => sessionsStore.setActiveSession(e.session))
    isConnected.value = true
  }

  function disconnect() {
    echo?.disconnect()
    echo = null
    isConnected.value = false
  }

  onUnmounted(() => {
    disconnect()
  })

  return { connect, disconnect, isConnected }
}
