<template>
  <div class="chart-skeleton" v-bind="$attrs">
    <Skeleton width="100%" :height="height" style="border-radius: var(--radius-lg, 0.75rem)" />
  </div>
</template>

<script setup lang="ts">
import Skeleton from 'primevue/skeleton'

withDefaults(defineProps<{ height?: string }>(), {
  height: '280px',
})
</script>

<style scoped>
.chart-skeleton {
  width: 100%;
}
</style>
