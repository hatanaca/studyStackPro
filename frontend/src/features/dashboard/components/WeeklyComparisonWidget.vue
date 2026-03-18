<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue'
const BarChart = defineAsyncComponent(() => import('@/components/charts/BarChart.vue'))
import EmptyState from '@/components/ui/EmptyState.vue'
import Skeleton from 'primevue/skeleton'
import { formatShortDate } from '@/utils/formatters'
import { useAnalyticsStore } from '@/stores/analytics.store'

const analyticsStore = useAnalyticsStore()

function resolveWeeklyScore(entry: Record<string, unknown>): number | undefined {
  const candidateKeys = ['score', 'focus_score', 'avg_focus_score', 'study_score']
  for (const key of candidateKeys) {
    const value = entry[key]
    if (typeof value === 'number' && Number.isFinite(value)) return value
  }
  return undefined
}

const chartData = computed(() => {
  const data = analyticsStore.weeklyComparison
  if (!data?.length) return undefined
  const sorted = [...data].reverse()
  const scores = sorted.map((d) => resolveWeeklyScore(d as unknown as Record<string, unknown>))
  const hasValidScores = scores.every((s): s is number => typeof s === 'number')
  return {
    labels: sorted.map((d) => formatShortDate(d.week_start)),
    values: sorted.map((d) => d.total_minutes),
    ...(hasValidScores ? { scores } : {}),
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
    <EmptyState
      v-else-if="!chartData || !chartData.values?.length"
      title="Nenhuma comparação semanal"
      description="Registre sessões ao longo das semanas para ver o gráfico de comparação."
      icon="📊"
      :hide-action="true"
    />
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
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
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
  min-height: var(--widget-chart-min-height);
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) 0;
}
.chart-skeleton :deep(.p-skeleton) {
  border-radius: var(--radius-sm);
}
.skeleton-bar {
  width: 80%;
}
@media (max-width: 640px) {
  .weekly-widget {
    padding: var(--widget-padding-sm);
  }
}
</style>
