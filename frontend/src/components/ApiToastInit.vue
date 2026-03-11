<script setup lang="ts">
/**
 * Inicializa a integração API → Toast. Registra callback no apiClient para que
 * interceptors (401, 429) possam exibir toasts. Monta após Toast do PrimeVue.
 */
import { onMounted } from 'vue'
import { setApiToast } from '@/api/client'
import { useToast } from '@/composables/useToast'

const { success, error, info } = useToast()

onMounted(() => {
  setApiToast((msg, type = 'success') => {
    if (type === 'error') error(msg)
    else if (type === 'info') info(msg)
    else success(msg)
  })
})
</script>

<template>
  <!-- Montado após Toast para que useToast() encontre o provide do PrimeVue -->
  <span class="api-toast-init-root" aria-hidden="true" />
</template>

<style scoped>
.api-toast-init-root {
  display: none;
}
</style>
