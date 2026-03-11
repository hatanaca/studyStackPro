import { computed, onMounted, onUnmounted } from 'vue'
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

  /** Inicia o interval que incrementa elapsedSeconds */
  function startTicking() {
    stopTicking()
    intervalId = setInterval(() => {
      store.setElapsedSeconds(store.elapsedSeconds + 1)
    }, POLL_INTERVAL_MS)
  }

  /** Para o interval do timer */
  function stopTicking() {
    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
  }

  /** Busca sessão ativa na API e inicia/para o timer conforme o resultado */
  async function fetchActive() {
    const session = await store.fetchActiveSession()
    if (session) {
      startTicking()
    } else {
      stopTicking()
    }
  }

  onMounted(() => fetchActive())
  onUnmounted(() => stopTicking())

  return {
    activeSession: computed(() => store.activeSession),
    elapsedSeconds: computed(() => store.elapsedSeconds),
    formattedTime: computed(() => store.formattedTimer),
    fetchActive,
    refresh: fetchActive
  }
}
