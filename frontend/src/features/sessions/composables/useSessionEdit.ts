import { ref, type Ref } from 'vue'
import { getApiErrorMessage } from '@/api/client'
import { sessionsApi } from '@/api/modules/sessions.api'
import { useToast } from '@/composables/useToast'
import { useInvalidateSessions } from './useSessionsListQuery'
import type { StudySession } from '@/types/domain.types'

export interface EditFormState {
  title: string
  date: string
  duration: number
  notes: string
}

const DEFAULT_EDIT_FORM: EditFormState = {
  title: '',
  date: '',
  duration: 0,
  notes: '',
}

export function useSessionEdit() {
  const toast = useToast()
  const invalidateSessions = useInvalidateSessions()

  const showEditModal: Ref<boolean> = ref(false)
  const editingSession: Ref<StudySession | null> = ref(null)
  const editForm: Ref<EditFormState> = ref({ ...DEFAULT_EDIT_FORM })
  const editLoading: Ref<boolean> = ref(false)

  function openEdit(session: StudySession) {
    editingSession.value = session
    editForm.value = {
      title: session.title?.trim() ?? '',
      date: session.started_at?.slice(0, 10) ?? '',
      duration: session.duration_min ?? 0,
      notes: session.notes ?? '',
    }
    showEditModal.value = true
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
    editLoading.value = true
    const technologyId = session.technology_id ?? session.technology?.id ?? ''
    if (!technologyId || !form.date || form.duration < 1) {
      toast.error('Preencha todos os campos obrigatórios')
      editLoading.value = false
      return
    }
    try {
      const start = new Date(`${form.date}T12:00:00`)
      const end = new Date(start.getTime() + form.duration * 60_000)
      const toISO = (d: Date) => {
        const y = d.getFullYear()
        const mo = String(d.getMonth() + 1).padStart(2, '0')
        const dd = String(d.getDate()).padStart(2, '0')
        const hh = String(d.getHours()).padStart(2, '0')
        const mi = String(d.getMinutes()).padStart(2, '0')
        const ss = String(d.getSeconds()).padStart(2, '0')
        return `${y}-${mo}-${dd}T${hh}:${mi}:${ss}`
      }
      await sessionsApi.update(session.id, {
        title: form.title.trim() ? form.title.trim() : null,
        technology_id: technologyId,
        started_at: toISO(start),
        ended_at: toISO(end),
        duration_min: form.duration,
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
