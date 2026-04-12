<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import Button from 'primevue/button'

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
  reminders.value = [
    ...reminders.value,
    { id: crypto.randomUUID?.() ?? String(Date.now()), text },
  ]
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
  reminders.value = reminders.value.map((item) =>
    item.id === r.id ? { ...item, text } : item
  )
  editingId.value = null
  editingText.value = ''
  saveToStorage()
}

function cancelEdit() {
  editingId.value = null
  editingText.value = ''
}

function deleteReminder(r: Reminder) {
  reminders.value = reminders.value.filter((item) => item.id !== r.id)
  if (editingId.value === r.id) {
    editingId.value = null
    editingText.value = ''
  }
  saveToStorage()
}

onMounted(loadFromStorage)
watch(() => props.technologyId, loadFromStorage)
</script>

<template>
  <section class="tech-reminders">
    <h2 class="tech-reminders__title">
      Lembretes
    </h2>
    <p class="tech-reminders__subtitle">
      Anote o que não quer esquecer para esta tecnologia.
    </p>
    <div class="tech-reminders__input">
      <input
        v-model="newReminder"
        type="text"
        class="tech-reminders__field"
        :placeholder="atLimit ? 'Limite atingido' : 'Novo lembrete...'"
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
    <ul
      v-if="reminders.length"
      class="tech-reminders__list"
    >
      <li
        v-for="r in reminders"
        :key="r.id"
        class="tech-reminders__item"
      >
        <textarea
          v-if="editingId === r.id"
          v-model="editingText"
          class="tech-reminders__textarea"
          rows="2"
          @keydown.ctrl.enter.prevent="saveEdit(r)"
        />
        <button
          v-else
          type="button"
          class="tech-reminders__text"
          @click="startEdit(r)"
        >
          {{ r.text }}
        </button>
        <div class="tech-reminders__actions">
          <template v-if="editingId === r.id">
            <Button label="Salvar" size="small" @click="saveEdit(r)" />
            <Button label="Cancelar" size="small" variant="text" severity="secondary" @click="cancelEdit" />
          </template>
          <Button
            v-else
            label="Remover"
            size="small"
            variant="text"
            severity="secondary"
            @click="deleteReminder(r)"
          />
        </div>
      </li>
    </ul>
    <p
      v-else
      class="tech-reminders__empty"
    >
      Nenhum lembrete. Adicione um acima.
    </p>
  </section>
</template>

<style scoped>
.tech-reminders {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: var(--widget-padding);
  border: 1px solid var(--color-border);
}
.tech-reminders__title {
  font-size: var(--text-base);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--spacing-sm);
  letter-spacing: var(--tracking-tight);
}
.tech-reminders__subtitle {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-lg);
  line-height: var(--leading-snug);
}
.tech-reminders__input {
  display: flex;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-lg);
}
.tech-reminders__field {
  flex: 1;
  min-width: 0;
  min-height: var(--input-height-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.tech-reminders__field:focus-visible {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focus);
}
.tech-reminders__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}
.tech-reminders__item {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm);
  background: var(--color-bg-soft);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
.tech-reminders__text {
  all: unset;
  flex: 1;
  min-width: 0;
  font-size: var(--text-sm);
  color: var(--color-text);
  cursor: pointer;
  line-height: var(--leading-snug);
  text-align: left;
}
.tech-reminders__text:focus-visible {
  outline: none;
  border-radius: var(--radius-sm);
  box-shadow: var(--shadow-focus);
}
.tech-reminders__textarea {
  flex: 1;
  min-width: 0;
  padding: var(--spacing-xs);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-family: inherit;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.tech-reminders__textarea:focus-visible {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focus);
}
.tech-reminders__actions {
  display: flex;
  gap: var(--spacing-sm);
}
.tech-reminders__actions :deep(.p-button:focus-visible) {
  box-shadow: var(--shadow-focus);
}
.tech-reminders__empty {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: var(--leading-normal);
}
</style>
