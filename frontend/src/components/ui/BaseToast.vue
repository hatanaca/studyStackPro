<script setup lang="ts">
import { useToast } from '@/composables/useToast'

const { toasts, dismiss } = useToast()
</script>

<template>
  <div class="toast-container">
    <TransitionGroup name="toast">
      <div
        v-for="t in toasts"
        :key="t.id"
        class="toast"
        :class="[`toast--${t.type}`]"
        role="alert"
      >
        <span class="message">{{ t.message }}</span>
        <button
          type="button"
          class="close"
          aria-label="Fechar"
          @click="dismiss(t.id)"
        >
          ×
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-container {
  position: fixed;
  top: var(--spacing-md);
  right: var(--spacing-md);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  max-width: 380px;
  width: calc(100% - var(--spacing-xl));
}
@media (min-width: 481px) {
  .toast-container {
    width: auto;
  }
}
.toast {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  border: 1px solid transparent;
}
.toast--success {
  background: color-mix(in srgb, var(--color-success) 15%, var(--color-bg-card));
  border-color: color-mix(in srgb, var(--color-success) 35%, transparent);
  color: var(--color-text);
}
.toast--error {
  background: color-mix(in srgb, var(--color-error) 15%, var(--color-bg-card));
  border-color: color-mix(in srgb, var(--color-error) 35%, transparent);
  color: var(--color-text);
}
.toast--info {
  background: color-mix(in srgb, var(--color-info) 15%, var(--color-bg-card));
  border-color: color-mix(in srgb, var(--color-info) 35%, transparent);
  color: var(--color-text);
}
.message {
  flex: 1;
  font-size: var(--text-sm);
  line-height: 1.4;
}
.close {
  background: none;
  border: none;
  color: var(--color-text-muted);
  font-size: var(--text-base);
  cursor: pointer;
  opacity: 1;
  width: var(--icon-size-md);
  height: var(--icon-size-md);
  border-radius: 9999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  transition: color var(--duration-fast) ease,
    background var(--duration-fast) ease;
}
.close:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.close:hover {
  color: var(--color-text);
  background: color-mix(in srgb, var(--color-text-muted) 12%, transparent);
}
.toast-enter-active,
.toast-leave-active {
  transition: opacity var(--duration-normal) var(--ease-in-out),
    transform var(--duration-normal) var(--ease-in-out);
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}
</style>
