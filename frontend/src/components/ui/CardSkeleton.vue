<template>
  <div class="card-skeleton" v-bind="$attrs">
    <Skeleton :width="titleWidth" height="1rem" class="card-skeleton__title" />
    <Skeleton :width="contentWidth" :height="contentHeight" class="card-skeleton__content" />
  </div>
</template>

<script setup lang="ts">
import Skeleton from 'primevue/skeleton'

withDefaults(defineProps<{
  titleWidth?: string
  contentWidth?: string
  contentHeight?: string
}>(), {
  titleWidth: '45%',
  contentWidth: '7rem',
  contentHeight: '2rem',
})
</script>

<style scoped>
.card-skeleton {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm, 0.5rem);
  padding: var(--spacing-lg, 1rem);
  background: var(--color-bg-card, #1c1c1f);
  border-radius: var(--radius-lg, 0.75rem);
}
.card-skeleton__title { border-radius: var(--radius-sm); }
.card-skeleton__content { border-radius: var(--radius-sm); }
</style>
