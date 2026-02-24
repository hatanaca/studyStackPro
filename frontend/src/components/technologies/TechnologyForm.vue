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

function onSubmit() {
  if (!validate()) return
  emit('submit', {
    name: name.value.trim(),
    color: color.value,
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
  <form @submit.prevent="onSubmit" class="technology-form">
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
        <input v-model="color" type="color" class="color-picker" />
        <input v-model="color" type="text" class="color-text" maxlength="7" />
      </div>
    </div>
    <BaseInput
      v-model="description"
      type="text"
      placeholder="Descrição (opcional)"
      label="Descrição"
    />
    <div class="actions">
      <BaseButton type="submit">{{ modelValue ? 'Salvar' : 'Criar' }}</BaseButton>
      <BaseButton type="button" variant="secondary" @click="onCancel">
        Cancelar
      </BaseButton>
    </div>
  </form>
</template>

<style scoped>
.technology-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.label {
  font-size: 0.875rem;
  color: #475569;
}
.color-input {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}
.color-picker {
  width: 40px;
  height: 36px;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  cursor: pointer;
}
.color-text {
  flex: 1;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}
.actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
</style>
