<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import Button from 'primevue/button'
import { STUDYTRACK_REMINDER_REMOVED_EVENT } from '@/features/sessions/utils/reminderRemovedSync'

const props = defineProps<{
  technologyId: string
}>()

interface Reminder {
  id: string
  text: string
}

const STORAGE_KEY_PREFIX = 'studytrack.reminders.'
const MAX_REMINDERS = 25

const reminders = ref<Reminder[]>([])
const newReminder = ref('')
const editingId = ref<string | null>(null)
const editingText = ref('')

const storageKey = computed(() => `${STORAGE_KEY_PREFIX}${props.technologyId}`)
const atLimit = computed(() => reminders.value.length >= MAX_REMINDERS)

function loadFromStorage() {
  try {
    const raw = localStorage.getItem(storageKey.value)
    if (!raw) return
    const parsed = JSON.parse(raw) as Reminder[]
    if (Array.isArray(parsed)) reminders.value = parsed
  } catch {
    reminders.value = []
  }
}

function saveToStorage() {
  localStorage.setItem(storageKey.value, JSON.stringify(reminders.value))
}

function addReminder() {
  const text = newReminder.value.trim()
  if (!text || atLimit.value) return
  reminders.value = [...reminders.value, { id: crypto.randomUUID?.() ?? String(Date.now()), text }]
  newReminder.value = ''
  saveToStorage()
}

function startEdit(r: Reminder) {
  editingId.value = r.id
  editingText.value = r.text
}

function saveEdit(r: Reminder) {
  const text = editingText.value.trim()
  if (!text) {
    deleteReminder(r)
    return
  }
  reminders.value = reminders.value.map((item) => (item.id === r.id ? { ...item, text } : item))
  editingId.value = null
  editingText.value = ''
  saveToStorage()
}

function cancelEdit() {
  editingId.value = null
  editingText.value = ''
}

function deleteReminder(r: Reminder) {
  const removedText = r.text.trim()
  reminders.value = reminders.value.filter((item) => item.id !== r.id)
  if (editingId.value === r.id) {
    editingId.value = null
    editingText.value = ''
  }
  saveToStorage()
  window.dispatchEvent(
    new CustomEvent(STUDYTRACK_REMINDER_REMOVED_EVENT, {
      detail: { technologyId: props.technologyId, text: removedText },
      bubbles: true,
    }),
  )
}

onMounted(loadFromStorage)
watch(() => props.technologyId, loadFromStorage)
</script>

<template>
  <section class="tech-reminders">
    <div class="tech-reminders__header">
      <div>
        <h2 class="tech-reminders__title">Lembretes</h2>
        <p class="tech-reminders__subtitle">Anote o que não quer esquecer para esta tecnologia.</p>
      </div>
    </div>

    <div class="tech-reminders__toolbar">
      <input
        v-model="newReminder"
        type="text"
        class="tech-reminders__field"
        :placeholder="atLimit ? 'Limite atingido' : 'Novo lembrete…'"
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

    <ul v-if="reminders.length" class="tech-reminders__list">
      <li v-for="(r, idx) in reminders" :key="r.id" class="tech-reminders__card">
        <span
          class="tech-reminders__accent"
          :style="{ background: idx % 2 === 0 ? 'var(--color-primary)' : '#f59e0b' }"
          aria-hidden="true"
        />
        <div class="tech-reminders__card-body">
          <textarea
            v-if="editingId === r.id"
            v-model="editingText"
            class="tech-reminders__textarea"
            rows="2"
            @keydown.enter.exact.prevent="saveEdit(r)"
          />
          <div
            v-else
            role="button"
            class="tech-reminders__text"
            @click="startEdit(r)"
            @keydown.enter.prevent="startEdit(r)"
          >
            {{ r.text }}
          </div>
        </div>
        <div class="tech-reminders__side">
          <template v-if="editingId === r.id">
            <Button label="Salvar" size="small" @click="saveEdit(r)" />
            <Button
              label="Cancelar"
              size="small"
              variant="outlined"
              severity="secondary"
              @click="cancelEdit"
            />
          </template>
          <button
            v-else
            type="button"
            class="tech-reminders__delete"
            aria-label="Remover lembrete"
            @click="deleteReminder(r)"
          >
            ✕
          </button>
        </div>
      </li>
    </ul>
    <p v-else class="tech-reminders__empty">Nenhum lembrete. Adicione um acima.</p>
  </section>
</template>

<style scoped>
.tech-reminders {
  max-width: 100%;
}
.tech-reminders__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--spacing-lg);
  padding: var(--spacing-lg) var(--spacing-xl);
  background: var(--surface-page-header-bg);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--surface-page-header-shadow);
}
.tech-reminders__title {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 var(--spacing-xs);
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.tech-reminders__subtitle {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
  line-height: var(--leading-snug);
}
.tech-reminders__toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-lg);
  align-items: center;
}
.tech-reminders__field {
  flex: 1;
  min-width: 0;
  min-height: var(--input-height-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  line-height: 1.45;
  box-sizing: border-box;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.tech-reminders__field:focus-visible {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focus);
}
.tech-reminders__toolbar :deep(.p-button:focus-visible) {
  box-shadow: var(--shadow-focus);
}
.tech-reminders__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
.tech-reminders__card {
  position: relative;
  display: flex;
  align-items: stretch;
  gap: var(--spacing-md);
  padding: var(--spacing-md) var(--spacing-md) var(--spacing-md) calc(var(--spacing-md) + 4px);
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.tech-reminders__card:hover {
  border-color: color-mix(in srgb, var(--color-primary) 22%, var(--color-border));
  box-shadow: var(--shadow-card-hover);
}
.tech-reminders__accent {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 4px;
  border-radius: var(--radius-lg) 0 0 var(--radius-lg);
}
.tech-reminders__card-body {
  flex: 1;
  min-width: 0;
  overflow-x: hidden;
  overflow-y: visible;
}
.tech-reminders__text {
  margin: 0;
  box-sizing: border-box;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 4;
  overflow: hidden;
  width: 100%;
  max-width: 100%;
  font-size: var(--text-sm);
  font-weight: 600;
  font-family: inherit;
  color: var(--color-text);
  cursor: pointer;
  line-height: var(--leading-snug);
  text-align: left;
  word-break: break-word;
  user-select: none;
  -webkit-user-select: none;
}
.tech-reminders__text:focus-visible {
  outline: none;
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-focus);
}
.tech-reminders__textarea {
  display: block;
  width: 100%;
  min-width: 0;
  min-height: 4.5rem;
  max-height: 7.5rem;
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  line-height: 1.45;
  font-family: inherit;
  text-align: start;
  vertical-align: top;
  background: var(--color-bg-soft);
  color: var(--color-text);
  caret-color: var(--color-text);
  resize: none;
  overflow-x: hidden;
  overflow-y: auto;
  box-sizing: border-box;
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.tech-reminders__textarea:focus-visible {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focus);
}
.tech-reminders__side {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: var(--spacing-xs);
}
.tech-reminders__side :deep(.p-button:focus-visible) {
  box-shadow: var(--shadow-focus);
}
.tech-reminders__delete {
  width: 2rem;
  height: 2rem;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-soft);
  color: var(--color-text-muted);
  font-size: var(--text-xs);
  line-height: 1;
  cursor: pointer;
  transition:
    border-color var(--duration-fast) ease,
    color var(--duration-fast) ease,
    background var(--duration-fast) ease;
}
.tech-reminders__delete:hover {
  border-color: var(--color-error);
  color: var(--color-error);
  background: var(--color-error-soft);
}
.tech-reminders__delete:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.tech-reminders__empty {
  margin: var(--spacing-lg) 0 0;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: var(--leading-normal);
}
</style>
