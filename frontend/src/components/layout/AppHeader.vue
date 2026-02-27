<script setup lang="ts">
defineProps<{
  title?: string
}>()
</script>

<template>
  <header class="app-header">
    <h2
      v-if="title"
      class="app-header__title"
    >
      {{ title }}
    </h2>
    <slot />
  </header>
</template>

<style scoped>
.app-header {
  padding: 0 0 1rem;
  margin-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}
.app-header__title {
  font-size: 1.25rem;
  color: #1e293b;
}
</style>
