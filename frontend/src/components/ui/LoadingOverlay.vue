<script setup lang="ts">
withDefaults(
  defineProps<{
    show: boolean
    message?: string
  }>(),
  { message: 'Carregando...' }
)
</script>

<template>
  <Transition name="fade">
    <div
      v-if="show"
      class="loading-overlay"
      role="status"
      aria-live="polite"
      aria-label="Carregando"
    >
      <div class="loading-overlay__backdrop" />
      <div class="loading-overlay__content">
        <span class="loading-overlay__spinner" />
        <span
          v-if="message"
          class="loading-overlay__message"
        >{{ message }}</span>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.loading-overlay {
  position: fixed;
  inset: 0;
  z-index: 9998;
  display: flex;
  align-items: center;
  justify-content: center;
}
.loading-overlay__backdrop {
  position: absolute;
  inset: 0;
  background: color-mix(in srgb, var(--color-bg-card) 75%, transparent);
  backdrop-filter: blur(4px);
}
.loading-overlay__content {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-lg);
  padding: var(--spacing-2xl);
}
.loading-overlay__spinner {
  width: 2.5rem;
  height: 2.5rem;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.loading-overlay__message {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  font-weight: 500;
}
</style>
