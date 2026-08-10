<script setup lang="ts">
/**
 * @module FunnelChart
 *
 * Visualização de funil de conversão usando barra horizontal com valores decrescentes.
 * ApexCharts não tem funnel nativo — usa bar horizontal com gradiente de opacidade.
 */
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'
import { useApexChartTheme } from '@/composables/useApexChartTheme'
import { useMediaQuery } from '@/composables/useMediaQuery'

const props = withDefaults(
  defineProps<{
    title?: string
    data: { label: string; value: number }[]
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

const sortedData = computed(() => [...props.data].sort((a, b) => b.value - a.value))

const chartOptions = computed<ApexOptions>(() => ({
  ...baseOptions.value,
  chart: {
    ...baseOptions.value.chart,
    type: 'bar',
    toolbar: { show: props.showToolbar },
    animations: {
      enabled: !prefersReducedMotion.value,
      speed: 800,
      easing: 'easeout',
    },
    fontFamily: theme.value.fontFamily,
    background: 'transparent',
  },
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: 4,
      borderRadiusApplication: 'end',
      barHeight: '70%',
      distributed: true,
    },
  },
  colors: props.colors ?? palette.value.slice(0, sortedData.value.length),
  dataLabels: {
    enabled: true,
    position: 'top',
    style: {
      fontSize: '12px',
      fontWeight: 600,
      fontFamily: theme.value.fontFamily,
      colors: [theme.value.textColor],
    },
    formatter: (val: number) => {
      if (val >= 60) {
        const h = Math.floor(val / 60)
        const m = val % 60
        return m > 0 ? `${h}h ${m}min` : `${h}h`
      }
      return `${val}min`
    },
  },
  xaxis: {
    categories: sortedData.value.map((d) => d.label),
    labels: {
      style: {
        colors: theme.value.textMuted,
        fontSize: theme.value.fontSize,
        fontFamily: theme.value.fontFamily,
      },
    },
    max: sortedData.value[0]?.value ?? 100,
  },
  yaxis: {
    labels: {
      style: {
        colors: theme.value.textMuted,
        fontSize: theme.value.fontSize,
        fontFamily: theme.value.fontFamily,
      },
    },
  },
  grid: {
    ...baseOptions.value.grid,
    xaxis: { lines: { show: false } },
  },
  tooltip: {
    ...baseOptions.value.tooltip,
    y: {
      formatter: (val: number) => {
        if (val >= 60) {
          const h = Math.floor(val / 60)
          const m = val % 60
          return m > 0 ? `${h}h ${m}min` : `${h}h`
        }
        return `${val} min`
      },
    },
  },
  legend: { show: false },
  responsive: [],
}))

const series = computed(() => [
  {
    name: 'Valor',
    data: sortedData.value.map((d) => d.value),
  },
])
</script>

<template>
  <div class="funnel-chart">
    <h3 v-if="title" class="funnel-chart__title">{{ title }}</h3>
    <div class="funnel-chart__body">
      <VueApexCharts
        type="bar"
        :height="chartHeight"
        width="100%"
        :options="chartOptions"
        :series="series"
      />
    </div>
  </div>
</template>

<style scoped>
.funnel-chart {
  /* Card handled by parent ChartPanel */
}
.funnel-chart__title {
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  color: var(--widget-title-color);
  margin: 0 0 var(--spacing-sm);
}
.funnel-chart__body {
  min-height: var(--widget-chart-min-height-sm);
}
</style>
