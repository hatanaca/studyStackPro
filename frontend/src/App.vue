<script setup lang="ts">
import { RouterView } from 'vue-router'
import BaseToast from '@/components/ui/BaseToast.vue'
</script>

<template>
  <RouterView />
  <BaseToast />
</template>

<style>
#app {
  min-height: 100vh;
}
</style>
