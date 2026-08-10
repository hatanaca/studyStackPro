import { watch, computed } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import type { AxiosError } from 'axios'
import { useQuerySessionEnabled } from '@/composables/useQueryAuthEnabled'
import { SESSION_NOT_READY } from '@/api/client'
import { analyticsApi } from '@/api/modules/analytics.api'
import { queryKeys } from '@/api/queryKeys'
import { useAnalyticsStore, type TimeSeriesPeriod } from '@/stores/analytics.store'
import type { DailyMinute } from '@/types/domain.types'

const STALE_TIME_MS = 5 * 60 * 1000

const PERIOD_TO_DAYS: Record<TimeSeriesPeriod, number> = {
  '7d': 7,
  '30d': 30,
  '90d': 90,
}

export function useTimeSeriesQuery(period?: TimeSeriesPeriod) {
  const analyticsStore = useAnalyticsStore()
  const enabled = useQuerySessionEnabled()
  const resolvedPeriod = computed(() => period ?? analyticsStore.selectedPeriod)
  const days = computed(() => PERIOD_TO_DAYS[resolvedPeriod.value])

  const query = useQuery({
    queryKey: computed(() => queryKeys.analytics.timeSeries(days.value)),
    queryFn: async (): Promise<DailyMinute[]> => {
      const res = await analyticsApi.getTimeSeries(days.value)
      return (res.data?.data ?? []) as DailyMinute[]
    },
    staleTime: STALE_TIME_MS,
    refetchOnWindowFocus: false,
    retry(failureCount, err) {
      if (err instanceof Error && err.message === SESSION_NOT_READY) return false
      const status = (err as AxiosError)?.response?.status
      if (status === 401 || status === 403) return false
      return failureCount < 2
    },
    enabled,
  })

  watch(
    () => query.data.value,
    (data) => {
      if (data) analyticsStore.setTimeSeriesData(resolvedPeriod.value, data)
    },
    { immediate: true }
  )

  return query
}
