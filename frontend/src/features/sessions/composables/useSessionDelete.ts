import { ref, type Ref } from 'vue'
import { getApiErrorMessage } from '@/api/client'
import { sessionsApi } from '@/api/modules/sessions.api'
import { useToast } from '@/composables/useToast'
import { useInvalidateSessions } from './useSessionsListQuery'
import type { StudySession } from '@/types/domain.types'

export function useSessionDelete() {
  const toast = useToast()
  const invalidateSessions = useInvalidateSessions()

  const showDeleteConfirm: Ref<boolean> = ref(false)
  const deletingSession: Ref<StudySession | null> = ref(null)
  const deleteLoading: Ref<boolean> = ref(false)

  function openDelete(session: StudySession) {
    deletingSession.value = session
    showDeleteConfirm.value = true
  }

  function closeDelete() {
    showDeleteConfirm.value = false
    deletingSession.value = null
  }

  async function confirmDelete() {
    if (!deletingSession.value || deleteLoading.value) return
    deleteLoading.value = true
    const session = deletingSession.value
    try {
      await sessionsApi.delete(session.id)
      toast.success('Sessão excluída!')
      closeDelete()
      await invalidateSessions()
    } catch (err: unknown) {
      toast.error(getApiErrorMessage(err) || 'Erro ao excluir sessão')
    } finally {
      deleteLoading.value = false
    }
  }

  return {
    showDeleteConfirm,
    deletingSession,
    deleteLoading,
    openDelete,
    closeDelete,
    confirmDelete,
  }
}
