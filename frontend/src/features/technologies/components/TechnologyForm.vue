<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import type { Technology } from '@/types/domain.types'
import { normalizeHexColor, safeHexColor } from '@/utils/color'

const props = defineProps<{
  modelValue?: Technology | null
}>()

const emit = defineEmits<{
  'update:modelValue': [Technology | null]
  submit: [payload: { name: string; color: string; description?: string }]
  cancel: []
}>()

const name = ref('')
const color = ref('#3b82f6')
const description = ref('')
const errors = ref<{ name?: string }>({})

watch(
  () => props.modelValue,
  (tech) => {
    if (tech) {
      name.value = tech.name
      color.value = tech.color || '#3b82f6'
      description.value = tech.description || ''
    } else {
      reset()
    }
  },
  { immediate: true }
)

function reset() {
  name.value = ''
  color.value = '#3b82f6'
  description.value = ''
  errors.value = {}
}

function validate(): boolean {
  const e: { name?: string } = {}
  if (!name.value.trim()) e.name = 'Nome é obrigatório'
  errors.value = e
  return Object.keys(e).length === 0
}

function onColorPickerInput(event: Event) {
  const target = event.target as { value?: string } | null
  if (!target) return
  color.value = safeHexColor(target.value ?? '')
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
      id="tech-name"
      v-model="name"
      label="Nome"
      placeholder="Ex: JavaScript"
      :error="errors.name"
    />
    <div class="field">
      <span class="label">Cor</span>
      <div class="color-input">
        <input
          :value="safeHexColor(color)"
          type="color"
          class="color-picker"
          @input="onColorPickerInput"
        >
        <input
          v-model="color"
          type="text"
          class="color-text"
          maxlength="7"
          placeholder="#3b82f6"
        >
      </div>
    </div>
    <BaseInput
      id="tech-desc"
      v-model="description"
      label="Descrição"
      placeholder="Descrição (opcional)"
    />
    <div class="actions">
      <BaseButton
        type="submit"
        variant="primary"
      >
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
  gap: var(--form-section-gap);
}
.field {
  display: flex;
  flex-direction: column;
  gap: var(--form-field-gap);
}
.label {
  font-size: var(--form-label-size);
  font-weight: var(--form-label-weight);
  letter-spacing: var(--form-label-tracking);
  color: var(--form-label-color);
}
.color-input {
  display: flex;
  gap: var(--spacing-sm);
  align-items: center;
}
.color-picker {
  width: 2.5rem;
  height: var(--form-input-height);
  border: 1px solid var(--form-input-border);
  border-radius: var(--form-input-radius);
  padding: var(--spacing-2xs);
  cursor: pointer;
  background: var(--form-input-bg);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.color-picker:hover {
  border-color: var(--form-input-border-focus);
}
.color-picker:focus-visible {
  outline: none;
  box-shadow: var(--form-input-shadow-focus);
}
.color-text {
  flex: 1;
  min-height: var(--form-input-height);
  padding: var(--form-input-padding);
  border: 1px solid var(--form-input-border);
  border-radius: var(--form-input-radius);
  font-size: var(--form-input-font-size);
  font-family: monospace;
  background: var(--form-input-bg);
  color: var(--form-input-text);
  outline: none;
  transition: border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease,
    background var(--duration-fast) ease;
  box-sizing: border-box;
}
.color-text::placeholder {
  color: var(--form-input-placeholder);
}
.color-text:focus-visible {
  border-color: var(--form-input-border-focus);
  box-shadow: var(--form-input-shadow-focus);
}
.actions {
  display: flex;
  gap: var(--spacing-md);
  margin-top: var(--spacing-sm);
}
</style>
