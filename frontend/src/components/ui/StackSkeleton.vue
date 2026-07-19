<template>
  <div class="stack-skeleton" v-bind="$attrs">
    <Skeleton
      v-for="(row, i) in rows"
      :key="i"
      :width="row.width || '100%'"
      :height="row.height || '1rem'"
      class="stack-skeleton__bar"
    />
  </div>
</template>

<script setup lang="ts">
import Skeleton from 'primevue/skeleton'

withDefaults(
  defineProps<{
    rows?: Array<{ width?: string; height?: string }>
  }>(),
  {
    rows: () => [
      { width: '38%', height: '1rem' },
      { width: '100%', height: '10rem' },
      { width: '100%', height: '6rem' },
    ],
  }
)
</script>

<style scoped>
.stack-skeleton {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm, 0.5rem);
}
.stack-skeleton__bar {
  border-radius: var(--radius-sm);
}
</style>
