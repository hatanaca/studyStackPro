import { computed, onMounted, onUnmounted } from 'vue'
import { useSessionsStore } from '@/stores/sessions.store'

const POLL_INTERVAL_MS = 1000

export function useSessionTimer() {
  const store = useSessionsStore()
  let intervalId: ReturnType<typeof setInterval> | null = null

  function startTicking() {
    stopTicking()
    intervalId = setInterval(() => {
      store.setElapsedSeconds(store.elapsedSeconds + 1)
    }, POLL_INTERVAL_MS)
  }

  function stopTicking() {
    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
  }

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
