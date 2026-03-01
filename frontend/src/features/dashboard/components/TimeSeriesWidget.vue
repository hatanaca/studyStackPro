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
  background: #fff;
  border-radius: 0.5rem;
  padding: 1rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.title {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0 0 0.75rem;
}
.chart-skeleton {
  min-height: 200px;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 1rem 0;
}
.skeleton-line {
  width: 90%;
}
</style>
