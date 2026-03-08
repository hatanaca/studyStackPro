<script setup lang="ts">
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import { useApexChartTheme } from '@/composables/useApexChartTheme'

const props = defineProps<{
  data?: { labels: string[]; values: number[] }
  title?: string
  /** Orientação: vertical (padrão) ou horizontal */
  orientation?: 'vertical' | 'horizontal'
}>()

const { baseOptions, theme } = useApexChartTheme()

const series = computed(() => [
  {
    name: 'Minutos',
    data: props.data?.values ?? [],
  },
])

const chartOptions = computed<ApexOptions>(() => {
  const isHorizontal = props.orientation === 'horizontal'
  return {
    ...baseOptions.value,
    chart: {
      ...baseOptions.value.chart,
      type: 'bar',
      background: 'transparent',
      toolbar: { show: false },
    },
    colors: [theme.value.palette[0]],
    plotOptions: {
      bar: {
        horizontal: isHorizontal,
        columnWidth: '60%',
        borderRadius: 4,
        dataLabels: { position: 'top' as const },
      },
    },
    dataLabels: {
      enabled: false,
      formatter: (val: number) => `${val} min`,
    },
    xaxis: {
      ...baseOptions.value.xaxis,
      categories: props.data?.labels ?? [],
    },
    yaxis: {
      ...baseOptions.value.yaxis,
      min: 0,
    },
    legend: { show: false },
    tooltip: {
      ...baseOptions.value.tooltip,
      y: { formatter: (val: number) => `${val} min` },
    },
  }
})
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
      <apexchart
        type="bar"
        :options="chartOptions"
        :series="series"
        class="apex-bar"
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
.apex-bar {
  width: 100%;
  height: 100%;
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
