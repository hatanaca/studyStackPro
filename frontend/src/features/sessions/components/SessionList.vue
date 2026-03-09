<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import SessionCard from './SessionCard.vue'
import SessionFilters from './SessionFilters.vue'
import LogSessionForm from './LogSessionForm.vue'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { useSessionsListQuery, useInvalidateSessions } from '@/features/sessions/composables/useSessionsListQuery'
import { useSessionEdit } from '@/features/sessions/composables/useSessionEdit'
import { useSessionDelete } from '@/features/sessions/composables/useSessionDelete'
import type { SessionListFilters } from '@/types/api.types'

const route = useRoute()
const technologiesStore = useTechnologiesStore()
const invalidateSessions = useInvalidateSessions()
const sessionEdit = useSessionEdit()
const sessionDelete = useSessionDelete()

const showEditModal = sessionEdit.showEditModal
const showDeleteConfirm = sessionDelete.showDeleteConfirm
const deletingSession = sessionDelete.deletingSession
const editForm = sessionEdit.editForm
const editLoading = sessionEdit.editLoading
const deleteLoading = sessionDelete.deleteLoading

const showAddModal = ref(false)

const filters = ref<SessionListFilters>({})

const currentPage = ref(1)

const listParams = computed(() => ({
  page: currentPage.value,
  per_page: 15,
  ...(filters.value.technology_id && { technology_id: filters.value.technology_id }),
  ...(filters.value.date_from && { date_from: filters.value.date_from }),
  ...(filters.value.date_to && { date_to: filters.value.date_to }),
  ...(filters.value.min_duration != null && { min_duration: filters.value.min_duration }),
  ...(filters.value.mood != null && { mood: filters.value.mood }),
}))

const sessionsListQuery = useSessionsListQuery(listParams)
const sessions = sessionsListQuery.sessions
const meta = sessionsListQuery.meta
const loading = sessionsListQuery.isPending

function onFiltersChange() {
  currentPage.value = 1
}

function goToPage(page: number) {
  currentPage.value = page
}

async function onSessionCreated() {
  showAddModal.value = false
  await invalidateSessions()
}

onMounted(() => {
  const techId = route.query.technology_id
  if (typeof techId === 'string' && techId) {
    filters.value = { ...filters.value, technology_id: techId }
  }
})
</script>

<template>
  <div class="session-list">
    <div class="session-list__header">
      <h2>Sessões de estudo</h2>
      <Button
        label="Nova sessão"
        size="small"
        @click="showAddModal = true"
      />
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
        @edit="sessionEdit.openEdit"
        @delete="sessionDelete.openDelete"
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
    <Dialog
      v-model:visible="showAddModal"
      header="Registrar sessão"
      modal
      :style="{ width: 'min(90vw, 420px)' }"
      @hide="showAddModal = false"
    >
      <LogSessionForm
        show-cancel
        @success="onSessionCreated"
        @cancel="showAddModal = false"
      />
    </Dialog>

    <!-- Edit modal -->
    <Dialog
      v-model:visible="showEditModal"
      header="Editar sessão"
      modal
      :style="{ width: 'min(90vw, 420px)' }"
      @hide="sessionEdit.closeEdit"
    >
      <form
        class="edit-form"
        @submit.prevent="sessionEdit.saveEdit"
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
          <Button
            type="submit"
            :label="editLoading ? 'Salvando...' : 'Salvar'"
            :loading="editLoading"
          />
          <Button
            label="Cancelar"
            severity="secondary"
            variant="outlined"
            @click="sessionEdit.closeEdit"
          />
        </div>
      </form>
    </Dialog>

    <!-- Delete confirm modal -->
    <Dialog
      v-model:visible="showDeleteConfirm"
      header="Excluir sessão"
      modal
      :style="{ width: 'min(90vw, 400px)' }"
      @hide="sessionDelete.closeDelete"
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
            :loading="deleteLoading"
            @click="sessionDelete.confirmDelete"
          >
            {{ deleteLoading ? 'Excluindo...' : 'Excluir' }}
          </button>
          <button
            type="button"
            class="delete-confirm__btn"
            @click="sessionDelete.closeDelete"
          >
            Cancelar
          </button>
        </div>
      </div>
    </Dialog>
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
  color: var(--color-primary-contrast);
}
.delete-confirm__btn--danger:hover:not(:disabled) {
  background: color-mix(in srgb, var(--color-error) 88%, var(--color-bg));
  border-color: color-mix(in srgb, var(--color-error) 88%, var(--color-bg));
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
