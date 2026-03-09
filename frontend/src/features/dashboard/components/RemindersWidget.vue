<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Button from 'primevue/button'

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
    // ignore
  }
}

function saveToStorage() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(reminders.value))
}

function addReminder() {
  const text = newReminder.value.trim()
  if (!text || reminders.value.length >= MAX_REMINDERS) return
  reminders.value = [
    ...reminders.value,
    { id: crypto.randomUUID?.() ?? String(Date.now()), text },
  ]
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
  reminders.value = reminders.value.map((r) =>
    r.id === reminder.id ? { ...r, text } : r
  )
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
    <header class="reminders-header">
      <div>
        <h3 class="reminders-title">
          Lembretes rápidos
        </h3>
        <p class="reminders-subtitle">
          {{ atLimit ? `Limite de ${MAX_REMINDERS} lembretes. Remova algum para adicionar.` : `Anote o que não quer esquecer. (${remainingCount} restantes)` }}
        </p>
      </div>
    </header>

    <div class="reminders-input">
      <input
        v-model="newReminder"
        type="text"
        class="reminders-input__field"
        :placeholder="atLimit ? 'Limite atingido' : 'Ex.: Revisar Anki, terminar capítulo...'"
        :disabled="atLimit"
        @keyup.enter.prevent="addReminder"
      >
      <Button
        label="Adicionar"
        size="small"
        :disabled="!newReminder.trim() || atLimit"
        @click="addReminder"
      />
    </div>

    <div
      v-if="reminders.length"
      class="reminders-list scroll-pretty"
    >
      <article
        v-for="r in reminders"
        :key="r.id"
        class="reminders-item"
      >
        <div class="reminders-item__content">
          <textarea
            v-if="editingId === r.id"
            v-model="editingText"
            class="reminders-item__textarea"
            rows="2"
            @keyup.meta.enter.prevent="saveEdit(r)"
            @keyup.ctrl.enter.prevent="saveEdit(r)"
          />
          <p
            v-else
            class="reminders-item__text"
            @click="startEdit(r)"
          >
            {{ r.text }}
          </p>
        </div>
        <div class="reminders-item__actions">
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
    <p
      v-else
      class="reminders-empty"
    >
      Nenhum lembrete ainda. Use o campo acima para criar o primeiro.
    </p>
  </section>
</template>

<style scoped>
.reminders-widget {
  display: flex;
  flex-direction: column;
  min-height: var(--widget-card-min-height);
  height: 100%;
  background: var(--color-bg-card);
  border-radius: var(--widget-radius);
  padding: var(--widget-padding);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  overflow: hidden;
}
.reminders-header {
  flex-shrink: 0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-sm);
}
.reminders-title {
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  color: var(--color-text);
  margin: 0;
  letter-spacing: -0.01em;
}
.reminders-subtitle {
  margin: var(--spacing-xs) 0 0;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  line-height: 1.4;
}

.reminders-input {
  flex-shrink: 0;
  display: flex;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-sm);
  align-items: stretch;
}
.reminders-input__field {
  flex: 1;
  min-width: 0;
  min-height: 2rem;
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  font-size: var(--text-sm);
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.reminders-input__field:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
}
.reminders-input__field::placeholder {
  color: var(--color-text-muted);
}

.reminders-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-2xs);
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
}
.reminders-item {
  flex-shrink: 0;
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  background: var(--color-bg-soft);
  border: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  transition: border-color var(--duration-fast) ease;
}
.reminders-item:hover {
  border-color: color-mix(in srgb, var(--color-primary) 30%, var(--color-border));
}
.reminders-item__content {
  width: 100%;
}
.reminders-item__text {
  margin: 0;
  font-size: var(--text-sm);
  line-height: 1.45;
  color: var(--color-text);
  cursor: text;
}
.reminders-item__textarea {
  width: 100%;
  min-height: 2.5rem;
  resize: vertical;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  padding: var(--spacing-xs) var(--spacing-sm);
  font-size: var(--text-sm);
  color: var(--color-text);
  background: var(--color-bg-card);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.reminders-item__textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
}
.reminders-item__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-xs);
  justify-content: flex-end;
}
.reminders-chip {
  border: none;
  border-radius: 999px;
  padding: var(--spacing-2xs) var(--spacing-sm);
  font-size: var(--text-xs);
  font-weight: 600;
  cursor: pointer;
  background: var(--color-border);
  color: var(--color-text);
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease;
}
.reminders-chip:hover {
  background: var(--color-text-muted);
  color: var(--color-bg-card);
}
.reminders-chip--primary {
  background: var(--color-primary);
  color: #fff;
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
  color: #fff;
}

.reminders-empty {
  margin: var(--spacing-sm) 0 0;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: 1.5;
}

@media (max-width: 640px) {
  .reminders-input {
    flex-direction: column;
    align-items: stretch;
  }
}

.reminders-input :deep(.base-button) {
  min-width: 6.5rem;
  min-height: 2rem;
}
</style>

