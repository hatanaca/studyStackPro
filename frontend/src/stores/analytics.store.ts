import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { DashboardData, UserMetrics, TechnologyMetric, DailyMinute } from '@/types/domain.types'
import { analyticsApi } from '@/api/modules/analytics.api'

export type TimeSeriesPeriod = '7d' | '30d' | '90d'

export interface WeeklySummary {
  week_start: string
  total_minutes: number
  session_count: number
  week_number?: number
  year?: number
  active_days?: number
}

export interface HeatmapDay {
  date: string
  total_minutes: number
}

const PERIOD_TO_DAYS: Record<TimeSeriesPeriod, number> = {
  '7d': 7,
  '30d': 30,
  '90d': 90,
}

export const useAnalyticsStore = defineStore('analytics', () => {
  const dashboard = ref<DashboardData | null>(null)
  const isLoading = ref(false)
  const isRecalculating = ref(false)
  const lastFetchAt = ref<Date | null>(null)

  // Dados separados para widgets (sem fetch próprio nos widgets)
  const heatmapData = ref<HeatmapDay[]>([])
  const heatmapLoading = ref(false)
  const heatmapYear = ref<number>(new Date().getFullYear())

  const weeklyData = ref<WeeklySummary[]>([])
  const weeklyLoading = ref(false)

  const timeSeriesData = ref<Record<TimeSeriesPeriod, DailyMinute[]>>({
    '7d': [],
    '30d': [],
    '90d': [],
  })
  const timeSeriesLoading = ref(false)
  const selectedPeriod = ref<TimeSeriesPeriod>('30d')

  const techStatsData = ref<TechnologyMetric[]>([])
  const techStatsLoading = ref(false)

  const TTL_MS = 5 * 60 * 1000
  const isFresh = computed(
    () =>
      lastFetchAt.value !== null &&
      Date.now() - lastFetchAt.value.getTime() < TTL_MS
  )

  // Computeds granulares (Dashboard.txt)
  const userMetrics = computed((): UserMetrics | null => dashboard.value?.user_metrics ?? null)
  const technologyMetrics = computed(() => dashboard.value?.technology_metrics ?? [])
  const timeSeries = computed(() => timeSeriesData.value[selectedPeriod.value] ?? [])
  const weeklyComparison = computed(() => weeklyData.value)
  const heatmap = computed(() => heatmapData.value)
  const techMetrics = computed(() => techStatsData.value)
  const topTechnologies = computed(() => dashboard.value?.top_technologies ?? [])

  async function fetchDashboard(force = false) {
    if (!force && isFresh.value) return

    isLoading.value = true
    try {
      const res = await analyticsApi.getDashboard()
      if (res.data.success && res.data.data) {
        const data = res.data.data
        dashboard.value = data
        if (data.time_series_30d?.length) {
          timeSeriesData.value = { ...timeSeriesData.value, '30d': data.time_series_30d }
        }
        lastFetchAt.value = new Date()
      }
    } finally {
      isLoading.value = false
    }
  }

  async function fetchHeatmap(year?: number) {
    heatmapLoading.value = true
    try {
      const res = await analyticsApi.getHeatmap(year ?? heatmapYear.value)
      if (res.data.success && Array.isArray(res.data.data)) {
        heatmapData.value = res.data.data as HeatmapDay[]
        if (year) heatmapYear.value = year
      }
    } finally {
      heatmapLoading.value = false
    }
  }

  async function fetchWeekly() {
    weeklyLoading.value = true
    try {
      const res = await analyticsApi.getWeekly()
      if (res.data.success && Array.isArray(res.data.data)) {
        weeklyData.value = res.data.data as WeeklySummary[]
      }
    } finally {
      weeklyLoading.value = false
    }
  }

  async function fetchTimeSeries(period?: TimeSeriesPeriod) {
    const p = period ?? selectedPeriod.value
    const days = PERIOD_TO_DAYS[p]
    timeSeriesLoading.value = true
    try {
      const res = await analyticsApi.getTimeSeries(days)
      const payload = res.data?.data
      if (res.data?.success && Array.isArray(payload)) {
        timeSeriesData.value = { ...timeSeriesData.value, [p]: payload }
      }
    } finally {
      timeSeriesLoading.value = false
    }
  }

  async function fetchTechStats() {
    techStatsLoading.value = true
    try {
      const res = await analyticsApi.getTechStats()
      if (res.data?.success && Array.isArray(res.data.data)) {
        techStatsData.value = res.data.data
      }
    } finally {
      techStatsLoading.value = false
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

  function setSelectedPeriod(period: TimeSeriesPeriod) {
    selectedPeriod.value = period
  }

  return {
    dashboard,
    isLoading,
    isRecalculating,
    lastFetchAt,
    isFresh,
    userMetrics,
    technologyMetrics,
    timeSeries30d: computed(() => timeSeriesData.value['30d'] ?? dashboard.value?.time_series_30d ?? []),
    timeSeries,
    weeklyComparison,
    heatmap,
    heatmapLoading,
    heatmapYear,
    weeklyData,
    weeklyLoading,
    timeSeriesLoading,
    selectedPeriod,
    techMetrics,
    techStatsData,
    techStatsLoading,
    timeSeriesData,
    topTechnologies,
    fetchDashboard,
    fetchHeatmap,
    fetchWeekly,
    fetchTimeSeries,
    fetchTechStats,
    updateFromWebSocket,
    setRecalculating,
    setSelectedPeriod,
  }
})
