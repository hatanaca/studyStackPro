import { computed, onMounted, onUnmounted, watch } from 'vue'
import { useSessionsStore } from '@/stores/sessions.store'

/** Intervalo do timer em ms (atualização a cada segundo) */
const POLL_INTERVAL_MS = 1000

/**
 * Composables do timer de sessão ativa.
 * Busca sessão ativa no mount, incrementa elapsedSeconds a cada segundo.
 * Retorna activeSession, elapsedSeconds, formattedTime, fetchActive.
 */
export function useSessionTimer() {
  const store = useSessionsStore()
  let intervalId: ReturnType<typeof setInterval> | null = null
  let baseTimestamp: number | null = null
  let baseElapsed = 0

  function startTicking() {
    stopTicking()
    baseTimestamp = Date.now()
    baseElapsed = store.elapsedSeconds
    intervalId = setInterval(() => {
      if (baseTimestamp !== null) {
        const drift = Math.floor((Date.now() - baseTimestamp) / 1000)
        store.setElapsedSeconds(baseElapsed + drift)
      }
    }, POLL_INTERVAL_MS)
  }

  function stopTicking() {
    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
    baseTimestamp = null
  }

  async function fetchActive() {
    try {
      const session = await store.fetchActiveSession()
      if (session) {
        startTicking()
      } else {
        stopTicking()
      }
    } catch {
      stopTicking()
    }
  }

  // Reage a mudanças da sessão ativa originadas por WebSocket (setActiveSession/clearActiveSession).
  // onMounted só cobre o carregamento inicial; este watch cobre eventos assíncronos posteriores.
  watch(
    () => store.activeSession,
    (session, prevSession) => {
      if (session && !prevSession) {
        startTicking()
      } else if (!session && prevSession) {
        stopTicking()
      }
    },
  )

  onMounted(() => {
    void fetchActive()
  })
  onUnmounted(() => stopTicking())

  return {
    activeSession: computed(() => store.activeSession),
    elapsedSeconds: computed(() => store.elapsedSeconds),
    formattedTime: computed(() => store.formattedTimer),
    fetchActive,
    refresh: fetchActive
  }
}
