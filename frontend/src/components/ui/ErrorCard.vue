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
      class="retry"
      @click="onRetry"
    >
      Tentar novamente
    </button>
  </div>
</template>

<style scoped>
.error-card {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 0.5rem;
  padding: 1rem;
  text-align: center;
}
.message {
  color: #991b1b;
  margin-bottom: 0.75rem;
}
.retry {
  padding: 0.5rem 1rem;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  font-weight: 500;
}
.retry:hover {
  background: #b91c1c;
}
</style>
