import { watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { useAnalyticsStore } from '@/stores/analytics.store'
import { analyticsApi } from '@/api/modules/analytics.api'
import { queryKeys } from '@/api/queryKeys'
import { parseDashboardResponse } from '@/types/schemas/api.schemas'
import type { DashboardData } from '@/types/domain.types'

const STALE_TIME_MS = 2 * 60 * 1000 // 2 min
const CACHE_TIME_MS = 5 * 60 * 1000 // 5 min

/**
 * Query do dashboard com cache, revalidação em foco e sincronização com a store.
 * A store continua sendo a fonte de verdade para computed (userMetrics, etc.);
 * o query preenche a store ao buscar e oferece isLoading/error/refetch.
 */
export function useDashboardQuery(options?: { enabled?: boolean }) {
  const analyticsStore = useAnalyticsStore()

  const query = useQuery({
    queryKey: queryKeys.analytics.dashboard(),
    queryFn: async (): Promise<DashboardData> => {
      const res = await analyticsApi.getDashboard()
      return parseDashboardResponse(res.data) as DashboardData
    },
    staleTime: STALE_TIME_MS,
    gcTime: CACHE_TIME_MS,
    refetchOnWindowFocus: true,
    retry: 2,
    enabled: options?.enabled ?? true,
  })

  watch(
    () => query.data.value,
    (data) => {
      if (data) analyticsStore.setDashboard(data)
    },
    { immediate: true }
  )

  return {
    ...query,
    refetch: async () => {
      const result = await query.refetch()
      const data = result.data
      if (data) analyticsStore.setDashboard(data)
      return result
    },
  }
}
