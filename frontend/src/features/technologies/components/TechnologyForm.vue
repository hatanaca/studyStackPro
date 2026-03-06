<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import type { Technology } from '@/types/domain.types'

const props = defineProps<{
  modelValue?: Technology | null
}>()

const emit = defineEmits<{
  'update:modelValue': [Technology | null]
  submit: [payload: { name: string; color: string; description?: string }]
  cancel: []
}>()

const name = ref('')
const color = ref('#3498DB')
const description = ref('')
const errors = ref<{ name?: string }>({})

watch(
  () => props.modelValue,
  (tech) => {
    if (tech) {
      name.value = tech.name
      color.value = tech.color || '#3498DB'
      description.value = tech.description || ''
    } else {
      reset()
    }
  },
  { immediate: true }
)

function reset() {
  name.value = ''
  color.value = '#3498DB'
  description.value = ''
  errors.value = {}
}

function validate(): boolean {
  const e: { name?: string } = {}
  if (!name.value.trim()) e.name = 'Nome é obrigatório'
  errors.value = e
  return Object.keys(e).length === 0
}

function normalizeHexColor(hex: string): string {
  const m = hex.match(/^#?([0-9A-Fa-f]+)$/)
  if (!m) return '#3498DB'
  const s = m[1]
  if (s.length === 6) return '#' + s
  if (s.length === 3) return '#' + s.split('').map((c) => c + c).join('')
  if (s.length === 5) return '#' + (s + s[0]).slice(0, 6)
  return '#' + (s + '0'.repeat(6)).slice(0, 6)
}

function onSubmit() {
  if (!validate()) return
  emit('submit', {
    name: name.value.trim(),
    color: normalizeHexColor(color.value),
    description: description.value.trim() || undefined
  })
}

function onCancel() {
  reset()
  emit('cancel')
}

defineExpose({ reset, setError: (msg: string) => { errors.value = { name: msg } } })
</script>

<template>
  <form
    class="technology-form"
    @submit.prevent="onSubmit"
  >
    <BaseInput
      v-model="name"
      type="text"
      placeholder="Ex: JavaScript"
      label="Nome"
      :error="errors.name"
    />
    <div class="field">
      <label class="label">Cor</label>
      <div class="color-input">
        <input
          v-model="color"
          type="color"
          class="color-picker"
        >
        <input
          v-model="color"
          type="text"
          class="color-text"
          maxlength="7"
        >
      </div>
    </div>
    <BaseInput
      v-model="description"
      type="text"
      placeholder="Descrição (opcional)"
      label="Descrição"
    />
    <div class="actions">
      <BaseButton type="submit">
        {{ modelValue ? 'Salvar' : 'Criar' }}
      </BaseButton>
      <BaseButton
        type="button"
        variant="secondary"
        @click="onCancel"
      >
        Cancelar
      </BaseButton>
    </div>
  </form>
</template>

<style scoped>
.technology-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
.field {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.label {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
}
.color-input {
  display: flex;
  gap: var(--spacing-sm);
  align-items: center;
}
.color-picker {
  width: 2.5rem;
  height: var(--input-height-sm);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  background: var(--color-bg-card);
  transition: border-color var(--duration-fast) ease;
}
.color-picker:hover {
  border-color: var(--color-primary);
}
.color-text {
  flex: 1;
  min-height: var(--input-height-sm);
  padding: 0.45rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-family: monospace;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.color-text:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
}
.actions {
  display: flex;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-xs);
}
</style>
