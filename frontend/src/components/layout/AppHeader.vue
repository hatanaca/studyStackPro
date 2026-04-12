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
  padding: 0 0 var(--spacing-lg);
  margin-bottom: var(--spacing-lg);
  border-bottom: 1px solid var(--color-border);
}
.app-header__title {
  font-family: var(--font-display);
  font-size: var(--text-lg);
  font-weight: 700;
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
  color: var(--color-text);
}
</style>
