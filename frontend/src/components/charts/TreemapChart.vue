<script setup lang="ts">
/**
 * @module TreemapChart
 *
 * Gráfico treemap para visualização de distribuição proporcional.
 * Mostra retângulos proporcionais ao valor, útil para tecnologias × tempo.
 */
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'
import { useApexChartTheme } from '@/composables/useApexChartTheme'
import { useMediaQuery } from '@/composables/useMediaQuery'

const props = withDefaults(
  defineProps<{
    title?: string
    series: { x: string; y: number; color?: string }[]
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
    type: 'treemap',
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
  plotOptions: {
    treemap: {
      distributed: true,
      enableShades: false,
    },
  },
  dataLabels: {
    enabled: true,
    style: {
      fontSize: '12px',
      fontWeight: 600,
      fontFamily: theme.value.fontFamily,
    },
    formatter: (val: number) => `${val}%`,
  },
  tooltip: {
    ...baseOptions.value.tooltip,
    y: {
      formatter: (val: number) => `${val} min`,
    },
  },
  legend: { show: false },
  responsive: [],
}))

const series = computed(() => [{
  data: props.series,
}])
</script>

<template>
  <div class="treemap-chart">
    <h3 v-if="title" class="treemap-chart__title">{{ title }}</h3>
    <div class="treemap-chart__body">
      <VueApexCharts
        type="treemap"
        :height="chartHeight"
        width="100%"
        :options="chartOptions"
        :series="series"
      />
    </div>
  </div>
</template>

<style scoped>
.treemap-chart {
  /* Card handled by parent ChartPanel */
}
.treemap-chart__title {
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  color: var(--widget-title-color);
  margin: 0 0 var(--spacing-sm);
}
.treemap-chart__body {
  min-height: var(--widget-chart-min-height-sm);
}
</style>
