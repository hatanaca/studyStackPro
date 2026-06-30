<script setup lang="ts">
/**
 * @module RadarChart
 *
 * Gráfico radar/spider para perfil multi-eixo de estudo.
 * Renderiza múltiplas séries sobre eixos radiais.
 */
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'
import { useApexChartTheme } from '@/composables/useApexChartTheme'
import { useMediaQuery } from '@/composables/useMediaQuery'

const props = withDefaults(
  defineProps<{
    title?: string
    series: { name: string; data: number[] }[]
    labels: string[]
    colors?: string[]
    showToolbar?: boolean
    chartHeight?: number
  }>(),
  {
    title: undefined,
    colors: undefined,
    showToolbar: false,
    chartHeight: 280,
  }
)

const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)')
const { baseOptions, theme, palette } = useApexChartTheme()

const chartOptions = computed<ApexOptions>(() => ({
  ...baseOptions.value,
  chart: {
    ...baseOptions.value.chart,
    type: 'radar',
    toolbar: { show: props.showToolbar },
    animations: {
      enabled: !prefersReducedMotion.value,
      speed: 800,
      easing: 'easeout',
    },
    fontFamily: theme.value.fontFamily,
    background: 'transparent',
  },
  colors: props.colors ?? palette.value,
  stroke: { width: 2, curve: 'smooth' },
  fill: { opacity: 0.15 },
  markers: { size: 4, hover: { size: 6 } },
  xaxis: {
    categories: props.labels,
    labels: {
      style: {
        colors: theme.value.textMuted,
        fontSize: theme.value.fontSize,
        fontFamily: theme.value.fontFamily,
      },
    },
  },
  yaxis: {
    show: false,
    max: 100,
  },
  tooltip: {
    ...baseOptions.value.tooltip,
    shared: true,
    intersect: false,
  },
  legend: {
    show: props.series.length > 1,
    position: 'bottom',
    labels: { colors: theme.value.textMuted },
  },
  responsive: [],
}))

const series = computed(() => props.series)
</script>

<template>
  <div class="radar-chart">
    <h3 v-if="title" class="radar-chart__title">{{ title }}</h3>
    <div class="radar-chart__body">
      <VueApexCharts
        type="radar"
        :height="chartHeight"
        width="100%"
        :options="chartOptions"
        :series="series"
      />
    </div>
  </div>
</template>

<style scoped>
.radar-chart {
  /* Card handled by parent ChartPanel */
}
.radar-chart__title {
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  color: var(--widget-title-color);
  margin: 0 0 var(--spacing-sm);
}
.radar-chart__body {
  min-height: var(--widget-chart-min-height-sm);
}
</style>
