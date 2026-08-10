import { watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import type { AxiosError } from 'axios'
import { useQuerySessionEnabled } from '@/composables/useQueryAuthEnabled'
import { SESSION_NOT_READY } from '@/api/client'
import { analyticsApi } from '@/api/modules/analytics.api'
import { queryKeys } from '@/api/queryKeys'
import { useAnalyticsStore } from '@/stores/analytics.store'
import type { TechnologyMetric } from '@/types/domain.types'

const STALE_TIME_MS = 5 * 60 * 1000

export function useTechStatsQuery() {
  const analyticsStore = useAnalyticsStore()
  const enabled = useQuerySessionEnabled()

  const query = useQuery({
    queryKey: queryKeys.analytics.techStats(),
    queryFn: async (): Promise<TechnologyMetric[]> => {
      const res = await analyticsApi.getTechStats()
      return (res.data?.data ?? []) as TechnologyMetric[]
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
      if (data) analyticsStore.setTechStatsData(data)
    },
    { immediate: true }
  )

  return query
}
