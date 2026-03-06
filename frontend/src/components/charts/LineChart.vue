<script setup lang="ts">
import { computed } from 'vue'
import 'chart.js/auto'
import { Chart, Filler } from 'chart.js'
import type { ChartOptions } from 'chart.js'
import { Line } from 'vue-chartjs'
import { useChartTheme } from '@/composables/useChartTheme'

Chart.register(Filler)

const props = defineProps<{
  data?: { labels: string[]; values: number[] }
  title?: string
}>()

const { themeColors } = useChartTheme()

const chartData = computed(() => {
  const { primary, primaryFill } = themeColors.value
  return {
    labels: props.data?.labels ?? [],
    datasets: [
      {
        label: 'Minutos',
        data: props.data?.values ?? [],
        borderColor: primary,
        backgroundColor: primaryFill,
        fill: true,
        tension: 0.3,
      },
    ],
  }
})

const options = computed<ChartOptions<'line'>>(() => {
  const { textColor, gridColor } = themeColors.value
  return {
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: (ctx) => `${ctx.parsed.y} min`,
        },
      },
    },
    scales: {
      x: {
        grid: { color: gridColor },
        ticks: { color: textColor },
      },
      y: {
        beginAtZero: true,
        grid: { color: gridColor },
        ticks: { color: textColor },
      },
    },
  }
})
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
  background: transparent;
  border-radius: var(--radius-md);
  padding: 0;
  min-height: var(--widget-chart-min-height-sm);
}
.chart-title {
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  margin-bottom: var(--spacing-sm);
  color: var(--widget-title-color);
}
.chart-wrap {
  height: var(--widget-chart-min-height-sm);
  min-height: var(--widget-chart-min-height-sm);
  position: relative;
}
@media (min-width: 640px) {
  .chart-wrap {
    height: var(--widget-chart-min-height);
    min-height: var(--widget-chart-min-height);
  }
}
.chart-placeholder {
  min-height: var(--widget-chart-min-height-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
}
</style>
