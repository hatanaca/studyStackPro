<script setup lang="ts">
import { computed } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend,
  type ChartOptions
} from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

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
      backgroundColor: 'rgba(59, 130, 246, 0.7)',
      borderColor: '#3b82f6',
      borderWidth: 1,
    },
  ],
}))

const options: ChartOptions<'bar'> = {
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
  <div class="bar-chart">
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
      <Bar
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
.bar-chart {
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
