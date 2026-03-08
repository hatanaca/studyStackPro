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

interface PendingSession {
  date: string
  minutes: number
  technology: { id: string; name: string; color: string } | null
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

  // --- Pending optimistic sessions (never overwritten by API) ---
  const pendingSessions = ref<PendingSession[]>([])
  const sessionCountAtPendingStart = ref<number | null>(null)

  const TTL_MS = 5 * 60 * 1000
  const isFresh = computed(
    () =>
      lastFetchAt.value !== null &&
      Date.now() - lastFetchAt.value.getTime() < TTL_MS
  )

  const todayStr = computed(() => {
    const d = new Date()
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  })

  // --- Helper: merge DailyMinute[] with pending sessions ---
  function mergeDailyWithPending(raw: DailyMinute[]): DailyMinute[] {
    if (!pendingSessions.value.length) return raw
    const merged = raw.map(d => ({ ...d }))
    for (const ps of pendingSessions.value) {
      const entry = merged.find(d => d.date === ps.date)
      if (entry) {
        entry.total_minutes += ps.minutes
        entry.session_count = (entry.session_count ?? 0) + 1
      } else {
        merged.push({ date: ps.date, total_minutes: ps.minutes, session_count: 1 })
      }
    }
    merged.sort((a, b) => a.date.localeCompare(b.date))
    return merged
  }

  // --- Computeds that merge API data + pending ---
  const userMetrics = computed((): UserMetrics | null => {
    const base = dashboard.value?.user_metrics
    if (!base) return null
    if (!pendingSessions.value.length) return base
    const pendingMins = pendingSessions.value.reduce((s, p) => s + p.minutes, 0)
    const pendingCount = pendingSessions.value.length
    return {
      ...base,
      total_sessions: base.total_sessions + pendingCount,
      total_minutes: base.total_minutes + pendingMins,
      total_hours: Math.round(((base.total_minutes + pendingMins) / 60) * 100) / 100,
    }
  })

  const technologyMetrics = computed(() => {
    const base = dashboard.value?.technology_metrics ?? []
    if (!pendingSessions.value.length) return base
    const merged = base.map(tm => ({ ...tm }))
    for (const ps of pendingSessions.value) {
      if (!ps.technology) continue
      const existing = merged.find(tm => tm.technology?.id === ps.technology!.id)
      if (existing) {
        existing.total_minutes += ps.minutes
        existing.session_count += 1
        existing.last_studied_at = new Date().toISOString()
      } else {
        merged.push({
          technology: { id: ps.technology.id, name: ps.technology.name, color: ps.technology.color, slug: '', is_active: true },
          total_minutes: ps.minutes,
          session_count: 1,
          last_studied_at: new Date().toISOString(),
        })
      }
    }
    return merged
  })

  const timeSeries = computed(() =>
    mergeDailyWithPending(timeSeriesData.value[selectedPeriod.value] ?? [])
  )

  const weeklyComparison = computed(() => {
    const data = weeklyData.value
    if (!pendingSessions.value.length || !data.length) return data
    const pendingMins = pendingSessions.value.reduce((s, p) => s + p.minutes, 0)
    const pendingCount = pendingSessions.value.length
    return data.map((w, i) =>
      i === 0
        ? { ...w, total_minutes: w.total_minutes + pendingMins, session_count: w.session_count + pendingCount }
        : w
    )
  })

  const heatmap = computed(() => {
    const data = heatmapData.value
    if (!pendingSessions.value.length) return data
    const merged = data.map(d => ({ ...d }))
    for (const ps of pendingSessions.value) {
      const entry = merged.find(d => d.date === ps.date)
      if (entry) {
        entry.total_minutes += ps.minutes
      } else {
        merged.push({ date: ps.date, total_minutes: ps.minutes })
      }
    }
    return merged
  })

  const techMetrics = computed(() => techStatsData.value)
  const topTechnologies = computed(() => dashboard.value?.top_technologies ?? [])

  const todayMinutes = computed(() => {
    const entry = (dashboard.value?.time_series_30d ?? []).find(d => d.date === todayStr.value)
    const apiMinutes = entry?.total_minutes ?? 0
    const pendingMinutes = pendingSessions.value
      .filter(s => s.date === todayStr.value)
      .reduce((sum, s) => sum + s.minutes, 0)
    return apiMinutes + pendingMinutes
  })

  const todaySessions = computed(() => {
    const entry = (dashboard.value?.time_series_30d ?? []).find(d => d.date === todayStr.value)
    const apiSessions = entry?.session_count ?? 0
    const pendingCount = pendingSessions.value.filter(s => s.date === todayStr.value).length
    return apiSessions + pendingCount
  })

  const todayTechnologies = computed(() => {
    const today = todayStr.value
    const apiTechs = (dashboard.value?.technology_metrics ?? []).filter(tm =>
      tm.last_studied_at?.startsWith(today)
    )

    const seenIds = new Set(apiTechs.map(tm => tm.technology?.id))
    const extras: TechnologyMetric[] = []

    for (const ps of pendingSessions.value) {
      if (ps.date !== today || !ps.technology || seenIds.has(ps.technology.id)) continue
      seenIds.add(ps.technology.id)
      const pendingMins = pendingSessions.value
        .filter(s => s.technology?.id === ps.technology!.id)
        .reduce((sum, s) => sum + s.minutes, 0)
      extras.push({
        technology: { id: ps.technology.id, name: ps.technology.name, color: ps.technology.color, slug: '', is_active: true },
        total_minutes: pendingMins,
        session_count: pendingSessions.value.filter(s => s.technology?.id === ps.technology!.id).length,
        last_studied_at: new Date().toISOString(),
      })
    }

    return [...apiTechs, ...extras]
  })

  // --- Reconcile: clear pending when API confirms data is up to date ---
  function reconcilePending() {
    if (!pendingSessions.value.length || sessionCountAtPendingStart.value === null) return
    const apiTotal = dashboard.value?.user_metrics.total_sessions ?? 0
    const expected = sessionCountAtPendingStart.value + pendingSessions.value.length
    if (apiTotal >= expected) {
      pendingSessions.value = []
      sessionCountAtPendingStart.value = null
    }
  }

  // --- Optimistic add: just pushes to pending, never touches API data ---
  function addLocalTodaySession(
    sessionDate: string,
    minutes: number,
    technology?: { id: string; name: string; color: string }
  ) {
    if (pendingSessions.value.length === 0) {
      sessionCountAtPendingStart.value = dashboard.value?.user_metrics.total_sessions ?? 0
    }
    pendingSessions.value = [
      ...pendingSessions.value,
      { date: sessionDate, minutes, technology: technology ?? null },
    ]
  }

  // --- Fetch functions ---
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
        reconcilePending()
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

  /** Atualiza o dashboard a partir de dados (ex.: TanStack Query). */
  function setDashboard(data: DashboardData | null) {
    dashboard.value = data
    if (data) {
      lastFetchAt.value = new Date()
      if (data.time_series_30d?.length) {
        timeSeriesData.value = { ...timeSeriesData.value, '30d': data.time_series_30d }
      }
      reconcilePending()
    }
  }

  function updateFromWebSocket(data: DashboardData) {
    dashboard.value = data
    lastFetchAt.value = new Date()
    isRecalculating.value = false
    pendingSessions.value = []
    sessionCountAtPendingStart.value = null
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
    timeSeries30d: computed(() => mergeDailyWithPending(timeSeriesData.value['30d'] ?? dashboard.value?.time_series_30d ?? [])),
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
    todayMinutes,
    todaySessions,
    todayTechnologies,
    addLocalTodaySession,
    setDashboard,
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
