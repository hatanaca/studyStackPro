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
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-xs);
  padding: 0.5rem 0.95rem;
  min-height: var(--input-height-sm);
  border-radius: var(--radius-md, 0.375rem);
  font-size: var(--text-sm, 0.875rem);
  font-weight: 600;
  letter-spacing: 0.01em;
  line-height: 1;
  cursor: pointer;
  border: 1px solid transparent;
  box-shadow: var(--shadow-sm);
  transition: background var(--duration-fast, 0.15s) ease,
    color var(--duration-fast, 0.15s) ease,
    border-color var(--duration-fast, 0.15s) ease,
    box-shadow var(--duration-fast, 0.15s) ease,
    transform var(--duration-fast, 0.15s) var(--ease-out-expo, ease);
}
.base-button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}
.base-button:active:not(:disabled) {
  transform: translateY(0);
}
.base-button--primary {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
}
.base-button--primary:hover:not(:disabled) {
  background: var(--color-primary-hover);
  border-color: var(--color-primary-hover);
}
.base-button--secondary {
  background: color-mix(in srgb, var(--color-text) 8%, var(--color-bg-card));
  border-color: var(--color-border);
  color: var(--color-text);
}
.base-button--secondary:hover:not(:disabled) {
  background: color-mix(in srgb, var(--color-primary) 12%, var(--color-bg-card));
  border-color: color-mix(in srgb, var(--color-primary) 38%, var(--color-border));
  color: var(--color-primary);
}
.base-button--ghost {
  background: transparent;
  border-color: transparent;
  color: var(--color-text-muted);
  box-shadow: none;
}
.base-button--ghost:hover:not(:disabled) {
  background: var(--color-bg-soft);
  color: var(--color-text);
}
.base-button--danger {
  background: var(--color-error);
  border-color: var(--color-error);
  color: #fff;
}
.base-button--danger:hover:not(:disabled) {
  background: color-mix(in srgb, var(--color-error) 88%, #000);
  border-color: color-mix(in srgb, var(--color-error) 88%, #000);
}
.base-button--outline {
  background: transparent;
  border: 1px solid var(--color-border);
  color: var(--color-text);
  box-shadow: none;
}
.base-button--outline:hover:not(:disabled) {
  background: var(--color-bg-soft);
  border-color: var(--color-primary);
  color: var(--color-primary);
}
.base-button--sm {
  min-height: 2rem;
  padding: 0.25rem 0.7rem;
  font-size: var(--text-xs, 0.8125rem);
}
.base-button--md {
  padding: 0.5rem 1rem;
  min-height: var(--input-height-sm);
}
.base-button--lg {
  min-height: var(--input-height-md);
  padding: 0.65rem 1.2rem;
  font-size: var(--text-base, 1rem);
}
.base-button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
</style>
