import { ref, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useAnalyticsStore } from '@/stores/analytics.store'

const POLLING_INTERVAL_MS = 120_000
const DISCONNECTED_POLLING_DELAY_MS = 5000
const VISIBILITY_COOLDOWN_MS = 10_000

export interface UseDashboardOptions {
  /** Refetch do dashboard (ex.: query.refetch do useDashboardQuery). Usado no polling e ao voltar à aba. */
  refetchDashboard?: () => Promise<unknown>
}

export function useDashboard(options?: UseDashboardOptions) {
  const authStore = useAuthStore()
  const analyticsStore = useAnalyticsStore()
  const refetchDashboard = options?.refetchDashboard

  const wsIsConnected = ref(false)

  let pollingIntervalId: ReturnType<typeof setInterval> | null = null
  let reconnectCheckId: ReturnType<typeof setInterval> | null = null
  let lastConnectedAt = Date.now()
  let lastVisibilityFetchAt = 0
  let consecutiveErrors = 0

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
      import('@/composables/useWebSocket').then(({ useWebSocket }) => {
        const ws = useWebSocket()
        wsIsConnected.value = ws.isConnected.value

        reconnectCheckId = setInterval(() => {
          wsIsConnected.value = ws.isConnected.value
          if (wsIsConnected.value) {
            lastConnectedAt = Date.now()
            consecutiveErrors = 0
            stopPolling()
          } else if (authStore.user?.id && consecutiveErrors < 3) {
            const disconnectedFor = Date.now() - lastConnectedAt
            if (disconnectedFor > DISCONNECTED_POLLING_DELAY_MS) {
              startPolling()
            }
          }
        }, 5000)
      }).catch(() => {
        startPolling()
      })
    } catch {
      startPolling()
    }
  })

  onUnmounted(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    stopPolling()
    if (reconnectCheckId) {
      clearInterval(reconnectCheckId)
      reconnectCheckId = null
    }
  })

  /**
   * Inicializa dados secundários do dashboard (heatmap, weekly, time series).
   * O dashboard principal é carregado via useDashboardQuery (TanStack Query).
   */
  async function initDashboard() {
    analyticsStore.fetchHeatmap().catch(() => {})
    analyticsStore.fetchWeekly().catch(() => {})

    if (!analyticsStore.timeSeriesData['30d']?.length) {
      analyticsStore.fetchTimeSeries('30d').catch(() => {})
    }
  }

  return {
    fetchDashboard: (force?: boolean) => analyticsStore.fetchDashboard(force),
    initDashboard,
  }
}
