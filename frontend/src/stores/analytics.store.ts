import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { DashboardData, UserMetrics } from '@/types/domain.types'
import { apiClient } from '@/api/client'
import type { ApiResponse } from '@/types/api.types'

export const useAnalyticsStore = defineStore('analytics', () => {
  const dashboard = ref<DashboardData | null>(null)
  const isLoading = ref(false)
  const isRecalculating = ref(false)
  const lastFetchAt = ref<Date | null>(null)

  const TTL_MS = 5 * 60 * 1000
  const isFresh = computed(
    () =>
      lastFetchAt.value !== null &&
      Date.now() - lastFetchAt.value.getTime() < TTL_MS
  )

  const userMetrics = computed((): UserMetrics | null => dashboard.value?.user_metrics ?? null)
  const technologyMetrics = computed(() => dashboard.value?.technology_metrics ?? [])
  const timeSeries30d = computed(() => dashboard.value?.time_series_30d ?? [])
  const topTechnologies = computed(() => dashboard.value?.top_technologies ?? [])

  async function fetchDashboard(force = false) {
    if (!force && isFresh.value) return

    isLoading.value = true
    try {
      const { data } = await apiClient.get<ApiResponse<DashboardData>>('/analytics/dashboard')
      if (data.success && data.data) {
        dashboard.value = data.data
        lastFetchAt.value = new Date()
      }
    } finally {
      isLoading.value = false
    }
  }

  function updateFromWebSocket(data: DashboardData) {
    dashboard.value = data
    lastFetchAt.value = new Date()
    isRecalculating.value = false
  }

  function setRecalculating(value: boolean) {
    isRecalculating.value = value
  }

  return {
    dashboard,
    isLoading,
    isRecalculating,
    lastFetchAt,
    isFresh,
    userMetrics,
    technologyMetrics,
    timeSeries30d,
    topTechnologies,
    fetchDashboard,
    updateFromWebSocket,
    setRecalculating
  }
})
