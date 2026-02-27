<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import BarChart from '@/components/charts/BarChart.vue'
import { formatShortDate } from '@/utils/formatters'
import { analyticsApi } from '@/api/modules/analytics.api'

interface WeeklyData {
  week_start: string
  total_minutes: number
  session_count: number
}

const data = ref<WeeklyData[]>([])
const loading = ref(false)

const chartData = computed(() => {
  if (!data.value?.length) return undefined
  const sorted = [...data.value].reverse()
  return {
    labels: sorted.map((d) => formatShortDate(d.week_start)),
    values: sorted.map((d) => d.total_minutes),
  }
})

onMounted(async () => {
  loading.value = true
  try {
    const res = await analyticsApi.getWeekly()
    const payload = res.data?.data
    if (res.data?.success && Array.isArray(payload)) data.value = payload
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="weekly-widget">
    <h3 class="title">
      Comparação semanal
    </h3>
    <div
      v-if="loading"
      class="chart-skeleton"
    />
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
