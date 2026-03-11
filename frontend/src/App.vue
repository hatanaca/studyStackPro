<script setup lang="ts">
/**
 * Componente raiz da aplicação.
 * Renderiza RouterView, Toast global, ConfirmDialog e ApiToastInit (integração API → toast).
 */
import { RouterView } from 'vue-router'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import ApiToastInit from '@/components/ApiToastInit.vue'
</script>

<template>
  <RouterView />
  <Toast />
  <ConfirmDialog />
  <ApiToastInit />
</template>

<style>
#app {
  min-height: 100vh;
}
</style>
