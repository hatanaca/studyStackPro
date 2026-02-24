import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { StudySession } from '@/types/domain.types'
import { sessionsApi } from '@/api/modules/sessions.api'
import type { ActiveSessionResponse } from '@/api/modules/sessions.api'
import type { ApiResponse } from '@/types/api.types'

export const useSessionsStore = defineStore('sessions', () => {
  const sessions = ref<StudySession[]>([])
  const activeSession = ref<ActiveSessionResponse | null>(null)
  const elapsedSeconds = ref(0)
  const isLoading = ref(false)
  const total = ref(0)

  const hasActiveSession = computed(() => !!activeSession.value)
  const formattedTimer = computed(() => {
    const s = elapsedSeconds.value
    const h = Math.floor(s / 3600)
    const m = Math.floor((s % 3600) / 60)
    const sec = s % 60
    return [h, m, sec].map((n) => n.toString().padStart(2, '0')).join(':')
  })

  async function fetchSessions(params?: { page?: number; per_page?: number; technology_id?: string }) {
    isLoading.value = true
    try {
      const { data } = await sessionsApi.list(params)
      if (data.success && Array.isArray(data.data)) {
        sessions.value = data.data
      }
      if (data.meta) {
        total.value = (data.meta as { total?: number }).total ?? 0
      }
    } finally {
      isLoading.value = false
    }
  }

  async function fetchActiveSession() {
    const { data } = await sessionsApi.getActive()
    if (data.success && data.data) {
      activeSession.value = data.data
      elapsedSeconds.value = data.data.elapsed_seconds
      return data.data
    }
    activeSession.value = null
    elapsedSeconds.value = 0
    return null
  }

  function setElapsedSeconds(s: number) {
    elapsedSeconds.value = s
  }

  function setActiveSession(session: StudySession | ActiveSessionResponse | null) {
    activeSession.value = session as ActiveSessionResponse | null
  }

  function clearActiveSession() {
    activeSession.value = null
    elapsedSeconds.value = 0
  }

  return {
    sessions,
    activeSession,
    elapsedSeconds,
    isLoading,
    total,
    hasActiveSession,
    formattedTimer,
    fetchSessions,
    fetchActiveSession,
    setElapsedSeconds,
    setActiveSession,
    clearActiveSession
  }
})
