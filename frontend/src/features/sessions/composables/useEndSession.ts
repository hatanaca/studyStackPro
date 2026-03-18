import { ref } from 'vue'
import { getApiErrorMessage } from '@/api/client'
import { sessionsApi } from '@/api/modules/sessions.api'
import { useSessionTimer } from '@/features/sessions/composables/useSessionTimer'
import { useToast } from '@/composables/useToast'

/**
 * Composable para finalizar sessão ativa.
 * Reutilizado por ActiveSessionBanner e SessionFocusView.
 */
export function useEndSession() {
  const { activeSession, formattedTime, refresh } = useSessionTimer()
  const toast = useToast()
  const ending = ref(false)

  async function endSession(): Promise<boolean> {
    if (!activeSession.value || ending.value) return false
    ending.value = true
    try {
      await sessionsApi.end(activeSession.value.id)
      await refresh()
      return true
    } catch (err: unknown) {
      toast.error(getApiErrorMessage(err) || 'Erro ao finalizar sessão.')
      return false
    } finally {
      ending.value = false
    }
  }

  return { activeSession, formattedTime, ending, endSession, refresh }
}
