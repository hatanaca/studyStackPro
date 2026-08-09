import { watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import type { AxiosError } from 'axios'
import { useQuerySessionEnabled } from '@/composables/useQueryAuthEnabled'
import { SESSION_NOT_READY } from '@/api/client'
import { analyticsApi } from '@/api/modules/analytics.api'
import { queryKeys } from '@/api/queryKeys'
import { useAnalyticsStore } from '@/stores/analytics.store'
import type { HeatmapDay } from '@/stores/analytics.store'

const STALE_TIME_MS = 5 * 60 * 1000

export function useHeatmapQuery(year?: number) {
  const analyticsStore = useAnalyticsStore()
  const enabled = useQuerySessionEnabled()

  const query = useQuery({
    queryKey: queryKeys.analytics.heatmap(year),
    queryFn: async (): Promise<HeatmapDay[]> => {
      const res = await analyticsApi.getHeatmap(year)
      return (res.data?.data ?? []) as HeatmapDay[]
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
      if (data) analyticsStore.setHeatmapData(data, year)
    },
    { immediate: true }
  )

  return query
}
