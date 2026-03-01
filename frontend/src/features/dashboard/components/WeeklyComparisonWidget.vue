<script setup lang="ts">
import { computed } from 'vue'
import BarChart from '@/components/charts/BarChart.vue'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
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
      <SkeletonLoader
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
  background: #fff;
  border-radius: 0.5rem;
  padding: 1rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.title {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.75rem;
}
.chart-skeleton {
  min-height: 200px;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 1rem 0;
}
.skeleton-bar {
  width: 80%;
}
</style>
