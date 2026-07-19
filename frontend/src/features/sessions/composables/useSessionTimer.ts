import { ref, computed, onUnmounted } from 'vue'
import { sessionsApi } from '@/api/modules/sessions.api'
import type { StudySession } from '@/types/domain.types'

// Singleton compartilhado entre todos os consumidores
let activeSession = ref<StudySession | null>(null)
let elapsedSeconds = ref(0)
let timerInterval: ReturnType<typeof setInterval> | null = null
let consumerCount = 0

function startTimer() {
  if (timerInterval) return
  timerInterval = setInterval(() => {
    if (activeSession.value?.started_at) {
      const start = new Date(activeSession.value.started_at).getTime()
      elapsedSeconds.value = Math.floor((Date.now() - start) / 1000)
    }
  }, 1000)
}

function stopTimer() {
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
}

function formatTime(seconds: number): string {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

export function useSessionTimer() {
  consumerCount++

  async function refresh() {
    try {
      const { data } = await sessionsApi.getActive()
      activeSession.value = data?.data ?? null
      if (activeSession.value) {
        startTimer()
      } else {
        stopTimer()
      }
    } catch {
      activeSession.value = null
      stopTimer()
    }
  }

  onUnmounted(() => {
    consumerCount--
    if (consumerCount <= 0) {
      stopTimer()
      consumerCount = 0
    }
  })

  const formattedTime = computed(() => formatTime(elapsedSeconds.value))

  return { activeSession, elapsedSeconds, formattedTime, refresh }
}
