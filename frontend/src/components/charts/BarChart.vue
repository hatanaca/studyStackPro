<script setup lang="ts">
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'
import { useApexChartTheme } from '@/composables/useApexChartTheme'
import { useMediaQuery } from '@/composables/useMediaQuery'

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
    /** Altura explícita do gráfico (px ou string, ex.: 340 ou "100%") */
    chartHeight?: number | string
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
    chartHeight: 340,
  }
)

const { baseOptions, theme } = useApexChartTheme()
const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)')
/** Evita Apex `responsive` nativo: em viewports estreitas ele reaplica Config várias vezes e pode corromper yaxis.title. */
const compactViewport = useMediaQuery('(max-width: 639px)')
const tinyViewport = useMediaQuery('(max-width: 479px)')

const resolvedChartHeight = computed(() => {
  const h = props.chartHeight
  if (typeof h === 'string') return h

  const isHorizontal = props.orientation === 'horizontal'
  const n = props.data?.values?.length ?? 0

  // Barras horizontais: nunca comprimir abaixo do espaço por categoria (evita “faixa” no topo).
  if (isHorizontal && n > 0) {
    const per = tinyViewport.value ? 46 : compactViewport.value ? 44 : 40
    // Espaço extra para eixo X, título e linhas de grade (evita “fatiar” em 100% zoom).
    const pad = tinyViewport.value ? 130 : compactViewport.value ? 140 : 150
    const floor = Math.min(n * per + pad, 680)
    return Math.max(h, floor)
  }

  if (tinyViewport.value) return Math.min(h, 240)
  if (compactViewport.value) return Math.min(h, 280)
  return h
})

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
  const chartHeight = resolvedChartHeight.value
  const yLabelMaxW = tinyViewport.value ? 88 : compactViewport.value ? 108 : 160
  const yLabelFont = tinyViewport.value ? '10px' : compactViewport.value ? '11px' : t.fontSizeAxis
  const xTitleText = props.xAxisTitle ?? ''
  const barH = isHorizontal ? (compactViewport.value ? '82%' : '72%') : '75%'
  return {
    ...baseOptions.value,
    chart: {
      ...baseOptions.value.chart,
      type: 'bar',
      height: chartHeight,
      background: 'transparent',
      toolbar: {
        show: props.showToolbar,
        offsetX: 0,
        offsetY: 0,
        tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false },
        export: { csv: { headerCategory: 'Categoria', headerValue: 'Valor' }, svg: {}, png: {} },
      },
      animations: {
        enabled: !prefersReducedMotion.value,
        easing: 'easeinout',
        speed: prefersReducedMotion.value ? 0 : 650,
        animateGradually: {
          enabled: !prefersReducedMotion.value,
          delay: prefersReducedMotion.value ? 0 : 40,
        },
        dynamicAnimation: {
          enabled: !prefersReducedMotion.value,
          speed: prefersReducedMotion.value ? 0 : 400,
        },
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
        barHeight: barH,
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
      xaxis: {
        lines: {
          show: isHorizontal,
          strokeDashArray: isHorizontal ? 3 : 0,
        },
      },
      yaxis: { lines: { show: true, strokeDashArray: 2 } },
      padding: {
        top: isHorizontal ? 18 : 12,
        right: isHorizontal ? (compactViewport.value ? 10 : 18) : 12,
        bottom: isHorizontal ? (tinyViewport.value ? 36 : compactViewport.value ? 44 : 52) : 36,
        left: isHorizontal ? (compactViewport.value ? 4 : 10) : 12,
      },
    },
    xaxis: {
      ...baseOptions.value.xaxis,
      categories: props.data?.labels ?? [],
      tickAmount: undefined,
      max: undefined,
      axisBorder: { show: isHorizontal, color: t.gridColor },
      axisTicks: { show: isHorizontal, color: t.gridColor },
      // ApexCharts acessa yaxis/xaxis title.text sem optional chaining — title deve ser objeto com text string.
      title: {
        text: xTitleText,
        offsetY: isHorizontal ? 4 : 0,
        style: {
          color: t.textMuted,
          fontSize: compactViewport.value && isHorizontal ? '10px' : t.fontSize,
          fontFamily: t.fontFamily,
        },
      },
      labels: {
        ...baseOptions.value.xaxis?.labels,
        show: true,
        rotate: isHorizontal ? 0 : -40,
        maxHeight: 72,
        trim: true,
        offsetY: isHorizontal ? 4 : 0,
      },
      crosshairs: { show: false },
    },
    // Array com um eixo: o Apex faz merge via extendArray e exige yaxe.title definido
    // (getyAxisTitleCoords usa yaxe.title.text sem checar title — title ausente quebra).
    yaxis: [
      {
        show: true,
        ...(isHorizontal ? {} : { min: 0, tickAmount: 5, forceNiceScale: true }),
        title: {
          text: props.yAxisTitle ?? '',
          rotate: isHorizontal ? 0 : -90,
          offsetY: 0,
          offsetX: 0,
          style: {
            color: t.textMuted,
            fontSize: t.fontSize,
            fontFamily: t.fontFamily,
            fontWeight: 600,
          },
        },
        labels: {
          maxWidth: isHorizontal ? yLabelMaxW : 160,
          trim: true,
          style: { colors: t.textMuted, fontSize: yLabelFont },
          align: isHorizontal ? 'left' : 'right',
          offsetX: isHorizontal ? (compactViewport.value ? -4 : -10) : 0,
          formatter: (val: number | string) => (isHorizontal ? String(val) : String(Math.round(Number(val)))),
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
        crosshairs: { show: false },
        tooltip: { enabled: false },
      },
    ],
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
    responsive: [],
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
        :height="resolvedChartHeight"
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
@media (min-width: 640px) {
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
