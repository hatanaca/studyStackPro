<script setup lang="ts">
import { computed } from 'vue'
import BarChart from '@/components/charts/BarChart.vue'
import Skeleton from 'primevue/skeleton'
import { formatShortDate } from '@/utils/formatters'
import { useAnalyticsStore } from '@/stores/analytics.store'

const analyticsStore = useAnalyticsStore()

const chartData = computed(() => {
  const data = analyticsStore.weeklyComparison
  if (!data?.length) return undefined
  const sorted = [...data].reverse()
  return {
    labels: sorted.map((d) => formatShortDate(d.week_start)),
    values: sorted.map((d) => d.total_minutes),
  }
})
</script>

<template>
  <div class="weekly-widget">
    <h3 class="title">
      Comparação semanal
    </h3>
    <div
      v-if="analyticsStore.weeklyLoading"
      class="chart-skeleton"
    >
      <Skeleton
        v-for="i in 6"
        :key="i"
        height="1.5rem"
        class="skeleton-bar"
      />
    </div>
    <BarChart
      v-else
      :data="chartData"
    />
  </div>
</template>

<style scoped>
.weekly-widget {
  background: var(--color-bg-card);
  border-radius: var(--widget-radius);
  padding: var(--widget-padding);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  overflow: hidden;
  min-height: var(--widget-chart-min-height-sm);
}
@media (min-width: 640px) {
  .weekly-widget {
    min-height: var(--widget-chart-min-height);
  }
}
.title {
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  color: var(--widget-title-color);
  margin: 0 0 var(--spacing-sm);
}
.chart-skeleton {
  min-height: var(--widget-chart-min-height-sm);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-md) 0;
}
.skeleton-bar {
  width: 80%;
}
@media (max-width: 480px) {
  .weekly-widget {
    padding: var(--widget-padding-sm);
  }
}
</style>
