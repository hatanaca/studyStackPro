<script setup lang="ts">
defineProps<{
  message?: string
  onRetry?: () => void
}>()
</script>

<template>
  <div class="error-card">
    <p class="message">
      {{ message || 'Ocorreu um erro ao carregar os dados.' }}
    </p>
    <button
      v-if="onRetry"
      type="button"
      class="retry"
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
  padding: var(--spacing-lg);
  text-align: center;
}
.message {
  color: var(--color-error);
  margin-bottom: var(--spacing-md);
  font-size: var(--text-sm);
  line-height: 1.5;
}
.retry {
  min-height: var(--input-height-sm);
  padding: 0.5rem 1rem;
  background: var(--color-error);
  color: #fff;
  border: 1px solid var(--color-error);
  border-radius: var(--radius-md);
  cursor: pointer;
  font-weight: 600;
  font-size: var(--text-sm);
  transition: background var(--duration-fast) ease, border-color var(--duration-fast) ease, transform var(--duration-fast) ease;
}
.retry:hover {
  background: color-mix(in srgb, var(--color-error) 88%, #000);
  border-color: color-mix(in srgb, var(--color-error) 88%, #000);
  transform: translateY(-1px);
}
</style>
