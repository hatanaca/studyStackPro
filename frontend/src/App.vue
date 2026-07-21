<script setup lang="ts">
/**
 * Componente raiz da aplicação.
 * Renderiza RouterView, Toast global, ConfirmDialog e ApiToastInit (integração API → toast).
 */
import { ref, onErrorCaptured } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import ApiToastInit from '@/components/ApiToastInit.vue'

const router = useRouter()
const isDev = import.meta.env.DEV
const hasError = ref(false)
const error = ref<Error | null>(null)

onErrorCaptured((err) => {
  hasError.value = true
  error.value = err instanceof Error ? err : new Error(String(err))
  return false
})

function retry() {
  hasError.value = false
  error.value = null
  router.go(0)
}
</script>

<template>
  <div v-if="hasError" class="error-boundary">
    <div class="error-boundary__icon">⚠️</div>
    <h2>Algo deu errado</h2>
    <p class="error-boundary__message">{{ error?.message }}</p>
    <p v-if="isDev" class="error-boundary__stack">{{ error?.stack }}</p>
    <button class="error-boundary__btn" @click="retry">Tentar novamente</button>
  </div>
  <RouterView v-else />
  <Toast />
  <ConfirmDialog />
  <ApiToastInit />
</template>

<style>
.error-boundary {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  gap: var(--spacing-md);
  padding: var(--spacing-xl);
  text-align: center;
}
.error-boundary__icon {
  font-size: 3rem;
}
.error-boundary h2 {
  font-family: var(--font-display);
  font-size: var(--text-xl);
  color: var(--color-text);
  margin: 0;
}
.error-boundary__message {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  max-width: 400px;
}
.error-boundary__stack {
  font-family: monospace;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  background: var(--color-bg-soft);
  padding: var(--spacing-sm);
  border-radius: var(--radius-sm);
  max-width: 600px;
  overflow: auto;
  max-height: 200px;
  text-align: left;
}
.error-boundary__btn {
  padding: var(--spacing-sm) var(--spacing-lg);
  border: none;
  border-radius: var(--radius-md);
  background: var(--color-primary);
  color: var(--color-primary-contrast);
  font-weight: 600;
  cursor: pointer;
  transition: background var(--duration-fast) ease;
}
.error-boundary__btn:hover {
  background: var(--color-primary-hover);
}
</style>

<style>
#app {
  min-height: 100vh;
}
</style>
