<script setup lang="ts">
withDefaults(
  defineProps<{
    type?: 'button' | 'submit'
    variant?: 'primary' | 'secondary' | 'ghost'
    disabled?: boolean
  }>(),
  { type: 'button', variant: 'primary', disabled: false }
)
</script>

<template>
  <button
    :type="type"
    class="base-button"
    :class="[`base-button--${variant}`]"
    :disabled="disabled"
  >
    <slot />
  </button>
</template>

<style scoped>
.base-button {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  cursor: pointer;
  border: none;
}
.base-button--primary {
  background: #3b82f6;
  color: #fff;
}
.base-button--secondary {
  background: #e2e8f0;
  color: #475569;
}
.base-button--ghost {
  background: transparent;
  color: #64748b;
}
.base-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
