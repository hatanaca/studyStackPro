import { computed } from 'vue'
import { useSessionsStore } from '@/stores/sessions.store'
import { sessionsApi } from '@/api/modules/sessions.api'
import type { StudySession } from '@/types/domain.types'

export function useStudySessions() {
  const sessionsStore = useSessionsStore()

  async function loadSessions(params?: { page?: number; per_page?: number; technology_id?: string }) {
    await sessionsStore.fetchSessions(params)
  }

  async function createSession(data: { technology_id: string; started_at: string; ended_at?: string; notes?: string; mood?: number }) {
    const { data: res } = await sessionsApi.create(data)
    if (!res.success || !res.data) return null
    const list = sessionsStore.sessions
    sessionsStore.sessions = [res.data, ...list]
    return res.data
  }

  async function deleteSession(id: string) {
    await sessionsApi.delete(id)
    sessionsStore.sessions = sessionsStore.sessions.filter((s: StudySession) => s.id !== id)
  }

  return {
    sessions: computed(() => sessionsStore.sessions),
    isLoading: computed(() => sessionsStore.isLoading),
    total: computed(() => sessionsStore.total),
    loadSessions,
    createSession,
    deleteSession
  }
}
