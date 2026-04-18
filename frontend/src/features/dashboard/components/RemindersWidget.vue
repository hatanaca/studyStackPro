<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import EmptyState from '@/components/ui/EmptyState.vue'

interface Reminder {
  id: string
  text: string
}

const STORAGE_KEY = 'studytrack.reminders'
const MAX_REMINDERS = 25

const reminders = ref<Reminder[]>([])
const newReminder = ref('')
const editingId = ref<string | null>(null)
const editingText = ref('')
const showDialog = ref(false)

const atLimit = computed(() => reminders.value.length >= MAX_REMINDERS)
const remainingCount = computed(() => Math.max(0, MAX_REMINDERS - reminders.value.length))

function loadFromStorage() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return
    const parsed = JSON.parse(raw) as Reminder[]
    if (Array.isArray(parsed)) {
      reminders.value = parsed
    }
  } catch {
    /* ignore */
  }
}

function saveToStorage() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(reminders.value))
}

function addReminder() {
  const text = newReminder.value.trim()
  if (!text || reminders.value.length >= MAX_REMINDERS) return
  reminders.value = [...reminders.value, { id: crypto.randomUUID?.() ?? String(Date.now()), text }]
  newReminder.value = ''
  saveToStorage()
}

function startEdit(reminder: Reminder) {
  editingId.value = reminder.id
  editingText.value = reminder.text
}

function saveEdit(reminder: Reminder) {
  const text = editingText.value.trim()
  if (!text) {
    deleteReminder(reminder)
    return
  }
  reminders.value = reminders.value.map((r) => (r.id === reminder.id ? { ...r, text } : r))
  editingId.value = null
  editingText.value = ''
  saveToStorage()
}

function cancelEdit() {
  editingId.value = null
  editingText.value = ''
}

function deleteReminder(reminder: Reminder) {
  reminders.value = reminders.value.filter((r) => r.id !== reminder.id)
  if (editingId.value === reminder.id) {
    editingId.value = null
    editingText.value = ''
  }
  saveToStorage()
}

onMounted(() => {
  loadFromStorage()
})
</script>

<template>
  <section class="reminders-widget">
    <div class="reminders-widget__head">
      <h2 class="reminders-widget__title">Lembretes rápidos</h2>
      <Button
        label="Gerir lembretes"
        icon="pi pi-list"
        size="small"
        @click="showDialog = true"
      />
    </div>

    <Dialog
      v-model:visible="showDialog"
      header="Lembretes rápidos"
      modal
      :style="{ width: 'min(92vw, 28rem)' }"
      :dismissable-mask="true"
      @hide="showDialog = false"
    >
      <div class="reminders-dialog">
        <p class="reminders-dialog__subtitle">
          {{
            atLimit
              ? `Limite de ${MAX_REMINDERS} lembretes. Remova algum para adicionar.`
              : `Anote o que não quer esquecer. (${remainingCount} restantes)`
          }}
        </p>

        <div class="reminders-dialog__input">
          <input
            v-model="newReminder"
            type="text"
            class="reminders-dialog__field"
            :placeholder="atLimit ? 'Limite atingido' : 'Ex.: Revisar Anki, terminar capítulo…'"
            :disabled="atLimit"
            @keyup.enter.prevent="addReminder"
          />
          <Button
            label="Adicionar"
            size="small"
            :disabled="!newReminder.trim() || atLimit"
            @click="addReminder"
          />
        </div>

        <div v-if="reminders.length" class="reminders-dialog__list scroll-pretty">
          <article v-for="r in reminders" :key="r.id" class="reminders-dialog__item">
            <div class="reminders-dialog__item-body">
              <textarea
                v-if="editingId === r.id"
                v-model="editingText"
                class="reminders-dialog__textarea"
                rows="2"
                @keyup.meta.enter.prevent="saveEdit(r)"
                @keyup.ctrl.enter.prevent="saveEdit(r)"
              />
              <button v-else type="button" class="reminders-dialog__text" @click="startEdit(r)">
                {{ r.text }}
              </button>
            </div>
            <div class="reminders-dialog__item-actions">
              <button
                v-if="editingId === r.id"
                type="button"
                class="reminders-chip reminders-chip--primary"
                @click="saveEdit(r)"
              >
                Salvar
              </button>
              <button
                v-if="editingId === r.id"
                type="button"
                class="reminders-chip"
                @click="cancelEdit"
              >
                Cancelar
              </button>
              <button
                type="button"
                class="reminders-chip reminders-chip--danger"
                @click="deleteReminder(r)"
              >
                Remover
              </button>
            </div>
          </article>
        </div>
        <div v-else class="reminders-dialog__empty">
          <EmptyState
            icon="📝"
            title="Nenhum lembrete ainda"
            description="Use o campo acima para criar o primeiro."
          />
        </div>
      </div>
    </Dialog>
  </section>
</template>

<style scoped>
.reminders-widget {
  background: var(--color-bg-card);
  border: var(--card-chrome-border);
  border-radius: var(--card-chrome-radius);
  padding: var(--spacing-xl);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  box-shadow: var(--card-chrome-shadow);
  min-height: var(--widget-card-min-height);
  height: 100%;
}
.reminders-widget__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-lg);
  flex-wrap: wrap;
}
.reminders-widget__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--text-lg);
  font-weight: 700;
  color: var(--color-text);
  line-height: var(--leading-tight);
  letter-spacing: var(--tracking-tight);
}

.reminders-dialog {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  min-height: 0;
}
.reminders-dialog__subtitle {
  margin: 0;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
}
.reminders-dialog__input {
  display: flex;
  gap: var(--spacing-sm);
  align-items: stretch;
  flex-wrap: wrap;
}
.reminders-dialog__field {
  flex: 1;
  min-width: 0;
  min-height: var(--input-height-sm);
  padding: var(--spacing-sm) var(--spacing-lg);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  font-size: var(--text-sm);
  background: var(--form-input-bg);
  color: var(--color-text);
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.reminders-dialog__field:focus-visible {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focus);
}
.reminders-dialog__field::placeholder {
  color: var(--color-text-muted);
}
.reminders-dialog__list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  max-height: min(50vh, 22rem);
  overflow-y: auto;
  overflow-x: hidden;
  padding: var(--spacing-2xs) 0;
}
.reminders-dialog__item {
  flex-shrink: 0;
  padding: var(--spacing-sm) var(--spacing-lg);
  border-radius: var(--radius-md);
  background: var(--color-bg-soft);
  border: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  transition: border-color var(--duration-fast) ease;
}
.reminders-dialog__item:hover {
  border-color: color-mix(in srgb, var(--color-primary) 30%, var(--color-border));
}
.reminders-dialog__item-body {
  width: 100%;
}
.reminders-dialog__text {
  all: unset;
  display: block;
  width: 100%;
  font-size: var(--text-sm);
  line-height: var(--leading-snug);
  color: var(--color-text);
  cursor: pointer;
  text-align: left;
}
.reminders-dialog__text:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
  border-radius: var(--radius-sm);
}
.reminders-dialog__textarea {
  width: 100%;
  min-height: 2.5rem;
  resize: vertical;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  padding: var(--spacing-xs) var(--spacing-sm);
  font-size: var(--text-sm);
  color: var(--color-text);
  background: var(--form-input-bg);
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.reminders-dialog__textarea:focus-visible {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focus);
}
.reminders-dialog__item-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-xs);
  justify-content: flex-end;
}
.reminders-chip {
  border: none;
  border-radius: var(--radius-full);
  padding: var(--spacing-2xs) var(--spacing-sm);
  font-size: var(--text-xs);
  font-weight: 600;
  cursor: pointer;
  background: var(--color-border);
  color: var(--color-text);
  transition:
    background var(--duration-fast) ease,
    color var(--duration-fast) ease;
}
.reminders-chip:hover {
  background: var(--color-text-muted);
  color: var(--color-bg-card);
}
.reminders-chip--primary {
  background: var(--color-primary);
  color: var(--color-primary-contrast);
}
.reminders-chip--primary:hover {
  background: var(--color-primary-hover);
}
.reminders-chip--danger {
  background: var(--color-error-soft);
  color: var(--color-error);
}
.reminders-chip--danger:hover {
  background: var(--color-error);
  color: var(--color-primary-contrast);
}
.reminders-chip:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.reminders-dialog__empty :deep(.empty-state) {
  min-height: 0;
  padding: var(--spacing-lg) var(--spacing-md);
}
@media (max-width: 640px) {
  .reminders-dialog__input {
    flex-direction: column;
  }
}
</style>
