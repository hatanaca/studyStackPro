<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { useElementSize } from '@vueuse/core'
import { useVirtualizer } from '@tanstack/vue-virtual'
import type { Virtualizer } from '@tanstack/vue-virtual'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import SessionCard from './SessionCard.vue'
import SessionFilters from './SessionFilters.vue'
import LogSessionForm from './LogSessionForm.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { useSessionsInfiniteQuery, useInvalidateSessionsInfinite } from '@/features/sessions/composables/useSessionsInfiniteQuery'
import { useSessionEdit } from '@/features/sessions/composables/useSessionEdit'
import { useSessionDelete } from '@/features/sessions/composables/useSessionDelete'
import { measureText, getSessionNotesFont, sessionCardNotesMaxWidth } from '@/composables/useTextMeasure'
import type { SessionListFilters } from '@/types/api.types'
import type { StudySession } from '@/types/domain.types'

const props = defineProps<{
  technologyId?: string
}>()

const CARD_FIXED_HEIGHT = 130
const NOTES_MARGIN = 8
const MAX_NOTE_LINES = 3
const CARD_GAP = 8
const SCROLL_THRESHOLD = 5

let _toggleExtraPx: number | null = null
function estimateNotesToggleExtraPx(): number {
  if (_toggleExtraPx !== null) return _toggleExtraPx
  if (typeof document === 'undefined') return 24
  const root = parseFloat(window.getComputedStyle(document.documentElement).fontSize) || 16
  const spacingXs = 0.25 * root
  const textXsLine = 0.75 * root * 1.45
  _toggleExtraPx = spacingXs + textXsLine
  return _toggleExtraPx
}

const heightCache = new Map<string, number>()

const route = useRoute()
const technologiesStore = useTechnologiesStore()
const invalidateSessions = useInvalidateSessionsInfinite()
const sessionEdit = useSessionEdit()
const sessionDelete = useSessionDelete()

const showEditModal = sessionEdit.showEditModal
const showDeleteConfirm = sessionDelete.showDeleteConfirm
const deletingSession = sessionDelete.deletingSession
const editForm = sessionEdit.editForm
const editLoading = sessionEdit.editLoading
const deleteLoading = sessionDelete.deleteLoading

const showAddModal = ref(false)
const filters = ref<SessionListFilters>(
  props.technologyId ? { technology_id: props.technologyId } : {}
)
const scrollContainerRef = ref<HTMLElement | null>(null)
const listRef = ref<HTMLElement | null>(null)
const { width: listOuterWidth } = useElementSize(listRef)

const activeFilters = computed<SessionListFilters>(() => {
  const f: SessionListFilters = {}
  if (filters.value.technology_id) f.technology_id = filters.value.technology_id
  if (filters.value.date_from) f.date_from = filters.value.date_from
  if (filters.value.date_to) f.date_to = filters.value.date_to
  if (filters.value.min_duration != null) f.min_duration = filters.value.min_duration
  if (filters.value.mood != null) f.mood = filters.value.mood
  return f
})

const infiniteQuery = useSessionsInfiniteQuery(activeFilters)
const allSessions = infiniteQuery.allSessions
const totalCount = infiniteQuery.totalCount
const loading = infiniteQuery.isPending
const hasError = infiniteQuery.isError
const isFetchingMore = infiniteQuery.isFetchingNextPage
const hasMore = infiniteQuery.hasNextPage

function estimateCardHeight(session: StudySession, outerW: number): number {
  const widthBucket = Math.round(outerW / 50)
  const cacheKey = `${session.id}:${widthBucket}`
  const cached = heightCache.get(cacheKey)
  if (cached !== undefined) return cached

  let h = CARD_FIXED_HEIGHT
  if (session.notes) {
    const { font, lineHeightPx } = getSessionNotesFont()
    const w = Number.isFinite(outerW) && outerW > 0 ? outerW : 400
    const cardWidth = sessionCardNotesMaxWidth(w)
    const { lineCount } = measureText(session.notes, font, cardWidth, lineHeightPx)
    const visibleLines = Math.min(lineCount, MAX_NOTE_LINES)
    h += NOTES_MARGIN + visibleLines * lineHeightPx
    if (lineCount > MAX_NOTE_LINES) {
      h += estimateNotesToggleExtraPx()
    }
  }

  heightCache.set(cacheKey, h)
  if (heightCache.size > 1000) {
    const first = heightCache.keys().next().value
    if (first !== undefined) heightCache.delete(first)
  }
  return h
}

let fetchDebounceTimer: ReturnType<typeof setTimeout> | null = null

function scheduleFetchNextPageIfNeeded(instance: Virtualizer<HTMLElement, HTMLElement>) {
  const items = instance.getVirtualItems()
  const lastIndex = items.length ? items[items.length - 1].index : -1
  if (fetchDebounceTimer) clearTimeout(fetchDebounceTimer)
  if (
    lastIndex >= 0 &&
    lastIndex >= allSessions.value.length - SCROLL_THRESHOLD &&
    hasMore.value &&
    !isFetchingMore.value
  ) {
    fetchDebounceTimer = setTimeout(() => {
      infiniteQuery.fetchNextPage()
    }, 150)
  }
}

const virtualizer = useVirtualizer<HTMLElement, HTMLElement>(
  computed(() => {
    const outerW = listOuterWidth.value || listRef.value?.clientWidth || 0
    return {
      count: allSessions.value.length,
      getScrollElement: () => scrollContainerRef.value,
      estimateSize: (i: number) => estimateCardHeight(allSessions.value[i], outerW),
      overscan: 5,
      gap: CARD_GAP,
      onChange: (instance) => {
        scheduleFetchNextPageIfNeeded(instance)
      },
    }
  }),
)

function findScrollParent(el: HTMLElement | null): HTMLElement | null {
  let cur = el?.parentElement ?? null
  while (cur) {
    const { overflowY } = window.getComputedStyle(cur)
    if (overflowY === 'auto' || overflowY === 'scroll') return cur
    cur = cur.parentElement
  }
  return document.documentElement
}

function onFiltersChange() {
  // Infinite query resets automatically via queryKey change
}

async function onSessionCreated() {
  showAddModal.value = false
  await invalidateSessions()
}

onMounted(() => {
  scrollContainerRef.value = findScrollParent(listRef.value)

  if (!props.technologyId) {
    const techId = route.query.technology_id
    if (typeof techId === 'string' && techId) {
      filters.value = { ...filters.value, technology_id: techId }
    }
  }
})

onBeforeUnmount(() => {
  if (fetchDebounceTimer) {
    clearTimeout(fetchDebounceTimer)
    fetchDebounceTimer = null
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
      :hide-technology="!!technologyId"
      @change="onFiltersChange"
    />

    <div
      v-if="loading"
      class="loading"
      role="status"
      aria-live="polite"
      aria-label="Carregando sessões"
    >
      <Skeleton class="loading__skeleton" height="6rem" />
      <Skeleton class="loading__skeleton" height="6rem" />
      <Skeleton class="loading__skeleton" height="6rem" />
      <Skeleton class="loading__skeleton" height="6rem" />
    </div>

    <div
      v-else-if="hasError"
      class="session-list__error"
      role="alert"
    >
      <p class="session-list__error-msg">Erro ao carregar sessões. Verifique sua conexão e tente novamente.</p>
      <Button
        label="Tentar novamente"
        severity="secondary"
        size="small"
        @click="infiniteQuery.refetch()"
      />
    </div>

    <div
      v-else-if="allSessions.length"
      ref="listRef"
      class="session-list__virtual"
      :style="{ height: `${virtualizer.getTotalSize()}px`, position: 'relative' }"
    >
      <div
        v-for="row in virtualizer.getVirtualItems()"
        :key="allSessions[row.index].id"
        :ref="(el) => { if (el) virtualizer.measureElement(el as HTMLElement) }"
        :data-index="row.index"
        class="session-list__virtual-item"
        :style="{
          position: 'absolute',
          top: 0,
          left: 0,
          width: '100%',
          transform: `translateY(${row.start}px)`,
        }"
      >
        <SessionCard
          :session="allSessions[row.index]"
          @edit="sessionEdit.openEdit"
          @delete="sessionDelete.openDelete"
        />
      </div>
    </div>

    <EmptyState
      v-else
      icon="📚"
      title="Nenhuma sessão registrada"
      description="Registre sua primeira sessão de estudo para começar a acompanhar seu progresso."
      action-label="Nova sessão"
      :hide-action="false"
      @action="showAddModal = true"
    />

    <!-- Infinite scroll status -->
    <div
      v-if="allSessions.length && (isFetchingMore || hasMore)"
      class="session-list__load-more"
    >
      <Skeleton v-if="isFetchingMore" class="loading__skeleton" height="4rem" />
      <span v-if="!isFetchingMore && hasMore" class="session-list__scroll-hint">
        Continue rolando para carregar mais sessões
      </span>
    </div>
    <div
      v-if="allSessions.length && !hasMore && totalCount > 0"
      class="session-list__footer"
    >
      <span class="session-list__total">
        {{ totalCount }} sessões no total
      </span>
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
        :default-technology-id="technologyId"
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
            :disabled="deleteLoading"
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
  padding: var(--spacing-lg) var(--spacing-xl);
  background: var(--surface-page-header-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--surface-page-header-shadow);
}
.session-list h2 {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  font-weight: 700;
  margin: 0;
  color: var(--color-text);
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.session-list__grid {
  display: flex;
  flex-direction: column;
  gap: var(--widget-gap);
}
.session-list__virtual {
  width: 100%;
}
.session-list__virtual-item {
  will-change: transform;
}
.session-list__load-more {
  padding: var(--spacing-lg) 0;
  text-align: center;
}
.session-list__scroll-hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
.session-list__footer {
  text-align: center;
  padding: var(--spacing-lg);
}
.session-list__total {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
.session-list__error {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-lg);
  padding: var(--spacing-2xl);
  margin-top: var(--spacing-lg);
  background: var(--color-error-soft);
  border: 1px solid color-mix(in srgb, var(--color-error) 30%, transparent);
  border-radius: var(--radius-md);
  text-align: center;
}
.session-list__error-msg {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-on-error-soft, var(--color-error));
  line-height: var(--leading-normal);
}
.loading {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
  padding: var(--spacing-xl);
  margin-top: var(--spacing-lg);
  background: color-mix(in srgb, var(--color-bg-soft) 50%, var(--color-bg-card));
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
}
.loading__skeleton {
  border-radius: var(--radius-md);
}

/* Edit form */
.edit-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}
.edit-form__label {
  display: block;
  font-size: var(--form-label-size);
  font-weight: var(--form-label-weight);
  letter-spacing: var(--form-label-tracking);
  margin-bottom: var(--form-field-gap);
  color: var(--form-label-color);
}
.edit-form__field {
  flex: 1;
  min-width: 0;
}
.edit-form__row {
  display: flex;
  gap: var(--spacing-lg);
}
.edit-form__select,
.edit-form__input {
  width: 100%;
  min-height: var(--form-input-height);
  padding: var(--form-input-padding);
  border: 1px solid var(--form-input-border);
  border-radius: var(--form-input-radius);
  font-size: var(--form-input-font-size);
  box-sizing: border-box;
  background: var(--form-input-bg);
  color: var(--form-input-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.edit-form__select:focus-visible,
.edit-form__input:focus-visible {
  outline: none;
  border-color: var(--form-input-border-focus);
  box-shadow: var(--form-input-shadow-focus);
}
.edit-form__textarea {
  width: 100%;
  padding: var(--form-input-padding);
  border: 1px solid var(--form-input-border);
  border-radius: var(--form-input-radius);
  font-size: var(--form-input-font-size);
  resize: vertical;
  box-sizing: border-box;
  background: var(--form-input-bg);
  color: var(--form-input-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.edit-form__textarea:focus-visible {
  outline: none;
  border-color: var(--form-input-border-focus);
  box-shadow: var(--form-input-shadow-focus);
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
  line-height: var(--leading-normal);
}
.delete-confirm__hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-lg);
}
.delete-confirm__actions {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.delete-confirm__btn {
  min-height: var(--touch-target-min);
  padding: var(--spacing-sm) var(--spacing-lg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  font-size: var(--text-sm);
  font-weight: 500;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, background var(--duration-fast) ease, color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.delete-confirm__btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.delete-confirm__btn--danger:focus-visible {
  outline: none;
  box-shadow: var(--form-input-shadow-error);
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
  opacity: var(--state-disabled-opacity);
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .edit-form__row {
    flex-direction: column;
  }
}
</style>
