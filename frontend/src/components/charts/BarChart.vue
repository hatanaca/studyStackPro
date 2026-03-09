<script setup lang="ts">
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
import { useApexChartTheme } from '@/composables/useApexChartTheme'

const props = withDefaults(
  defineProps<{
    data?: { labels: string[]; values: number[] }
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
  { valueUnit: 'min', showDataLabels: true, borderRadius: 6, showToolbar: false, gradientFill: true }
)

const { baseOptions, theme } = useApexChartTheme()

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
        speed: 600,
        animateGradually: { enabled: true, delay: 80 },
        dynamicAnimation: { enabled: true, speed: 350 },
      },
      dropShadow: {
        enabled: true,
        top: 2,
        left: 0,
        blur: 8,
        opacity: 0.12,
        color: t.textColor,
      },
      zoom: { enabled: false },
      selection: { enabled: false },
    },
    colors: props.gradientFill
      ? undefined
      : [t.palette[0]],
    fill: props.gradientFill
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
        columnWidth: '55%',
        barHeight: '70%',
        borderRadius: props.borderRadius,
        borderRadiusApplication: 'end',
        borderRadiusWhenStacked: 'last',
        dataLabels: {
          position: isHorizontal ? 'center' : ('top' as const),
          hideOverflowingLabels: true,
          maxItems: 100,
        },
        distributed: false,
        rangeBarOverlap: false,
        ...(props.gradientFill ? { colors: { ranges: [{ from: 0, to: 1e9, color: t.palette[0] }] } } : {}),
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
      yaxis: {
        lines: {
          show: true,
          strokeDashArray: 4,
        },
      },
      padding: { top: 10, right: 10, bottom: 0, left: 8 },
    },
    xaxis: {
      ...baseOptions.value.xaxis,
      categories: props.data?.labels ?? [],
      tickAmount: undefined,
      max: undefined,
      title: props.xAxisTitle ? { text: props.xAxisTitle, style: { color: t.textMuted, fontSize: t.fontSize } } : undefined,
      labels: {
        ...baseOptions.value.xaxis?.labels,
        rotate: isHorizontal ? 0 : -45,
        maxHeight: 80,
        trim: true,
      },
      crosshairs: { show: true, width: 1, position: 'back', opacity: 0.3, stroke: { width: 0, color: t.gridColor } },
    },
    yaxis: {
      min: 0,
      tickAmount: 5,
      forceNiceScale: true,
      title: props.yAxisTitle ? { text: props.yAxisTitle, style: { color: t.textMuted, fontSize: t.fontSize } } : undefined,
      labels: {
        style: { colors: t.textMuted, fontSize: t.fontSizeAxis },
        formatter: (val: number) => String(Math.round(val)),
      },
      crosshairs: { show: true, position: 'back', stroke: { width: 1, color: t.gridColor, dashArray: 4 } },
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
    annotations: (() => {
      const vals = props.data?.values ?? []
      if (vals.length < 2) return undefined
      const avg = vals.reduce((a, b) => a + b, 0) / vals.length
      return {
        yaxis: [
          {
            y: avg,
            borderColor: t.textMuted,
            strokeDashArray: 4,
            borderWidth: 1,
            label: {
              borderColor: t.textMuted,
              style: { color: t.textMuted, fontSize: t.fontSize },
              text: `Média: ${Math.round(avg)} ${props.valueUnit}`,
              position: 'right',
            },
          },
        ],
      }
    })(),
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
