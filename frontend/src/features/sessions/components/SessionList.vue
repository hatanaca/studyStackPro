<script setup lang="ts">
import { ref, onMounted } from 'vue'
import SessionCard from './SessionCard.vue'
import SessionFilters from './SessionFilters.vue'
import LogSessionForm from './LogSessionForm.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { getApiErrorMessage } from '@/api/client'
import { sessionsApi } from '@/api/modules/sessions.api'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { useToast } from '@/composables/useToast'
import type { StudySession } from '@/types/domain.types'

const toast = useToast()
const technologiesStore = useTechnologiesStore()

const showAddModal = ref(false)

const filters = ref<{
  technology_id?: string
  date_from?: string
  date_to?: string
  min_duration?: number
  mood?: number
}>({})

const sessions = ref<StudySession[]>([])
const meta = ref<{ current_page: number; last_page: number; per_page: number; total: number } | null>(null)
const loading = ref(false)
const currentPage = ref(1)

async function fetchSessions() {
  loading.value = true
  try {
    const params: Record<string, string | number | undefined> = {
      page: currentPage.value,
      per_page: 15,
    }
    if (filters.value.technology_id) params.technology_id = filters.value.technology_id
    if (filters.value.date_from) params.date_from = filters.value.date_from
    if (filters.value.date_to) params.date_to = filters.value.date_to
    if (filters.value.min_duration != null) params.min_duration = filters.value.min_duration
    if (filters.value.mood != null) params.mood = filters.value.mood

    const { data } = await sessionsApi.list(params as Parameters<typeof sessionsApi.list>[0])
    if (data.success && Array.isArray(data.data)) {
      sessions.value = data.data
      meta.value = (data as { meta?: typeof meta.value }).meta ?? null
    }
  } finally {
    loading.value = false
  }
}

function onFiltersChange() {
  currentPage.value = 1
  fetchSessions()
}

function goToPage(page: number) {
  currentPage.value = page
  fetchSessions()
}

function onSessionCreated() {
  showAddModal.value = false
  fetchSessions()
}

// --- Edit ---
const showEditModal = ref(false)
const editingSession = ref<StudySession | null>(null)
const editForm = ref({ technology_id: '', date: '', duration: 0, notes: '' })
const editLoading = ref(false)

function onEdit(session: StudySession) {
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

async function saveEdit() {
  if (!editingSession.value || editLoading.value) return
  if (!editForm.value.technology_id || !editForm.value.date || editForm.value.duration < 1) {
    toast.error('Preencha todos os campos obrigatórios')
    return
  }
  editLoading.value = true
  try {
    const startedAt = new Date(`${editForm.value.date}T12:00:00`)
    const endedAt = new Date(startedAt.getTime() + editForm.value.duration * 60 * 1000)
    await sessionsApi.update(editingSession.value.id, {
      technology_id: editForm.value.technology_id,
      started_at: startedAt.toISOString(),
      ended_at: endedAt.toISOString(),
      notes: editForm.value.notes.trim() || undefined,
    } as Partial<StudySession>)
    toast.success('Sessão atualizada!')
    showEditModal.value = false
    editingSession.value = null
    fetchSessions()
  } catch (err: unknown) {
    toast.error(getApiErrorMessage(err) || 'Erro ao atualizar sessão')
  } finally {
    editLoading.value = false
  }
}

// --- Delete ---
const showDeleteConfirm = ref(false)
const deletingSession = ref<StudySession | null>(null)
const deleteLoading = ref(false)

function onDelete(session: StudySession) {
  deletingSession.value = session
  showDeleteConfirm.value = true
}

async function confirmDelete() {
  if (!deletingSession.value || deleteLoading.value) return
  deleteLoading.value = true
  try {
    await sessionsApi.delete(deletingSession.value.id)
    toast.success('Sessão excluída!')
    showDeleteConfirm.value = false
    deletingSession.value = null
    fetchSessions()
  } catch (err: unknown) {
    toast.error(getApiErrorMessage(err) || 'Erro ao excluir sessão')
  } finally {
    deleteLoading.value = false
  }
}

function cancelDelete() {
  showDeleteConfirm.value = false
  deletingSession.value = null
}

onMounted(() => fetchSessions())
</script>

<template>
  <div class="session-list">
    <div class="session-list__header">
      <h2>Sessões de estudo</h2>
      <BaseButton
        size="sm"
        @click="showAddModal = true"
      >
        Nova sessão
      </BaseButton>
    </div>

    <SessionFilters
      v-model="filters"
      @change="onFiltersChange"
    />

    <div
      v-if="loading"
      class="loading"
    >
      Carregando...
    </div>
    <div
      v-else-if="sessions.length"
      class="session-list__grid"
    >
      <SessionCard
        v-for="s in sessions"
        :key="s.id"
        :session="s"
        @edit="onEdit"
        @delete="onDelete"
      />
    </div>
    <p
      v-else
      class="empty"
    >
      Nenhuma sessão registrada. Clique em "Nova sessão" para registrar.
    </p>

    <!-- Pagination -->
    <div
      v-if="meta && meta.last_page > 1"
      class="pagination"
    >
      <button
        type="button"
        class="pagination__btn"
        :disabled="currentPage <= 1"
        @click="goToPage(currentPage - 1)"
      >
        ←
      </button>
      <span class="pagination__info">
        Página {{ meta.current_page }} de {{ meta.last_page }}
        ({{ meta.total }} sessões)
      </span>
      <button
        type="button"
        class="pagination__btn"
        :disabled="currentPage >= meta.last_page"
        @click="goToPage(currentPage + 1)"
      >
        →
      </button>
    </div>

    <!-- Add modal -->
    <BaseModal
      :show="showAddModal"
      title="Registrar sessão"
      @close="showAddModal = false"
    >
      <LogSessionForm
        show-cancel
        @success="onSessionCreated"
        @cancel="showAddModal = false"
      />
    </BaseModal>

    <!-- Edit modal -->
    <BaseModal
      :show="showEditModal"
      title="Editar sessão"
      @close="showEditModal = false"
    >
      <form
        class="edit-form"
        @submit.prevent="saveEdit"
      >
        <div class="edit-form__field">
          <label class="edit-form__label">Tecnologia</label>
          <select
            v-model="editForm.technology_id"
            class="edit-form__select"
          >
            <option
              value=""
              disabled
            >
              Selecione...
            </option>
            <option
              v-for="t in technologiesStore.technologies"
              :key="t.id"
              :value="t.id"
            >
              {{ t.name }}
            </option>
          </select>
        </div>

        <div class="edit-form__row">
          <div class="edit-form__field">
            <label class="edit-form__label">Data</label>
            <input
              v-model="editForm.date"
              type="date"
              class="edit-form__input"
            >
          </div>
          <div class="edit-form__field">
            <label class="edit-form__label">Duração (min)</label>
            <input
              v-model.number="editForm.duration"
              type="number"
              min="1"
              max="1440"
              class="edit-form__input"
            >
          </div>
        </div>

        <div class="edit-form__field">
          <label class="edit-form__label">Observações</label>
          <textarea
            v-model="editForm.notes"
            rows="2"
            class="edit-form__textarea"
          />
        </div>

        <div class="edit-form__actions">
          <BaseButton
            type="submit"
            :disabled="editLoading"
          >
            {{ editLoading ? 'Salvando...' : 'Salvar' }}
          </BaseButton>
          <BaseButton
            type="button"
            variant="outline"
            @click="showEditModal = false"
          >
            Cancelar
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Delete confirm modal -->
    <BaseModal
      :show="showDeleteConfirm"
      title="Excluir sessão"
      @close="cancelDelete"
    >
      <div class="delete-confirm">
        <p class="delete-confirm__msg">
          Tem certeza que deseja excluir esta sessão de
          <strong>{{ deletingSession?.technology?.name ?? 'estudo' }}</strong>?
        </p>
        <p class="delete-confirm__hint">
          Esta ação não pode ser desfeita.
        </p>
        <div class="delete-confirm__actions">
          <button
            type="button"
            class="delete-confirm__btn delete-confirm__btn--danger"
            :disabled="deleteLoading"
            @click="confirmDelete"
          >
            {{ deleteLoading ? 'Excluindo...' : 'Excluir' }}
          </button>
          <button
            type="button"
            class="delete-confirm__btn"
            @click="cancelDelete"
          >
            Cancelar
          </button>
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<style scoped>
.session-list__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--page-section-gap);
  flex-wrap: wrap;
  gap: var(--spacing-sm);
  padding: var(--spacing-md) var(--spacing-lg);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}
.session-list h2 {
  font-size: var(--text-lg);
  font-weight: 600;
  margin: 0;
  color: var(--color-text);
  letter-spacing: -0.01em;
}
.session-list__grid {
  display: flex;
  flex-direction: column;
  gap: var(--widget-gap);
}
.loading,
.empty {
  padding: var(--spacing-lg);
  text-align: center;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  background: color-mix(in srgb, var(--color-bg-soft) 50%, var(--color-bg-card));
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
  margin-top: var(--spacing-md);
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: var(--spacing-md);
  margin-top: var(--spacing-lg);
  padding: var(--spacing-md);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}
.pagination__btn {
  min-height: var(--input-height-sm);
  padding: 0.35rem 0.75rem;
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, background var(--duration-fast) ease, color var(--duration-fast) ease;
}
.pagination__btn:hover:not(:disabled) {
  background: var(--color-bg-soft);
  border-color: var(--color-primary);
  color: var(--color-primary);
}
.pagination__btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.pagination__info {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

/* Edit form */
.edit-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
.edit-form__label {
  display: block;
  font-size: var(--text-xs);
  font-weight: 600;
  margin-bottom: var(--spacing-xs);
  color: var(--color-text-muted);
}
.edit-form__field {
  flex: 1;
  min-width: 0;
}
.edit-form__row {
  display: flex;
  gap: var(--spacing-md);
}
.edit-form__select,
.edit-form__input {
  width: 100%;
  min-height: var(--input-height-sm);
  padding: 0.45rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  box-sizing: border-box;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.edit-form__select:focus,
.edit-form__input:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
}
.edit-form__textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  resize: vertical;
  box-sizing: border-box;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.edit-form__textarea:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
}
.edit-form__actions {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}

/* Delete confirm */
.delete-confirm__msg {
  margin: 0 0 var(--spacing-sm);
  color: var(--color-text);
  font-size: var(--text-sm);
  line-height: 1.5;
}
.delete-confirm__hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-md);
}
.delete-confirm__actions {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.delete-confirm__btn {
  min-height: var(--input-height-sm);
  padding: 0.5rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  font-size: var(--text-sm);
  font-weight: 500;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, background var(--duration-fast) ease, color var(--duration-fast) ease;
}
.delete-confirm__btn:hover {
  background: var(--color-bg-soft);
  border-color: var(--color-primary);
  color: var(--color-primary);
}
.delete-confirm__btn--danger {
  background: var(--color-error);
  border-color: var(--color-error);
  color: #fff;
}
.delete-confirm__btn--danger:hover:not(:disabled) {
  background: color-mix(in srgb, var(--color-error) 88%, #000);
  border-color: color-mix(in srgb, var(--color-error) 88%, #000);
}
.delete-confirm__btn--danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 480px) {
  .edit-form__row {
    flex-direction: column;
  }
}
</style>
