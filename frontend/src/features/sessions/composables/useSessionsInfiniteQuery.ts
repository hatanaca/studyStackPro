import { computed, shallowRef, watch, type MaybeRefOrGetter, toValue } from 'vue'
import { useInfiniteQuery, useQueryClient } from '@tanstack/vue-query'
import { useQuerySessionEnabled } from '@/composables/useQueryAuthEnabled'
import { sessionsApi } from '@/api/modules/sessions.api'
import { queryKeys } from '@/api/queryKeys'
import { parseSessionsListResponse } from '@/types/schemas/api.schemas'
import type { StudySession } from '@/types/domain.types'
import type { SessionListFilters } from '@/types/api.types'

const PER_PAGE = 30

export function useSessionsInfiniteQuery(filters: MaybeRefOrGetter<SessionListFilters>) {
  const queryKey = computed(() => queryKeys.sessions.list({ ...toValue(filters), _infinite: true }))
  const enabled = useQuerySessionEnabled()

  const query = useInfiniteQuery({
    queryKey,
    enabled,
    staleTime: 2 * 60 * 1000,
    /** Listas grandes: libertar páginas da memória mais cedo após sair da vista. */
    gcTime: 3 * 60 * 1000,
    refetchOnWindowFocus: false,
    queryFn: async ({ pageParam }) => {
      const f = toValue(filters)
      const res = await sessionsApi.list({ ...f, page: pageParam, per_page: PER_PAGE })
      return parseSessionsListResponse(res.data)
    },
    getNextPageParam: (lastPage, allPages) => {
      const meta = lastPage.meta
      if (!meta || meta.last_page < 1) return undefined
      if (meta.current_page >= meta.last_page) return undefined
      const rows = lastPage.data ?? []
      if (rows.length === 0) return undefined
      /* Se a API repetir os mesmos IDs da página anterior, não pedir mais páginas (evita lista infinita). */
      if (allPages && allPages.length > 1) {
        const seen = new Set(allPages.slice(0, -1).flatMap((p) => (p.data ?? []).map((s) => s.id)))
        const newCount = rows.filter((s) => !seen.has(s.id)).length
        if (newCount === 0) return undefined
      }
      return meta.current_page + 1
    },
    initialPageParam: 1,
  })

  /** Lista achatada em shallowRef: evita recomputar flatMap em cada tick quando só outras partes do dashboard re-renderizam. */
  const allSessions = shallowRef<StudySession[]>([])

  watch(
    () => query.data.value?.pages,
    (pages) => {
      if (!pages?.length) {
        allSessions.value = []
        return
      }
      const flat = pages.flatMap((p) => (p.data ?? []) as StudySession[])
      const seen = new Set<string>()
      allSessions.value = flat.filter((s) => {
        if (seen.has(s.id)) return false
        seen.add(s.id)
        return true
      })
    },
    { immediate: true }
  )

  const totalCount = computed(() => {
    const pages = query.data.value?.pages
    if (!pages?.length) return 0
    return pages[pages.length - 1].meta?.total ?? allSessions.value.length
  })

  return {
    ...query,
    allSessions,
    totalCount,
  }
}

export function useInvalidateSessionsInfinite() {
  const qc = useQueryClient()
  return () => qc.invalidateQueries({ queryKey: queryKeys.sessions.all })
}
