import { defineStore } from 'pinia'
import { ref, shallowRef, computed } from 'vue'
import type { StudySession } from '@/types/domain.types'
import { sessionsApi } from '@/api/modules/sessions.api'
import type { ActiveSessionResponse } from '@/api/modules/sessions.api'

/** Store de sessões: lista, sessão ativa, timer (elapsedSeconds, formattedTimer). */
export const useSessionsStore = defineStore('sessions', () => {
  const sessions = shallowRef<StudySession[]>([])
  const activeSession = shallowRef<ActiveSessionResponse | null>(null)
  const elapsedSeconds = ref(0)
  const isLoading = ref(false)
  const total = ref(0)

  /** True se há sessão ativa */
  const hasActiveSession = computed(() => !!activeSession.value)
  /** Timer formatado HH:MM:SS */
  const formattedTimer = computed(() => {
    const s = elapsedSeconds.value
    const h = Math.floor(s / 3600)
    const m = Math.floor((s % 3600) / 60)
    const sec = s % 60
    return [h, m, sec].map((n) => n.toString().padStart(2, '0')).join(':')
  })

  async function fetchSessions(params?: {
    page?: number
    per_page?: number
    technology_id?: string
  }) {
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

  /** Busca sessão ativa (timer). Retorna null se não houver ou em caso de erro. */
  async function fetchActiveSession() {
    try {
      const { data } = await sessionsApi.getActive()
      if (data.success && data.data) {
        activeSession.value = data.data
        elapsedSeconds.value = data.data.elapsed_seconds
        return data.data
      }
      activeSession.value = null
      elapsedSeconds.value = 0
      return null
    } catch {
      activeSession.value = null
      elapsedSeconds.value = 0
      return null
    }
  }

  /** Atualiza segundos decorridos (usado pelo timer) */
  function setElapsedSeconds(s: number) {
    elapsedSeconds.value = s
  }

  /** Define sessão ativa (ex.: via WebSocket). Sincroniza elapsedSeconds quando disponível. */
  function setActiveSession(session: StudySession | ActiveSessionResponse | null) {
    activeSession.value = session as ActiveSessionResponse | null
    if (session && 'elapsed_seconds' in session) {
      elapsedSeconds.value = (session as ActiveSessionResponse).elapsed_seconds
    }
  }

  /** Limpa sessão ativa (ex.: ao encerrar via WebSocket) */
  function clearActiveSession() {
    activeSession.value = null
    elapsedSeconds.value = 0
  }

  function $reset() {
    sessions.value = []
    activeSession.value = null
    elapsedSeconds.value = 0
    isLoading.value = false
    total.value = 0
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
    clearActiveSession,
    $reset,
  }
})
