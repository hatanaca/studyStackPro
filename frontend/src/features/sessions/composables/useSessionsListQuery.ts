import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import { useQuerySessionEnabled } from '@/composables/useQueryAuthEnabled'
import { useQuery } from '@tanstack/vue-query'
import { useQueryClient } from '@tanstack/vue-query'
import { sessionsApi } from '@/api/modules/sessions.api'
import { queryKeys } from '@/api/queryKeys'
import { parseSessionsListResponse } from '@/types/schemas/api.schemas'
import type { StudySession } from '@/types/domain.types'
import type { PaginationMeta, SessionListFilters } from '@/types/api.types'

export interface SessionsListParams extends SessionListFilters {
  page?: number
  per_page?: number
}

const STALE_MS = 60 * 1000 // 1 min

/**
 * Query da lista de sessões com filtros e paginação.
 * Retorna sessions, meta, isPending, error e refetch.
 */
export function useSessionsListQuery(params: MaybeRefOrGetter<SessionsListParams | undefined>) {
  const queryKey = computed(() =>
    queryKeys.sessions.list(toValue(params) as Record<string, unknown> | undefined)
  )
  const enabled = useQuerySessionEnabled()
  const query = useQuery({
    queryKey,
    queryFn: async () => {
      const p = toValue(params)
      const res = await sessionsApi.list(p)
      const { data, meta } = parseSessionsListResponse(res.data)
      return { data: data as StudySession[], meta: meta as PaginationMeta | undefined }
    },
    staleTime: STALE_MS,
    gcTime: 4 * 60 * 1000,
    refetchOnWindowFocus: false,
    enabled,
  })

  const sessions = computed<StudySession[]>(() => query.data.value?.data ?? [])
  const meta = computed<PaginationMeta | null>(() => query.data.value?.meta ?? null)

  return {
    ...query,
    sessions,
    meta,
  }
}

export function useInvalidateSessions() {
  const queryClient = useQueryClient()
  return () => queryClient.invalidateQueries({ queryKey: queryKeys.sessions.all })
}
