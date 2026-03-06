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
  padding: 0 0 var(--spacing-md);
  margin-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--color-border);
}
.app-header__title {
  font-size: var(--text-lg);
  font-weight: 600;
  color: var(--color-text);
}
</style>
