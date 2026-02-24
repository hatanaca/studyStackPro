import { computed } from 'vue'
import { useAnalyticsStore } from '@/stores/analytics.store'

export function useMetrics() {
  const analyticsStore = useAnalyticsStore()

  async function refreshDashboard() {
    await analyticsStore.fetchDashboard()
  }

  return {
    userMetrics: computed(() => analyticsStore.userMetrics),
    technologyMetrics: computed(() => analyticsStore.technologyMetrics),
    timeSeries30d: computed(() => analyticsStore.timeSeries30d),
    isLoading: computed(() => analyticsStore.isLoading),
    refreshDashboard
  }
}
