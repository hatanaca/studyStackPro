<script setup lang="ts">
withDefaults(
  defineProps<{
    type?: 'button' | 'submit'
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger' | 'outline'
    size?: 'sm' | 'md' | 'lg'
    disabled?: boolean
  }>(),
  { type: 'button', variant: 'primary', size: 'md', disabled: false }
)
</script>

<template>
  <button
    :type="type"
    class="base-button"
    :class="[`base-button--${variant}`, `base-button--${size}`]"
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
.base-button--danger {
  background: #dc2626;
  color: #fff;
}
.base-button--outline {
  background: transparent;
  border: 1px solid #94a3b8;
  color: #475569;
}
.base-button--outline:hover:not(:disabled) {
  background: #f1f5f9;
}
.base-button--sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.8125rem;
}
.base-button--md {
  padding: 0.5rem 1rem;
}
.base-button--lg {
  padding: 0.75rem 1.25rem;
  font-size: 1rem;
}
.base-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
