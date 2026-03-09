import { ref, type Ref } from 'vue'
import { getApiErrorMessage } from '@/api/client'
import { sessionsApi } from '@/api/modules/sessions.api'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { useToast } from '@/composables/useToast'
import { useInvalidateSessions } from './useSessionsListQuery'
import type { StudySession } from '@/types/domain.types'

export interface EditFormState {
  technology_id: string
  date: string
  duration: number
  notes: string
}

const DEFAULT_EDIT_FORM: EditFormState = {
  technology_id: '',
  date: '',
  duration: 0,
  notes: '',
}

export function useSessionEdit() {
  const toast = useToast()
  const technologiesStore = useTechnologiesStore()
  const invalidateSessions = useInvalidateSessions()

  const showEditModal: Ref<boolean> = ref(false)
  const editingSession: Ref<StudySession | null> = ref(null)
  const editForm: Ref<EditFormState> = ref({ ...DEFAULT_EDIT_FORM })
  const editLoading: Ref<boolean> = ref(false)

  function openEdit(session: StudySession) {
    editingSession.value = session
    editForm.value = {
      technology_id: session.technology_id ?? session.technology?.id ?? '',
      date: session.started_at?.slice(0, 10) ?? '',
      duration: session.duration_min ?? 0,
      notes: session.notes ?? '',
    }
    showEditModal.value = true
    technologiesStore.fetchTechnologies()
  }

  function closeEdit() {
    showEditModal.value = false
    editingSession.value = null
    editForm.value = { ...DEFAULT_EDIT_FORM }
  }

  async function saveEdit() {
    const session = editingSession.value
    const form = editForm.value
    if (!session || editLoading.value) return
    if (!form.technology_id || !form.date || form.duration < 1) {
      toast.error('Preencha todos os campos obrigatórios')
      return
    }
    editLoading.value = true
    try {
      const startedAt = new Date(`${form.date}T12:00:00`)
      const endedAt = new Date(startedAt.getTime() + form.duration * 60 * 1000)
      await sessionsApi.update(session.id, {
        technology_id: form.technology_id,
        started_at: startedAt.toISOString(),
        ended_at: endedAt.toISOString(),
        notes: form.notes.trim() || undefined,
      } as Partial<StudySession>)
      toast.success('Sessão atualizada!')
      closeEdit()
      await invalidateSessions()
    } catch (err: unknown) {
      toast.error(getApiErrorMessage(err) || 'Erro ao atualizar sessão')
    } finally {
      editLoading.value = false
    }
  }

  return {
    showEditModal,
    editingSession,
    editForm,
    editLoading,
    openEdit,
    closeEdit,
    saveEdit,
  }
}
