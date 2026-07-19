import { ref, shallowRef, computed } from 'vue'
import { analyticsApi } from '@/api/modules/analytics.api'
import type { DashboardData, DailyMinute, TechnologyMetric } from '@/types/domain.types'
import type { DateRange, RadarChartData, FunnelDataPoint, TreemapDataPoint, KpiCard } from '@/types/chart.types'
import type { WeeklySummary } from '@/stores/analytics.store'
import { formatDuration, formatHoursLabel } from '@/utils/formatters'
import { toISODateString } from '@/utils/dateUtils'

function defaultRange(): DateRange {
  const end = new Date()
  const start = new Date()
  start.setDate(start.getDate() - 30)
  return {
    start: toISODateString(start),
    end: toISODateString(end),
  }
}

export function useGraficos() {
  const dateRange = ref<DateRange>(defaultRange())
  const selectedTechIds = ref<string[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const dashboard = shallowRef<DashboardData | null>(null)
  const timeSeriesData = shallowRef<DailyMinute[]>([])
  const weeklyData = shallowRef<WeeklySummary[]>([])
  const techStatsData = shallowRef<TechnologyMetric[]>([])
  const heatmapData = shallowRef<{ date: string; total_minutes: number }[]>([])

  const loadingStates = ref({
    dashboard: false,
    timeSeries: false,
    weekly: false,
    techStats: false,
    heatmap: false,
  })

  const filteredTechStats = computed(() => {
    const data = techStatsData.value
    if (!selectedTechIds.value.length) return data
    return data.filter((t) => t.technology?.id && selectedTechIds.value.includes(t.technology.id))
  })

  const timeSeriesForChart = computed(() => {
    const clean = timeSeriesData.value.filter((d): d is DailyMinute => d != null && typeof d.date === 'string')
    const sorted = [...clean].sort((a, b) => a.date.localeCompare(b.date))
    return {
      labels: sorted.map((d) => {
        const date = new Date(d.date + 'T12:00:00')
        return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })
      }),
      values: sorted.map((d) => d.total_minutes),
    }
  })

  const weeklyForChart = computed(() => {
    const clean = weeklyData.value.filter((w): w is WeeklySummary => w != null && typeof w.week_start === 'string')
    const sorted = [...clean].sort((a, b) => a.week_start.localeCompare(b.week_start))
    return {
      labels: sorted.map((w) => {
        const date = new Date(w.week_start + 'T12:00:00')
        return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })
      }),
      values: sorted.map((w) => w.total_minutes),
      scores: sorted.map((w) => w.score ?? w.focus_score ?? w.avg_focus_score ?? w.study_score ?? null),
    }
  })

  const techDistributionForChart = computed(() => {
    const data = filteredTechStats.value
      .filter((t) => t.total_minutes > 0)
      .sort((a, b) => b.total_minutes - a.total_minutes)
      .slice(0, 10)
    return {
      series: data.map((t) => t.total_minutes),
      labels: data.map((t) => t.technology?.name ?? 'Sem nome'),
      colors: data.map((t) => t.technology?.color ?? 'var(--color-primary)'),
    }
  })

  const radarData = computed((): RadarChartData => {
    const m = dashboard.value?.user_metrics
    if (!m) return { labels: [], series: [] }
    const maxMinutes = Math.max(m.total_minutes ?? 1, 1)
    const maxSessions = Math.max(m.total_sessions ?? 1, 1)
    const maxStreak = Math.max(m.max_streak_days ?? 1, 1)
    return {
      labels: ['Horas totais', 'Sessões', 'Streak atual', 'Melhor streak', 'Média/sessão'],
      series: [{
        name: 'Seu perfil',
        data: [
          Math.round(((m.total_minutes ?? 0) / maxMinutes) * 100),
          Math.round(((m.total_sessions ?? 0) / maxSessions) * 100),
          Math.round(((m.current_streak_days ?? 0) / maxStreak) * 100),
          Math.round(((m.max_streak_days ?? 0) / maxStreak) * 100),
          Math.min(100, Math.round(((m.avg_session_min ?? 0) / 120) * 100)),
        ],
      }],
    }
  })

  const funnelData = computed((): FunnelDataPoint[] => {
    const m = dashboard.value?.user_metrics
    if (!m) return []
    const techCount = techStatsData.value.filter((t) => t.total_minutes > 0).length
    return [
      { label: 'Total de horas', value: m.total_minutes ?? 0 },
      { label: 'Sessões realizadas', value: m.total_sessions ?? 0 },
      { label: 'Dias ativos (streak)', value: m.current_streak_days ?? 0 },
      { label: 'Tecnologias estudadas', value: techCount },
    ]
  })

  const treemapData = computed((): TreemapDataPoint[] => {
    return filteredTechStats.value
      .filter((t) => t.total_minutes > 0)
      .sort((a, b) => b.total_minutes - a.total_minutes)
      .slice(0, 12)
      .map((t) => ({
        x: t.technology?.name ?? 'Sem nome',
        y: t.total_minutes,
        color: t.technology?.color,
      }))
  })

  const kpiData = computed((): KpiCard[] => {
    const m = dashboard.value?.user_metrics
    if (!m) return []
    const dailyMinutes = timeSeriesData.value
    const totalDays = dailyMinutes.length || 1
    const avgDaily = Math.round((m.total_minutes ?? 0) / totalDays)
    const activeDays = dailyMinutes.filter((d) => d.total_minutes > 0).length
    const bestDay = dailyMinutes.reduce((best, d) => d.total_minutes > best.total_minutes ? d : best, { total_minutes: 0, date: '' })

    return [
      {
        label: 'Total de horas',
        value: formatHoursLabel(m.total_hours ?? 0),
        color: 'var(--color-primary)',
        sparkline: dailyMinutes.slice(-14).map((d) => d.total_minutes / 60),
      },
      {
        label: 'Média diária',
        value: formatDuration(avgDaily),
        color: 'var(--color-success)',
        sparkline: dailyMinutes.slice(-14).map((d) => d.total_minutes),
      },
      {
        label: 'Dias ativos',
        value: activeDays,
        color: 'var(--color-info)',
      },
      {
        label: 'Melhor dia',
        value: bestDay.date
          ? new Date(bestDay.date + 'T12:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })
          : '—',
        color: 'var(--color-warning)',
      },
      {
        label: 'Streak atual',
        value: `${m.current_streak_days ?? 0} dias`,
        color: 'var(--color-primary)',
      },
      {
        label: 'Total sessões',
        value: m.total_sessions ?? 0,
        color: 'var(--color-success)',
      },
    ]
  })

  async function fetchDashboard() {
    loadingStates.value.dashboard = true
    try {
      const { data } = await analyticsApi.getDashboard()
      if (data.success && data.data) {
        dashboard.value = data.data
      }
    } catch {
      /* handled by Promise.allSettled */
    } finally {
      loadingStates.value.dashboard = false
    }
  }

  async function fetchTimeSeries(days = 90) {
    loadingStates.value.timeSeries = true
    try {
      const { data } = await analyticsApi.getTimeSeries(days)
      if (data.success && Array.isArray(data.data)) {
        timeSeriesData.value = data.data
      }
    } catch {
      /* handled by Promise.allSettled */
    } finally {
      loadingStates.value.timeSeries = false
    }
  }

  async function fetchWeekly() {
    loadingStates.value.weekly = true
    try {
      const { data } = await analyticsApi.getWeekly()
      if (data.success && Array.isArray(data.data)) {
        weeklyData.value = data.data as WeeklySummary[]
      }
    } catch {
      /* handled by Promise.allSettled */
    } finally {
      loadingStates.value.weekly = false
    }
  }

  async function fetchTechStats() {
    loadingStates.value.techStats = true
    try {
      const { data } = await analyticsApi.getTechStats()
      if (data.success && Array.isArray(data.data)) {
        techStatsData.value = data.data
      }
    } catch {
      /* handled by Promise.allSettled */
    } finally {
      loadingStates.value.techStats = false
    }
  }

  async function fetchHeatmap(year?: number) {
    loadingStates.value.heatmap = true
    try {
      const { data } = await analyticsApi.getHeatmap(year)
      if (data.success && Array.isArray(data.data)) {
        heatmapData.value = data.data
      }
    } catch {
      /* handled by Promise.allSettled */
    } finally {
      loadingStates.value.heatmap = false
    }
  }

  async function fetchAll() {
    isLoading.value = true
    error.value = null
    try {
      await Promise.allSettled([
        fetchDashboard(),
        fetchTimeSeries(90),
        fetchWeekly(),
        fetchTechStats(),
        fetchHeatmap(),
      ])
    } catch {
      error.value = 'Erro ao carregar dados'
    } finally {
      isLoading.value = false
    }
  }

  function setDateRange(range: DateRange) {
    dateRange.value = range
  }

  function toggleTechFilter(techId: string) {
    const idx = selectedTechIds.value.indexOf(techId)
    if (idx >= 0) {
      selectedTechIds.value.splice(idx, 1)
    } else {
      selectedTechIds.value.push(techId)
    }
  }

  return {
    dateRange,
    selectedTechIds,
    isLoading,
    error,
    loadingStates,
    dashboard,
    timeSeriesData,
    weeklyData,
    techStatsData,
    heatmapData,
    filteredTechStats,
    timeSeriesForChart,
    weeklyForChart,
    techDistributionForChart,
    radarData,
    funnelData,
    treemapData,
    kpiData,
    fetchAll,
    fetchDashboard,
    fetchTimeSeries,
    fetchWeekly,
    fetchTechStats,
    fetchHeatmap,
    setDateRange,
    toggleTechFilter,
  }
}
