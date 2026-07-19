import { computed } from 'vue'
import { useQueryClient } from '@tanstack/vue-query'
import { useSessionsStore } from '@/stores/sessions.store'
import { sessionsApi } from '@/api/modules/sessions.api'
import { queryKeys } from '@/api/queryKeys'

export function useStudySessions() {
  const sessionsStore = useSessionsStore()
  const queryClient = useQueryClient()

  async function loadSessions(params?: {
    page?: number
    per_page?: number
    technology_id?: string
  }) {
    await sessionsStore.fetchSessions(params)
  }

  async function createSession(data: {
    technology_id: string
    title: string
    started_at: string
    ended_at?: string
    notes?: string
    mood?: number
  }) {
    const { data: res } = await sessionsApi.create(data)
    if (!res.success || !res.data) return null
    await queryClient.invalidateQueries({ queryKey: queryKeys.sessions.all })
    return res.data
  }

  async function deleteSession(id: string) {
    await sessionsApi.delete(id)
    await queryClient.invalidateQueries({ queryKey: queryKeys.sessions.all })
  }

  return {
    sessions: computed(() => sessionsStore.sessions),
    isLoading: computed(() => sessionsStore.isLoading),
    total: computed(() => sessionsStore.total),
    loadSessions,
    createSession,
    deleteSession,
  }
}
