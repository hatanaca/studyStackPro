import { watch } from 'vue'
import { useQuerySessionEnabled } from '@/composables/useQueryAuthEnabled'
import { useQuery } from '@tanstack/vue-query'
import { useQueryClient } from '@tanstack/vue-query'
import { technologiesApi } from '@/api/modules/technologies.api'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { queryKeys } from '@/api/queryKeys'
import { parseTechnologiesListResponse } from '@/types/schemas/api.schemas'
import type { Technology } from '@/types/domain.types'

const STALE_MS = 5 * 60 * 1000 // 5 min — technologies rarely change
/** Mantém o payload pequeno em memória mais tempo ao navegar entre rotas (menos GET duplicados). */
const GC_MS = 30 * 60 * 1000

/**
 * Query da lista de tecnologias com cache e sincronização com a store.
 * Usado em TechnologiesView e SessionsView; deduplica e preenche a store.
 */
export function useTechnologiesQuery(options?: { enabled?: boolean }) {
  const store = useTechnologiesStore()
  const enabled = useQuerySessionEnabled(
    options?.enabled !== undefined ? () => options.enabled! : undefined,
  )

  const query = useQuery({
    queryKey: queryKeys.technologies.list(),
    queryFn: async (): Promise<Technology[]> => {
      const res = await technologiesApi.list()
      return parseTechnologiesListResponse(res.data) as Technology[]
    },
    staleTime: STALE_MS,
    gcTime: GC_MS,
    refetchOnWindowFocus: false,
    enabled,
  })

  watch(
    () => query.data.value,
    (data) => {
      if (Array.isArray(data)) store.setTechnologies(data)
    },
    { immediate: true },
  )

  return query
}

export function useInvalidateTechnologies() {
  const queryClient = useQueryClient()
  return () => queryClient.invalidateQueries({ queryKey: queryKeys.technologies.all })
}
