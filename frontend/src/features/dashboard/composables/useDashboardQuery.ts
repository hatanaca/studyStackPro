import { watch } from 'vue'
import { useQuerySessionEnabled } from '@/composables/useQueryAuthEnabled'
import { SESSION_NOT_READY } from '@/api/client'
import { useQuery } from '@tanstack/vue-query'
import type { AxiosError } from 'axios'
import { useAnalyticsStore } from '@/stores/analytics.store'
import { analyticsApi } from '@/api/modules/analytics.api'
import { queryKeys } from '@/api/queryKeys'
import { parseDashboardResponse } from '@/types/schemas/api.schemas'
import type { DashboardData } from '@/types/domain.types'

const STALE_TIME_MS = 2 * 60 * 1000 // 2 min
const CACHE_TIME_MS = 15 * 60 * 1000 // 15 min — navegação típica reutiliza cache sem novo parse/rede

/**
 * Query do dashboard com cache e sincronização com a store (sem refetch ao focar a janela).
 * A store continua sendo a fonte de verdade para computed (userMetrics, etc.);
 * o query preenche a store ao buscar e oferece isLoading/error/refetch.
 */
export function useDashboardQuery(options?: { enabled?: boolean }) {
  const analyticsStore = useAnalyticsStore()
  const enabled = useQuerySessionEnabled(
    options?.enabled !== undefined ? () => options.enabled! : undefined
  )

  const query = useQuery({
    queryKey: queryKeys.analytics.dashboard(),
    queryFn: async (): Promise<DashboardData> => {
      const res = await analyticsApi.getDashboard()
      return parseDashboardResponse(res.data) as DashboardData
    },
    staleTime: STALE_TIME_MS,
    gcTime: CACHE_TIME_MS,
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
      if (data) analyticsStore.setDashboard(data)
    },
    { immediate: true }
  )

  return {
    ...query,
    refetch: () => query.refetch(),
  }
}
