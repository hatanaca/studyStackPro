<script setup lang="ts">
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'
import { useApexChartTheme } from '@/composables/useApexChartTheme'

const props = withDefaults(
  defineProps<{
    data?: { labels: string[]; values: number[]; scores?: number[] }
    title?: string
    /** Orientação: vertical (padrão) ou horizontal */
    orientation?: 'vertical' | 'horizontal'
    /** Unidade no eixo/tooltip (ex.: "min", "h") */
    valueUnit?: string
    /** Exibir valor em cima das barras */
    showDataLabels?: boolean
    /** Barras com bordas arredondadas (0 = reto) */
    borderRadius?: number
    /** Exibir toolbar com export */
    showToolbar?: boolean
    /** Preencher barras com gradiente em vez de cor sólida */
    gradientFill?: boolean
    /** Título do eixo X (opcional) */
    xAxisTitle?: string
    /** Título do eixo Y (opcional) */
    yAxisTitle?: string
  }>(),
  {
    data: undefined,
    title: undefined,
    orientation: 'vertical',
    valueUnit: 'min',
    showDataLabels: true,
    borderRadius: 6,
    showToolbar: false,
    gradientFill: true,
    xAxisTitle: undefined,
    yAxisTitle: undefined,
  }
)

const { baseOptions, theme } = useApexChartTheme()

type Rgb = { r: number; g: number; b: number }

const SCORE_MIN = 10
const SCORE_MAX = 100
const SCORE_LOW_COLOR: Rgb = { r: 101, g: 181, b: 129 } // #65B581
const SCORE_MID_COLOR: Rgb = { r: 255, g: 206, b: 52 } // #FFCE34
const SCORE_HIGH_COLOR: Rgb = { r: 253, g: 102, b: 95 } // #FD665F

const hasScoreData = computed(() => {
  const scores = props.data?.scores ?? []
  const values = props.data?.values ?? []
  return scores.length > 0 && scores.length === values.length
})

const clamp = (value: number, min: number, max: number): number => Math.min(Math.max(value, min), max)

const mix = (from: Rgb, to: Rgb, factor: number): Rgb => ({
  r: Math.round(from.r + (to.r - from.r) * factor),
  g: Math.round(from.g + (to.g - from.g) * factor),
  b: Math.round(from.b + (to.b - from.b) * factor),
})

const rgbToHex = ({ r, g, b }: Rgb): string =>
  `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`

const colorByScore = (score: number): string => {
  const safeScore = clamp(score, SCORE_MIN, SCORE_MAX)
  const ratio = (safeScore - SCORE_MIN) / (SCORE_MAX - SCORE_MIN)
  const midpoint = 0.5

  if (ratio <= midpoint) {
    const factor = ratio / midpoint
    return rgbToHex(mix(SCORE_LOW_COLOR, SCORE_MID_COLOR, factor))
  }

  const factor = (ratio - midpoint) / midpoint
  return rgbToHex(mix(SCORE_MID_COLOR, SCORE_HIGH_COLOR, factor))
}

const scoreColors = computed(() => (props.data?.scores ?? []).map((score) => colorByScore(score)))

const series = computed(() => [
  {
    name: props.valueUnit === 'h' ? 'Horas' : 'Minutos',
    data: props.data?.values ?? [],
  },
])

const chartOptions = computed<ApexOptions>(() => {
  const isHorizontal = props.orientation === 'horizontal'
  const t = theme.value
  const unit = props.valueUnit || 'min'
  return {
    ...baseOptions.value,
    chart: {
      ...baseOptions.value.chart,
      type: 'bar',
      background: 'transparent',
      toolbar: {
        show: props.showToolbar,
        offsetX: 0,
        offsetY: 0,
        tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false },
        export: { csv: { headerCategory: 'Categoria', headerValue: 'Valor' }, svg: {}, png: {} },
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 500,
        animateGradually: { enabled: true, delay: 60 },
        dynamicAnimation: { enabled: true, speed: 300 },
      },
      dropShadow: { enabled: false },
      zoom: { enabled: false },
      selection: { enabled: false },
    },
    colors: hasScoreData.value ? scoreColors.value : [t.palette[0]],
    fill: hasScoreData.value
      ? { type: 'solid' }
      : props.gradientFill
      ? {
          type: 'gradient',
          gradient: {
            shade: 'light',
            type: 'vertical',
            shadeIntensity: 0.4,
            opacityFrom: 0.9,
            opacityTo: 0.7,
            stops: [0, 100],
            colorStops: [
              { offset: 0, color: t.palette[0], opacity: 1 },
              { offset: 100, color: t.palette[0], opacity: 0.75 },
            ],
          },
        }
      : undefined,
    plotOptions: {
      bar: {
        horizontal: isHorizontal,
        columnWidth: '60%',
        barHeight: isHorizontal ? '72%' : '75%',
        borderRadius: props.borderRadius,
        borderRadiusApplication: 'end',
        borderRadiusWhenStacked: 'last',
        dataLabels: {
          position: isHorizontal ? 'center' : ('top' as const),
          hideOverflowingLabels: true,
          maxItems: 100,
        },
        distributed: hasScoreData.value,
        rangeBarOverlap: false,
        ...(!hasScoreData.value && props.gradientFill
          ? { colors: { ranges: [{ from: 0, to: 1e9, color: t.palette[0] }] } }
          : {}),
      },
    },
    dataLabels: {
      enabled: props.showDataLabels,
      formatter: (val: number) => `${val} ${unit}`,
      style: { fontSize: t.fontSize, fontFamily: t.fontFamily },
      offsetY: isHorizontal ? 0 : -4,
      dropShadow: { enabled: false },
    },
    grid: {
      ...baseOptions.value.grid,
      xaxis: { lines: { show: false } },
      yaxis: { lines: { show: true, strokeDashArray: 2 } },
      padding: {
        top: isHorizontal ? 4 : 12,
        right: 12,
        bottom: isHorizontal ? 12 : 36,
        left: isHorizontal ? 4 : 12,
      },
    },
    xaxis: {
      ...baseOptions.value.xaxis,
      categories: props.data?.labels ?? [],
      tickAmount: undefined,
      max: undefined,
      title: props.xAxisTitle ? { text: props.xAxisTitle, style: { color: t.textMuted, fontSize: t.fontSize } } : undefined,
      labels: {
        ...baseOptions.value.xaxis?.labels,
        rotate: isHorizontal ? 0 : -40,
        maxHeight: 72,
        trim: true,
      },
      crosshairs: { show: false },
    },
    yaxis: {
      ...(isHorizontal ? {} : { min: 0, tickAmount: 5, forceNiceScale: true }),
      title: props.yAxisTitle ? { text: props.yAxisTitle, style: { color: t.textMuted, fontSize: t.fontSize } } : undefined,
      labels: {
        style: { colors: t.textMuted, fontSize: t.fontSizeAxis },
        align: isHorizontal ? 'left' : 'right',
        offsetX: isHorizontal ? -10 : 0,
        formatter: (val: number | string) => (isHorizontal ? String(val) : String(Math.round(Number(val)))),
      },
      crosshairs: { show: false },
    },
    legend: { show: false },
    tooltip: {
      ...baseOptions.value.tooltip,
      shared: true,
      intersect: false,
      x: { show: true, format: 'dd/MM' },
      y: {
        formatter: (val: number) => `${val} ${unit}`,
        title: { formatter: () => (props.valueUnit === 'h' ? 'Horas' : 'Minutos') },
      },
    },
    responsive: [
      { breakpoint: 640, options: { chart: { height: 280 }, xaxis: { title: undefined } } },
      { breakpoint: 480, options: { chart: { height: 260 }, dataLabels: { style: { fontSize: '10px' } }, plotOptions: { bar: { columnWidth: '65%' } } } },
      { breakpoint: 360, options: { chart: { height: 240 }, legend: { fontSize: '10px' } } },
    ],
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
      <VueApexCharts
        type="bar"
        :options="chartOptions"
        :series="series"
        class="apex-bar"
      />
    </div>
    <div v-else-if="!data?.values?.length" class="chart-placeholder">
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
  min-height: var(--widget-chart-min-height-sm);
  position: relative;
  flex: 1 1 auto;
  padding-bottom: 4px;
}
@media (min-width: var(--screen-sm)) {
  .chart-wrap {
    min-height: var(--widget-chart-min-height);
  }
}
.apex-bar {
  width: 100%;
  height: 100%;
  display: block;
}
.apex-bar :deep(.apexcharts-canvas) {
  margin: 0;
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
