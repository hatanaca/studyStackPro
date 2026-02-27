<script setup lang="ts">
import { computed } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  type ChartOptions
} from 'chart.js'
import { Line } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend)

const props = defineProps<{
  data?: { labels: string[]; values: number[] }
  title?: string
}>()

const chartData = computed(() => ({
  labels: props.data?.labels ?? [],
  datasets: [
    {
      label: 'Minutos',
      data: props.data?.values ?? [],
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      fill: true,
      tension: 0.3,
    },
  ],
}))

const options: ChartOptions<'line'> = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (ctx) => `${ctx.parsed.y} min`,
      },
    },
  },
  scales: {
    y: { beginAtZero: true },
  },
}
</script>

<template>
  <div class="line-chart">
    <h3
      v-if="title"
      class="chart-title"
    >
      {{ title }}
    </h3>
    <div
      v-if="data?.values?.length"
      class="chart-wrap"
    >
      <Line
        :data="chartData"
        :options="options"
      />
    </div>
    <div
      v-else
      class="chart-placeholder"
    >
      Sem dados
    </div>
  </div>
</template>

<style scoped>
.line-chart {
  background: #fff;
  border-radius: 0.5rem;
  padding: 1rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.chart-title {
  font-size: 0.875rem;
  margin-bottom: 0.75rem;
  color: #64748b;
}
.chart-wrap {
  min-height: 200px;
  position: relative;
}
.chart-placeholder {
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  font-size: 0.875rem;
}
</style>
