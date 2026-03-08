<script setup lang="ts">
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import { useApexChartTheme } from '@/composables/useApexChartTheme'

const props = withDefaults(
  defineProps<{
    series?: number[]
    labels?: string[]
    colors?: string[]
    title?: string
  }>(),
  { series: () => [], labels: () => [], colors: () => [] }
)

const { baseOptions, palette, theme } = useApexChartTheme()

const chartSeries = computed(() => props.series.length ? props.series : [])

const labels = computed(() =>
  props.labels.length ? props.labels : chartSeries.value.map((_, i) => `Item ${i + 1}`)
)

const chartOptions = computed<ApexOptions>(() => {
  const colors = props.colors.length ? props.colors : palette.value
  return {
    ...baseOptions.value,
    chart: {
      ...baseOptions.value.chart,
      type: 'pie',
      background: 'transparent',
    },
    colors: labels.value.map((_, i) => colors[i % colors.length]),
    labels: labels.value,
    legend: {
      ...baseOptions.value.legend,
      show: true,
      position: 'bottom',
    },
    dataLabels: { enabled: false },
    stroke: { width: 2, colors: [theme.value.background] },
    plotOptions: {
      pie: {
        expandOnClick: true,
      },
    },
    tooltip: {
      ...baseOptions.value.tooltip,
      y: { formatter: (val: number) => String(val) },
    },
  }
})
</script>

<template>
  <div class="pie-chart">
    <h3
      v-if="title"
      class="chart-title"
    >
      {{ title }}
    </h3>
    <div
      v-if="chartSeries.length"
      class="chart-wrap"
    >
      <apexchart
        type="pie"
        :options="chartOptions"
        :series="chartSeries"
        class="apex-pie"
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
.pie-chart {
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
  min-height: var(--widget-chart-min-height-sm);
  position: relative;
}
@media (min-width: 640px) {
  .chart-wrap {
    min-height: var(--widget-chart-min-height);
  }
}
.apex-pie {
  width: 100%;
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
