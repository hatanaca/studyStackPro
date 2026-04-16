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
  padding: var(--spacing-sm) var(--spacing-lg);
  min-height: var(--input-height-sm);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-weight: 600;
  letter-spacing: var(--tracking-normal);
  line-height: 1;
  cursor: pointer;
  border: 1px solid transparent;
  box-shadow: var(--shadow-sm);
  transition:
    background var(--duration-fast) ease,
    color var(--duration-fast) ease,
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease,
    transform var(--duration-fast) var(--ease-out-expo);
}
.base-button:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.base-button:hover:not(:disabled) {
  transform: var(--state-hover-lift);
  box-shadow: var(--shadow-md);
}
.base-button:active:not(:disabled) {
  transform: var(--state-active-press);
}
.base-button--primary {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-primary-contrast);
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
  color: var(--color-primary-contrast);
}
.base-button--danger:hover:not(:disabled) {
  background: color-mix(in srgb, var(--color-error) 88%, var(--color-bg));
  border-color: color-mix(in srgb, var(--color-error) 88%, var(--color-bg));
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
  min-height: var(--input-height-sm);
  padding: var(--spacing-xs) var(--spacing-sm);
  font-size: var(--text-xs);
}
.base-button--md {
  padding: var(--spacing-sm) var(--spacing-lg);
  min-height: var(--input-height-sm);
}
.base-button--lg {
  min-height: var(--input-height-md);
  padding: var(--spacing-sm) var(--spacing-xl);
  font-size: var(--text-base);
}
.base-button:disabled {
  opacity: var(--state-disabled-opacity);
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
</style>
