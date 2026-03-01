import { onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import { useAnalyticsStore } from '@/stores/analytics.store'
import { useWebSocket } from '@/composables/useWebSocket'

const POLLING_INTERVAL_MS = 120_000
const DISCONNECTED_POLLING_DELAY_MS = 5000

export function useDashboard() {
  const authStore = useAuthStore()
  const analyticsStore = useAnalyticsStore()
  const { isConnected } = useWebSocket()

  let pollingIntervalId: ReturnType<typeof setInterval> | null = null
  let reconnectCheckId: ReturnType<typeof setInterval> | null = null
  let lastConnectedAt = Date.now()

  function startPolling() {
    stopPolling()
    pollingIntervalId = setInterval(() => {
      analyticsStore.fetchDashboard(true)
    }, POLLING_INTERVAL_MS)
  }

  function stopPolling() {
    if (pollingIntervalId) {
      clearInterval(pollingIntervalId)
      pollingIntervalId = null
    }
  }

  function handleVisibilityChange() {
    if (document.visibilityState === 'visible') {
      analyticsStore.fetchDashboard(true)
    }
  }

  onMounted(() => {
    document.addEventListener('visibilitychange', handleVisibilityChange)

    reconnectCheckId = setInterval(() => {
      if (isConnected.value) {
        lastConnectedAt = Date.now()
        stopPolling()
      } else if (authStore.user?.id) {
        const disconnectedFor = Date.now() - lastConnectedAt
        if (disconnectedFor > DISCONNECTED_POLLING_DELAY_MS) {
          startPolling()
        }
      }
    }, 2000)
  })

  onUnmounted(() => {
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    stopPolling()
    if (reconnectCheckId) {
      clearInterval(reconnectCheckId)
      reconnectCheckId = null
    }
  })

  async function initDashboard() {
    await Promise.all([
      analyticsStore.fetchDashboard(),
      analyticsStore.fetchHeatmap(),
      analyticsStore.fetchWeekly(),
    ])
    if (!analyticsStore.timeSeriesData['30d']?.length) {
      await analyticsStore.fetchTimeSeries('30d')
    }
  }

  return {
    fetchDashboard: (force?: boolean) => analyticsStore.fetchDashboard(force),
    initDashboard,
  }
}
