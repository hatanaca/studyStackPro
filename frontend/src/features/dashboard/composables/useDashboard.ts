import { onMounted, onUnmounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useAnalyticsStore } from '@/stores/analytics.store'
import { isConnected } from '@/composables/useWebSocket'

/** Intervalo de polling do dashboard quando WebSocket desconectado (2min) */
const POLLING_INTERVAL_MS = 120_000
const DISCONNECTED_POLLING_DELAY_MS = 5000
const VISIBILITY_COOLDOWN_MS = 10_000

export interface UseDashboardOptions {
  /** Refetch do dashboard (ex.: query.refetch do useDashboardQuery). Usado no polling e ao voltar à aba. */
  refetchDashboard?: () => Promise<unknown>
}

/**
 * Composable do dashboard: polling de fallback, refetch ao voltar à aba, init (heatmap/weekly/timeSeries).
 * Quando WebSocket desconecta, inicia polling. initDashboard chama fetchDashboard e carrega widgets extras.
 */
export function useDashboard(options?: UseDashboardOptions) {
  const authStore = useAuthStore()
  const analyticsStore = useAnalyticsStore()
  const refetchDashboard = options?.refetchDashboard

  let pollingIntervalId: ReturnType<typeof setInterval> | null = null
  let disconnectTimeoutId: ReturnType<typeof setTimeout> | null = null
  let lastVisibilityFetchAt = 0
  let consecutiveErrors = 0
  let stopWatcher: (() => void) | null = null

  function getFetchFn() {
    return refetchDashboard ?? (() => analyticsStore.fetchDashboard(true))
  }

  function startPolling() {
    if (pollingIntervalId) return
    const doFetch = getFetchFn()
    pollingIntervalId = setInterval(async () => {
      try {
        await doFetch()
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

    const now = Date.now()
    if (now - lastVisibilityFetchAt < VISIBILITY_COOLDOWN_MS) return
    lastVisibilityFetchAt = now

    try {
      await getFetchFn()()
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

  /**
   * Inicializa dados secundários do dashboard (heatmap, weekly, time series).
   * O dashboard principal é carregado via useDashboardQuery (TanStack Query).
   */
  async function initDashboard() {
    await Promise.all([
      analyticsStore.fetchHeatmap().catch(() => {}),
      analyticsStore.fetchWeekly().catch(() => {}),
    ])

    // 30d: widgets padrão; 7d: progresso de metas (useGoalProgress); 90d: sob demanda no TimeSeriesWidget
    const series: Promise<unknown>[] = []
    if (!analyticsStore.timeSeriesData['30d']?.length) {
      series.push(analyticsStore.fetchTimeSeries('30d').catch(() => {}))
    }
    if (!analyticsStore.timeSeriesData['7d']?.length) {
      series.push(analyticsStore.fetchTimeSeries('7d').catch(() => {}))
    }
    if (series.length) await Promise.all(series)
  }

  return {
    fetchDashboard: (force?: boolean) => analyticsStore.fetchDashboard(force),
    initDashboard,
  }
}
