import { watch } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { useQueryClient } from '@tanstack/vue-query'
import { technologiesApi } from '@/api/modules/technologies.api'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { queryKeys } from '@/api/queryKeys'
import { parseTechnologiesListResponse } from '@/types/schemas/api.schemas'
import type { Technology } from '@/types/domain.types'

const STALE_MS = 60 * 1000 // 1 min

/**
 * Query da lista de tecnologias com cache e sincronização com a store.
 * Usado em TechnologiesView e SessionsView; deduplica e preenche a store.
 */
export function useTechnologiesQuery(options?: { enabled?: boolean }) {
  const store = useTechnologiesStore()

  const query = useQuery({
    queryKey: queryKeys.technologies.list(),
    queryFn: async (): Promise<Technology[]> => {
      const res = await technologiesApi.list()
      return parseTechnologiesListResponse(res.data) as Technology[]
    },
    staleTime: STALE_MS,
    enabled: options?.enabled ?? true,
  })

  watch(
    () => query.data.value,
    (data) => {
      if (data?.length !== undefined) store.setTechnologies(data)
    },
    { immediate: true }
  )

  return query
}

export function useInvalidateTechnologies() {
  const queryClient = useQueryClient()
  return () => queryClient.invalidateQueries({ queryKey: queryKeys.technologies.all })
}
