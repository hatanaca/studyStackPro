<script setup lang="ts">
import { LANGUAGE_CONFIGS } from '../types/code-terminal.types'
import type { ProgrammingLanguage } from '../types/code-terminal.types'

const props = defineProps<{
  modelValue: ProgrammingLanguage
}>()

const emit = defineEmits<{
  'update:modelValue': [value: ProgrammingLanguage]
}>()

const languages = Object.values(LANGUAGE_CONFIGS)

function handleChange(event: Event) {
  const target = event.target as HTMLSelectElement
  emit('update:modelValue', target.value as ProgrammingLanguage)
}
</script>

<template>
  <select
    class="language-selector"
    :value="modelValue"
    aria-label="Selecionar linguagem"
    @change="handleChange"
  >
    <option
      v-for="lang in languages"
      :key="lang.name"
      :value="lang.name"
    >
      {{ lang.label }}
    </option>
  </select>
</template>

<style scoped>
.language-selector {
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
  color: var(--color-text);
  font-size: var(--text-sm);
  font-weight: 500;
  cursor: pointer;
  transition: border-color var(--duration-fast) ease;
  appearance: auto;
}

.language-selector:hover {
  border-color: var(--color-primary);
}

.language-selector:focus {
  outline: 2px solid var(--color-primary);
  outline-offset: 1px;
}
</style>
