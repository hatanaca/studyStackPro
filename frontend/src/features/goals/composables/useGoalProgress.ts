import { computed } from 'vue'
import type { Goal } from '@/types/goals.types'
import { useAnalyticsStore } from '@/stores/analytics.store'

export function useGoalProgress(goal: {
  type: Goal['type']
  start_date: string
  end_date: string | null
}) {
  const analyticsStore = useAnalyticsStore()

  const currentValue = computed(() => {
    const startDate = new Date(goal.start_date)
    const endDate = goal.end_date ? new Date(goal.end_date) : new Date()

    if (goal.type === 'minutes_per_week') {
      const series = analyticsStore.timeSeriesData['7d'] ?? []
      return series
        .filter((d) => {
          const date = new Date(d.date)
          return date >= startDate && date <= endDate
        })
        .reduce((acc, d) => acc + (d.total_minutes ?? 0), 0)
    }
    if (goal.type === 'sessions_per_week') {
      const series = analyticsStore.timeSeriesData['7d'] ?? []
      return series
        .filter((d) => {
          const date = new Date(d.date)
          return date >= startDate && date <= endDate
        })
        .reduce((acc, d) => acc + (d.session_count ?? 0), 0)
    }
    if (goal.type === 'streak_days') {
      return analyticsStore.userMetrics?.current_streak_days ?? 0
    }
    return 0
  })

  return { currentValue }
}
