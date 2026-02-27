<script setup lang="ts">
import { computed } from 'vue'
import LineChart from '@/components/charts/LineChart.vue'
import { formatShortDate } from '@/utils/formatters'
import type { DailyMinute } from '@/types/domain.types'

const props = defineProps<{
  data: DailyMinute[]
  loading?: boolean
}>()

const chartData = computed(() => {
  if (!props.data?.length) return undefined
  return {
    labels: props.data.map((d) => formatShortDate(d.date)),
    values: props.data.map((d) => d.total_minutes),
  }
})
</script>

<template>
  <div class="time-series-widget">
    <h3 class="title">
      Minutos por dia (30 dias)
    </h3>
    <div
      v-if="loading"
      class="chart-skeleton"
    />
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
.title {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.75rem;
}
.chart-skeleton {
  min-height: 200px;
  background: #f1f5f9;
  border-radius: 0.25rem;
  animation: pulse 1.5s ease-in-out infinite;
}
@keyframes pulse {
  50% {
    opacity: 0.7;
  }
}
</style>
