<script setup lang="ts">
import Button from 'primevue/button'
import LanguageSelector from './LanguageSelector.vue'
import type { ProgrammingLanguage } from '../types/code-terminal.types'

defineProps<{
  language: ProgrammingLanguage
  isExecuting: boolean
  darkMode: boolean
}>()

const emit = defineEmits<{
  'update:language': [value: ProgrammingLanguage]
  run: []
  clear: []
  'toggle-theme': []
}>()
</script>

<template>
  <div class="terminal-toolbar">
    <div class="terminal-toolbar__left">
      <LanguageSelector
        :model-value="language"
        @update:model-value="emit('update:language', $event)"
      />
    </div>

    <div class="terminal-toolbar__right">
      <Button
        icon="pi pi-play"
        label="Run"
        severity="success"
        size="small"
        :loading="isExecuting"
        :disabled="isExecuting"
        aria-label="Executar código"
        @click="emit('run')"
      />

      <Button
        icon="pi pi-trash"
        severity="danger"
        variant="text"
        size="small"
        aria-label="Limpar output"
        @click="emit('clear')"
      />

      <Button
        :icon="darkMode ? 'pi pi-sun' : 'pi pi-moon'"
        severity="secondary"
        variant="text"
        size="small"
        :aria-label="darkMode ? 'Tema claro' : 'Tema escuro'"
        @click="emit('toggle-theme')"
      />
    </div>
  </div>
</template>

<style scoped>
.terminal-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  gap: var(--spacing-sm);
}

.terminal-toolbar__left {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.terminal-toolbar__right {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}

@media (max-width: 640px) {
  .terminal-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .terminal-toolbar__right {
    justify-content: flex-end;
  }
}
</style>
