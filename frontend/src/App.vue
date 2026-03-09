<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
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
  <RouterView />
  <Toast />
  <ConfirmDialog />
</template>

<style>
#app {
  min-height: 100vh;
}
</style>
