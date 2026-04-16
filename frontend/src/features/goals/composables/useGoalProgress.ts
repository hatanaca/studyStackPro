import { computed } from 'vue'
import type { Goal } from '@/types/goals.types'
import { useAnalyticsStore } from '@/stores/analytics.store'

/**
 * Calcula o valor atual de uma meta com base no período e tipo,
 * usando dados do analytics store (séries temporais, heatmap, etc.).
 */
export function useGoalProgress(goal: {
  type: Goal['type']
  start_date: string
  end_date: string | null
}) {
  const analyticsStore = useAnalyticsStore()

  const currentValue = computed(() => {
    if (goal.type === 'minutes_per_week') {
      const series = analyticsStore.timeSeriesData['7d'] ?? []
      return series.reduce((acc, d) => acc + (d.total_minutes ?? 0), 0)
    }
    if (goal.type === 'sessions_per_week') {
      const series = analyticsStore.timeSeriesData['7d'] ?? []
      return series.reduce((acc, d) => acc + (d.session_count ?? 0), 0)
    }
    if (goal.type === 'streak_days') {
      return analyticsStore.userMetrics?.current_streak_days ?? 0
    }
    return 0
  })

  return { currentValue }
}
