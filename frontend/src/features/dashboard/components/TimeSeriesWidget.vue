<script setup lang="ts">
import { computed } from 'vue'
import LineChart from '@/components/charts/LineChart.vue'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
import { formatShortDate } from '@/utils/formatters'
import { useAnalyticsStore } from '@/stores/analytics.store'
import PeriodSelector from './PeriodSelector.vue'

const analyticsStore = useAnalyticsStore()

const chartData = computed(() => {
  const data = analyticsStore.timeSeries
  if (!data?.length) return undefined
  return {
    labels: data.map((d) => formatShortDate(d.date)),
    values: data.map((d) => d.total_minutes),
  }
})

const periodLabel = computed(() => {
  const map = { '7d': '7 dias', '30d': '30 dias', '90d': '90 dias' }
  return map[analyticsStore.selectedPeriod]
})
</script>

<template>
  <div class="time-series-widget">
    <div class="header">
      <h3 class="title">
        Minutos por dia ({{ periodLabel }})
      </h3>
      <PeriodSelector
        :model-value="analyticsStore.selectedPeriod"
        @update:model-value="
          (p) => {
            analyticsStore.setSelectedPeriod(p)
            const hasData = analyticsStore.timeSeriesData[p]?.length
            if (!hasData) analyticsStore.fetchTimeSeries(p)
          }
        "
      />
    </div>
    <div
      v-if="analyticsStore.timeSeriesLoading || analyticsStore.isRecalculating"
      class="chart-skeleton"
    >
      <SkeletonLoader
        v-for="i in 8"
        :key="i"
        height="1.25rem"
        class="skeleton-line"
      />
    </div>
    <LineChart
      v-else
      :data="chartData"
    />
  </div>
</template>

<style scoped>
.time-series-widget {
  background: var(--color-bg-card);
  border-radius: var(--widget-radius);
  padding: var(--widget-padding);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  overflow: hidden;
  min-height: var(--widget-chart-min-height-sm);
}
@media (min-width: 640px) {
  .time-series-widget {
    min-height: var(--widget-chart-min-height);
  }
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-xs);
}
.title {
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  color: var(--widget-title-color);
  margin: 0;
}
.chart-skeleton {
  min-height: var(--widget-chart-min-height-sm);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-md) 0;
}
.skeleton-line {
  width: 90%;
}
@media (max-width: 480px) {
  .time-series-widget {
    padding: var(--widget-padding-sm);
  }
  .header {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
