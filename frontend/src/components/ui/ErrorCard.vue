<script setup lang="ts">
defineProps<{
  message?: string
  onRetry?: () => void
}>()
</script>

<template>
  <div
    class="error-card"
    role="alert"
    aria-live="assertive"
    aria-atomic="true"
  >
    <p
      id="error-card-message"
      class="message"
    >
      {{ message || 'Ocorreu um erro ao carregar os dados.' }}
    </p>
    <button
      v-if="onRetry"
      type="button"
      class="retry"
      aria-describedby="error-card-message"
      @click="onRetry"
    >
      Tentar novamente
    </button>
  </div>
</template>

<style scoped>
.error-card {
  background: var(--color-error-soft);
  border: 1px solid color-mix(in srgb, var(--color-error) 40%, transparent);
  border-radius: var(--radius-md);
  padding: var(--spacing-xl);
  text-align: center;
}
.message {
  color: var(--color-error);
  margin-bottom: var(--spacing-lg);
  font-size: var(--text-sm);
  line-height: var(--leading-normal);
}
.retry {
  min-height: var(--input-height-sm);
  padding: var(--spacing-sm) var(--spacing-lg);
  background: var(--color-error);
  color: var(--color-primary-contrast);
  border: 1px solid var(--color-error);
  border-radius: var(--radius-md);
  cursor: pointer;
  font-weight: 600;
  font-size: var(--text-sm);
  transition: background var(--duration-fast) ease,
    border-color var(--duration-fast) ease,
    transform var(--duration-fast) var(--ease-out-expo);
}
.retry:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.retry:hover {
  background: color-mix(in srgb, var(--color-error) 88%, black);
  border-color: color-mix(in srgb, var(--color-error) 88%, black);
  transform: translateY(-1px);
}
</style>
