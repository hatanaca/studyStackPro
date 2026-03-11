<script setup lang="ts">
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'
import { useApexChartTheme } from '@/composables/useApexChartTheme'
import { useMediaQuery } from '@/composables/useMediaQuery'
import { formatMinutesToHoursLabel } from '@/utils/formatters'

const props = withDefaults(
  defineProps<{
    data?: { labels: string[]; values: number[] }
    title?: string
    /** true = area preenchida (padrão), false = só linha */
    filled?: boolean
    /** Unidade de exibição no eixo Y e tooltip: 'minutes' = "X min", 'hours' = "Xh Ymin" */
    valueUnit?: 'minutes' | 'hours'
    /** Aria-label do container do gráfico */
    ariaLabel?: string
    /** Usar altura maior no desktop (--widget-chart-min-height-tall) */
    tall?: boolean
    /** Preset visual do gráfico */
    preset?: 'default' | 'stock'
  }>(),
  {
    data: undefined,
    title: undefined,
    filled: true,
    valueUnit: 'minutes',
    ariaLabel: 'Gráfico de evolução',
    tall: false,
    preset: 'default',
  }
)

const { baseOptions, theme } = useApexChartTheme()
const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)')

const series = computed(() => [
  {
    name: props.valueUnit === 'hours' ? 'Horas' : 'Minutos',
    data: props.data?.values ?? [],
  },
])

const valueFormatter = (val: number): string =>
  props.valueUnit === 'hours' ? formatMinutesToHoursLabel(val) : `${val} min`

const chartOptions = computed<ApexOptions>(() => {
  const categories = props.data?.labels ?? []
  const isStockPreset = props.preset === 'stock'
  const styleReader = globalThis.getComputedStyle?.bind(globalThis)
  const stockColor = typeof document !== 'undefined'
    ? (styleReader?.(document.documentElement)
      .getPropertyValue('--chart-line-stock-color')
      .trim() || '#1e88e5')
    : '#1e88e5'
  return {
  ...baseOptions.value,
  chart: {
    ...baseOptions.value.chart,
    type: 'area',
    height: '100%',
    background: 'transparent',
    toolbar: {
      show: isStockPreset,
      tools: {
        download: true,
        selection: false,
        zoom: false,
        zoomin: false,
        zoomout: false,
        pan: false,
        reset: false,
      },
    },
    zoom: { enabled: false },
    stacked: false,
    animations: {
      enabled: !prefersReducedMotion.value,
      easing: 'easeinout',
      speed: 400,
    },
  },
  colors: [isStockPreset ? stockColor : theme.value.palette[0]],
  stroke: {
    curve: 'smooth',
    width: isStockPreset ? 3.5 : 2,
    lineCap: 'round',
  },
  fill: {
    type: isStockPreset ? 'gradient' : (props.filled !== false ? 'gradient' : 'solid'),
    opacity: isStockPreset ? 1 : 1,
    gradient: isStockPreset
      ? {
          shadeIntensity: 0,
          opacityFrom: 0.26,
          opacityTo: 0.08,
          stops: [0, 100],
          colorStops: [
            { offset: 0, color: stockColor, opacity: 0.3 },
            { offset: 100, color: stockColor, opacity: 0.07 },
          ],
        }
      : {
          shadeIntensity: 1,
          opacityFrom: 0.25,
          opacityTo: 0.02,
          stops: [0, 90],
        },
  },
  markers: {
    size: isStockPreset ? 0 : 3,
    hover: { sizeOffset: 2 },
  },
  grid: {
    ...baseOptions.value.grid,
    padding: { left: isStockPreset ? 4 : 8, right: isStockPreset ? 8 : 16, top: isStockPreset ? 6 : 12, bottom: isStockPreset ? 8 : 40 },
    xaxis: { lines: { show: false } },
    yaxis: { lines: { show: true, strokeDashArray: isStockPreset ? 0 : 2 } },
  },
  xaxis: {
    ...baseOptions.value.xaxis,
    categories,
    tickPlacement: isStockPreset ? 'on' : 'between',
    tickAmount: categories.length
      ? Math.min(12, Math.max(4, Math.floor(categories.length / 2)))
      : undefined,
    labels: {
      rotate: isStockPreset ? 0 : -40,
      maxHeight: isStockPreset ? 32 : 56,
      style: { fontSize: theme.value.fontSizeAxis },
    },
    axisBorder: { show: isStockPreset ? false : true },
    axisTicks: { show: isStockPreset ? false : true },
  },
  yaxis: {
    ...baseOptions.value.yaxis,
    min: 0,
    opposite: isStockPreset,
    tickAmount: isStockPreset ? 4 : undefined,
    labels: {
      formatter: (val: number) =>
        isStockPreset ? String(Math.round(val)) : valueFormatter(val),
    },
  },
  legend: { show: false },
  dataLabels: { enabled: false },
  tooltip: {
    ...baseOptions.value.tooltip,
    shared: true,
    intersect: false,
    y: { formatter: valueFormatter },
  },
}
})
</script>

<template>
  <div
    class="line-chart"
    :class="{ 'line-chart--tall': tall }"
    role="img"
    :aria-label="ariaLabel"
  >
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
      <VueApexCharts
        type="area"
        height="100%"
        :options="chartOptions"
        :series="series"
        class="apex-line"
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
  --line-chart-height: var(--widget-chart-min-height-sm);
  background: transparent;
  border-radius: var(--radius-md);
  padding: 0;
  min-height: var(--widget-chart-min-height-sm);
  height: 100%;
  display: flex;
  flex-direction: column;
}
.chart-title {
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  margin-bottom: var(--spacing-sm);
  color: var(--widget-title-color);
}
.chart-wrap {
  height: 100%;
  min-height: var(--line-chart-height);
  position: relative;
  flex: 1 1 auto;
  padding-bottom: 8px;
}
.line-chart--tall .chart-wrap {
  min-height: var(--widget-chart-min-height-tall);
}
@media (min-width: var(--screen-sm)) {
  .line-chart {
    --line-chart-height: var(--widget-chart-min-height);
  }
  .chart-wrap {
    min-height: var(--line-chart-height);
  }
}
.apex-line {
  width: 100%;
  height: 100%;
  display: block;
}
.chart-placeholder {
  flex: 1 1 auto;
  min-height: var(--widget-chart-min-height-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
}
</style>
