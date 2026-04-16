<script setup lang="ts">
import { ref, onBeforeUnmount } from 'vue'

const props = withDefaults(
  defineProps<{
    /** Texto a ser copiado para o clipboard */
    text: string
    /** Rótulo acessível do botão */
    label?: string
    /** Rótulo após copiar (feedback) */
    copiedLabel?: string
    /** Tamanho do botão */
    size?: 'sm' | 'md'
  }>(),
  {
    label: 'Copiar',
    copiedLabel: 'Copiado!',
    size: 'sm',
  }
)

const copied = ref(false)
let resetTimer: ReturnType<typeof setTimeout> | null = null

async function copy() {
  if (!props.text) return
  try {
    await navigator.clipboard.writeText(props.text)
    copied.value = true
    if (resetTimer) clearTimeout(resetTimer)
    resetTimer = setTimeout(() => {
      copied.value = false
      resetTimer = null
    }, 2000)
  } catch {
    copied.value = false
  }
}

onBeforeUnmount(() => {
  if (resetTimer) {
    clearTimeout(resetTimer)
    resetTimer = null
  }
})
</script>

<template>
  <button
    type="button"
    class="copy-button"
    :class="[`copy-button--${size}`, { 'copy-button--copied': copied }]"
    :aria-label="copied ? copiedLabel : label"
    :title="copied ? copiedLabel : label"
    @click="copy"
  >
    <span v-if="copied" class="copy-button__icon" aria-hidden="true"> ✓ </span>
    <span v-else class="copy-button__icon copy-button__icon--copy" aria-hidden="true"> 📋 </span>
    <span class="copy-button__text">
      {{ copied ? copiedLabel : label }}
    </span>
  </button>
</template>

<style scoped>
.copy-button {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-xs);
  padding: var(--spacing-2xs) var(--spacing-sm);
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  background: var(--color-bg-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition:
    color var(--duration-fast) ease,
    background var(--duration-fast) ease,
    border-color var(--duration-fast) ease;
}
.copy-button:hover {
  color: var(--color-primary);
  border-color: var(--color-primary);
  background: var(--color-primary-soft);
}
.copy-button--copied {
  color: var(--color-success);
  border-color: var(--color-success);
  background: var(--color-success-soft);
}
.copy-button__icon {
  flex-shrink: 0;
  font-size: var(--text-sm);
  line-height: 1;
}
.copy-button__icon--copy {
  opacity: 0.9;
}
.copy-button--md {
  padding: var(--spacing-xs) var(--spacing-lg);
  font-size: var(--text-sm);
}
</style>
