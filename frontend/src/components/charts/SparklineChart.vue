<script setup lang="ts">
/**
 * @module SparklineChart
 *
 * Sparkline inline para cards KPI e métricas compactas.
 * Renderiza uma linha/área mínima sem eixos, labels ou toolbar.
 */
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'
import { useApexChartTheme } from '@/composables/useApexChartTheme'

const props = withDefaults(
  defineProps<{
    data: number[]
    color?: string
    type?: 'line' | 'area'
    height?: number
    width?: number | string
  }>(),
  {
    color: undefined,
    type: 'area',
    height: 40,
    width: '100%',
  }
)

const { theme } = useApexChartTheme()

const chartColor = computed(() => props.color ?? theme.value.palette[0])

const chartOptions = computed<ApexOptions>(() => ({
  chart: {
    type: 'area',
    sparkline: { enabled: true },
    animations: { enabled: true, speed: 800 },
  },
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.4,
      opacityTo: 0.05,
      stops: [0, 100],
    },
  },
  colors: [chartColor.value],
  tooltip: { enabled: false },
}))

const series = computed(() => [{
  name: '',
  data: props.data,
}])
</script>

<template>
  <div class="sparkline-chart" :style="{ width: typeof width === 'number' ? `${width}px` : width }">
    <VueApexCharts
      v-if="data.length"
      :type="type"
      :height="height"
      :width="typeof width === 'number' ? width : '100%'"
      :options="chartOptions"
      :series="series"
    />
  </div>
</template>

<style scoped>
.sparkline-chart {
  display: inline-block;
  vertical-align: middle;
}
</style>
