import { defineStore } from 'pinia'
import { ref, shallowRef, computed } from 'vue'
import type {
  DashboardData,
  UserMetrics,
  TechnologyMetric,
  DailyMinute,
} from '@/types/domain.types'

/** Periodos disponiveis para graficos de series temporais */
export type TimeSeriesPeriod = '7d' | '30d' | '90d'

export interface WeeklySummary {
  week_start: string
  total_minutes: number
  session_count: number
  score?: number | null
  focus_score?: number | null
  avg_focus_score?: number | null
  study_score?: number | null
  week_number?: number
  year?: number
  active_days?: number
}

/** Entrada do heatmap: data + minutos */
export interface HeatmapDay {
  date: string
  total_minutes: number
}

interface PendingSession {
  date: string
  minutes: number
  technology: { id: string; name: string; color: string } | null
}

/**
 * Store de analytics: estado de UI e computeds que mesclam dados da API com
 * sessoes pendentes (optimistic). O data fetching e gerenciado por TanStack Query
 * composables (useDashboardQuery, useHeatmapQuery, etc.) que escrevem neste store
 * via setters.
 */
export const useAnalyticsStore = defineStore('analytics', () => {
  // --- Data refs (escritas por query composables) ---
  const dashboard = shallowRef<DashboardData | null>(null)
  const heatmapData = shallowRef<HeatmapDay[]>([])
  const heatmapYear = ref<number>(new Date().getFullYear())
  const weeklyData = shallowRef<WeeklySummary[]>([])
  const timeSeriesData = shallowRef<Record<TimeSeriesPeriod, DailyMinute[]>>({
    '7d': [],
    '30d': [],
    '90d': [],
  })
  const techStatsData = shallowRef<TechnologyMetric[]>([])

  // --- UI state ---
  const isRecalculating = ref(false)
  const selectedPeriod = ref<TimeSeriesPeriod>('30d')
  const lastFetchAt = ref<Date | null>(null)

  // --- Pending optimistic sessions ---
  const pendingSessions = ref<PendingSession[]>([])
  const sessionCountAtPendingStart = ref<number | null>(null)

  const todayStr = computed(() => {
    const d = new Date()
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  })

  // --- Helper: merge DailyMinute[] with pending sessions ---
  function mergeDailyWithPending(raw: DailyMinute[]): DailyMinute[] {
    if (!pendingSessions.value.length) return raw
    const merged = raw.map((d) => ({ ...d }))
    for (const ps of pendingSessions.value) {
      const entry = merged.find((d) => d.date === ps.date)
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

  // --- Computeds que mesclam dados da API + pending ---
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
    const merged = base.map((tm) => ({ ...tm }))
    for (const ps of pendingSessions.value) {
      if (!ps.technology) continue
      const existing = merged.find((tm) => tm.technology?.id === ps.technology!.id)
      if (existing) {
        existing.total_minutes += ps.minutes
        existing.session_count += 1
        existing.last_studied_at = new Date().toISOString()
      } else {
        merged.push({
          technology: {
            id: ps.technology.id,
            name: ps.technology.name,
            color: ps.technology.color,
            slug: '',
            is_active: true,
          },
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
        ? {
            ...w,
            total_minutes: w.total_minutes + pendingMins,
            session_count: w.session_count + pendingCount,
          }
        : w
    )
  })

  const heatmap = computed(() => {
    const data = heatmapData.value
    if (!pendingSessions.value.length) return data
    const merged = data.map((d) => ({ ...d }))
    for (const ps of pendingSessions.value) {
      const entry = merged.find((d) => d.date === ps.date)
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
    const entry = (dashboard.value?.time_series_30d ?? []).find((d) => d.date === todayStr.value)
    const apiMinutes = entry?.total_minutes ?? 0
    const pendingMinutes = pendingSessions.value
      .filter((s) => s.date === todayStr.value)
      .reduce((sum, s) => sum + s.minutes, 0)
    return apiMinutes + pendingMinutes
  })

  const todaySessions = computed(() => {
    const entry = (dashboard.value?.time_series_30d ?? []).find((d) => d.date === todayStr.value)
    const apiSessions = entry?.session_count ?? 0
    const pendingCount = pendingSessions.value.filter((s) => s.date === todayStr.value).length
    return apiSessions + pendingCount
  })

  const todayTechnologies = computed(() => {
    const today = todayStr.value
    const apiTechs = (dashboard.value?.technology_metrics ?? []).filter((tm) =>
      tm.last_studied_at?.startsWith(today)
    )

    const seenIds = new Set(apiTechs.map((tm) => tm.technology?.id))
    const extras: TechnologyMetric[] = []

    // Agrega sessões pendentes por tecnologia em uma única passada.
    const byTech = new Map<string, { minutes: number; count: number; tech: PendingSession['technology'] }>()
    for (const ps of pendingSessions.value) {
      if (ps.date !== today || !ps.technology || seenIds.has(ps.technology.id)) continue
      seenIds.add(ps.technology.id)
      const entry = byTech.get(ps.technology.id) ?? { minutes: 0, count: 0, tech: ps.technology }
      entry.minutes += ps.minutes
      entry.count += 1
      byTech.set(ps.technology.id, entry)
    }

    for (const { minutes, count, tech } of byTech.values()) {
      extras.push({
        technology: {
          id: tech!.id,
          name: tech!.name,
          color: tech!.color,
          slug: '',
          is_active: true,
        },
        total_minutes: minutes,
        session_count: count,
        last_studied_at: new Date().toISOString(),
      })
    }

    return [...apiTechs, ...extras]
  })

  // --- Reconcile: limpar pending quando API confirma dados atualizados ---
  function reconcilePending() {
    if (!pendingSessions.value.length || sessionCountAtPendingStart.value === null) return
    const apiTotal = dashboard.value?.user_metrics?.total_sessions ?? 0
    const expected = sessionCountAtPendingStart.value + pendingSessions.value.length
    if (apiTotal >= expected) {
      pendingSessions.value = []
      sessionCountAtPendingStart.value = null
    }
  }

  // --- Optimistic: adiciona ao pending, nao toca nos dados da API ---
  function addLocalTodaySession(
    sessionDate: string,
    minutes: number,
    technology?: { id: string; name: string; color: string }
  ) {
    if (pendingSessions.value.length === 0) {
      sessionCountAtPendingStart.value = dashboard.value?.user_metrics?.total_sessions ?? 0
    }
    pendingSessions.value = [
      ...pendingSessions.value,
      { date: sessionDate, minutes, technology: technology ?? null },
    ]
  }

  // --- Setters (chamados por TanStack Query composables) ---
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

  function setHeatmapData(data: HeatmapDay[], year?: number) {
    heatmapData.value = data
    if (year) heatmapYear.value = year
  }

  function setWeeklyData(data: WeeklySummary[]) {
    weeklyData.value = data
  }

  function setTimeSeriesData(period: TimeSeriesPeriod, data: DailyMinute[]) {
    timeSeriesData.value = { ...timeSeriesData.value, [period]: data }
  }

  function setTechStatsData(data: TechnologyMetric[]) {
    techStatsData.value = data
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
    // Data
    dashboard,
    heatmapData,
    heatmapYear,
    weeklyData,
    timeSeriesData,
    techStatsData,
    // UI state
    isRecalculating,
    selectedPeriod,
    lastFetchAt,
    // Pending
    pendingSessions,
    sessionCountAtPendingStart,
    // Computeds
    userMetrics,
    technologyMetrics,
    timeSeries,
    timeSeries30d: computed(() =>
      mergeDailyWithPending(timeSeriesData.value['30d'] ?? dashboard.value?.time_series_30d ?? [])
    ),
    weeklyComparison,
    heatmap,
    techMetrics,
    topTechnologies,
    todayMinutes,
    todaySessions,
    todayTechnologies,
    // Actions
    addLocalTodaySession,
    setDashboard,
    setHeatmapData,
    setWeeklyData,
    setTimeSeriesData,
    setTechStatsData,
    updateFromWebSocket,
    setRecalculating,
    setSelectedPeriod,
  }
})
