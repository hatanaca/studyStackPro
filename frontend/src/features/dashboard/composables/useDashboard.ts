import { onMounted, onUnmounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { isConnected } from '@/composables/useWebSocket'

/** Intervalo de polling do dashboard quando WebSocket desconectado (2min) */
const POLLING_INTERVAL_MS = 120_000
const DISCONNECTED_POLLING_DELAY_MS = 5000
const VISIBILITY_COOLDOWN_MS = 10_000

export interface UseDashboardOptions {
  /** Refetch do dashboard (ex.: query.refetch do useDashboardQuery). */
  refetchDashboard?: () => Promise<unknown>
}

/**
 * Composable do dashboard: polling de fallback e refetch ao voltar a aba.
 * Quando WebSocket desconecta, inicia polling.
 */
export function useDashboard(options?: UseDashboardOptions) {
  const authStore = useAuthStore()
  const refetchDashboard = options?.refetchDashboard

  let pollingIntervalId: ReturnType<typeof setInterval> | null = null
  let disconnectTimeoutId: ReturnType<typeof setTimeout> | null = null
  let lastVisibilityFetchAt = 0
  let consecutiveErrors = 0
  let stopWatcher: (() => void) | null = null

  function startPolling() {
    if (pollingIntervalId || !refetchDashboard) return
    pollingIntervalId = setInterval(async () => {
      try {
        await refetchDashboard()
        consecutiveErrors = 0
      } catch {
        consecutiveErrors++
        if (consecutiveErrors >= 3) {
          stopPolling()
        }
      }
    }, POLLING_INTERVAL_MS)
  }

  function stopPolling() {
    if (pollingIntervalId) {
      clearInterval(pollingIntervalId)
      pollingIntervalId = null
    }
  }

  function clearDisconnectTimeout() {
    if (disconnectTimeoutId) {
      clearTimeout(disconnectTimeoutId)
      disconnectTimeoutId = null
    }
  }

  function onWsConnectionChange(connected: boolean) {
    clearDisconnectTimeout()
    if (connected) {
      consecutiveErrors = 0
      stopPolling()
    } else if (authStore.user?.id && consecutiveErrors < 3) {
      disconnectTimeoutId = setTimeout(() => {
        disconnectTimeoutId = null
        if (!isConnected.value) {
          startPolling()
        }
      }, DISCONNECTED_POLLING_DELAY_MS)
    }
  }

  async function handleVisibilityChange() {
    if (document.visibilityState !== 'visible') return
    if (isConnected.value) return
    if (!refetchDashboard) return

    const now = Date.now()
    if (now - lastVisibilityFetchAt < VISIBILITY_COOLDOWN_MS) return
    lastVisibilityFetchAt = now

    try {
      await refetchDashboard()
      consecutiveErrors = 0
    } catch {
      consecutiveErrors++
    }
  }

  onMounted(() => {
    document.addEventListener('visibilitychange', handleVisibilityChange)
    try {
      stopWatcher = watch(isConnected, onWsConnectionChange, { immediate: true })
    } catch {
      startPolling()
    }
  })

  onUnmounted(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    stopPolling()
    clearDisconnectTimeout()
    stopWatcher?.()
    stopWatcher = null
  })

  return {
    refetchDashboard: refetchDashboard ?? (() => Promise.resolve()),
  }
}
