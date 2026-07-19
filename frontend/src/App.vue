<script setup lang="ts">
/**
 * Componente raiz da aplicação.
 * Renderiza RouterView, Toast global, ConfirmDialog e ApiToastInit (integração API → toast).
 */
import { ref, onErrorCaptured } from 'vue'
import { RouterView } from 'vue-router'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import ApiToastInit from '@/components/ApiToastInit.vue'

const hasError = ref(false)
const error = ref<Error | null>(null)

onErrorCaptured((err) => {
  hasError.value = true
  error.value = err instanceof Error ? err : new Error(String(err))
  return false
})
</script>

<template>
  <div v-if="hasError" class="error-boundary">
    <h2>Algo deu errado</h2>
    <p>{{ error?.message }}</p>
    <button
      @click="
        hasError = false
        error = null
      "
    >
      Tentar novamente
    </button>
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
  gap: 1rem;
  font-family: system-ui, sans-serif;
}
.error-boundary button {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  background: var(--color-primary, #3b82f6);
  color: white;
  cursor: pointer;
}
</style>

<style>
#app {
  min-height: 100vh;
}
</style>
