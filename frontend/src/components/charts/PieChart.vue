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
    /** Exibir percentual e valor nas fatias (dataLabels) */
    showDataLabels?: boolean
    /** Exibir toolbar com export (CSV, SVG, PNG) */
    showToolbar?: boolean
  }>(),
  {
    series: () => [],
    labels: () => [],
    colors: () => [],
    title: undefined,
    showDataLabels: true,
    showToolbar: false,
  }
)

const { baseOptions, palette, theme } = useApexChartTheme()

const chartSeries = computed(() => props.series.length ? props.series : [])

const labels = computed(() =>
  props.labels.length ? props.labels : chartSeries.value.map((_, i) => `Item ${i + 1}`)
)

const total = computed(() => chartSeries.value.reduce((a, b) => a + b, 0))

const chartOptions = computed<ApexOptions>(() => {
  const colors = props.colors.length ? props.colors : palette.value
  const t = theme.value
  return {
    ...baseOptions.value,
    chart: {
      ...baseOptions.value.chart,
      type: 'pie',
      background: 'transparent',
      toolbar: {
        show: props.showToolbar,
        offsetX: 0,
        offsetY: 0,
        tools: {
          download: true,
          selection: false,
          zoom: false,
          zoomin: false,
          zoomout: false,
          pan: false,
          reset: false,
        },
        export: { csv: { headerCategory: 'Categoria', headerValue: 'Valor' }, svg: {}, png: {} },
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800,
        animateGradually: { enabled: true, delay: 150 },
        dynamicAnimation: { enabled: true, speed: 400 },
      },
      dropShadow: {
        enabled: true,
        top: 2,
        left: 0,
        blur: 12,
        opacity: 0.15,
        color: t.textColor,
      },
    },
    colors: labels.value.map((_, i) => colors[i % colors.length]),
    labels: labels.value,
    stroke: { width: 2, colors: [t.background] },
    states: {
      hover: { filter: { type: 'lighten', value: 0.12 } as { type: string; value: number } },
      active: { filter: { type: 'darken', value: 0.2 } as { type: string; value: number } },
    },
    dataLabels: {
      enabled: props.showDataLabels,
      formatter: (val: number, opts: { w?: { globals?: { seriesTotals?: number[] } } }) => {
        const w = opts?.w
        const sum = w?.globals?.seriesTotals?.reduce((a, b) => a + b, 0) ?? total.value
        const pct = sum ? ((Number(val) / sum) * 100).toFixed(1) : '0'
        return `${Number(val).toFixed(1)}\n(${pct}%)`
      },
      style: { fontSize: t.fontSize, fontFamily: t.fontFamily },
      dropShadow: { enabled: false },
      offset: 4,
    },
    legend: {
      ...baseOptions.value.legend,
      show: true,
      position: 'bottom',
      fontSize: t.fontSize,
      markers: { size: 6, strokeWidth: 0 },
      itemMargin: { horizontal: 10, vertical: 6 },
    },
    plotOptions: {
      pie: {
        expandOnClick: true,
        expandRadius: 2,
        startAngle: -90,
        endAngle: 270,
        dataLabels: { offset: -8 },
      },
    },
    tooltip: {
      ...baseOptions.value.tooltip,
      fillSeriesColor: true,
      shared: true,
      followCursor: true,
      onDatasetHover: { highlightDataSeries: false },
      y: {
        formatter: (val: number, opts: { w?: { globals?: { seriesTotals?: number[] } }; seriesIndex?: number }) => {
          const w = opts?.w
          const sum = w?.globals?.seriesTotals?.reduce((a, b) => a + b, 0) ?? total.value
          const pct = sum ? ((Number(val) / sum) * 100).toFixed(1) : '0'
          const label = labels.value[opts?.seriesIndex ?? 0] ?? ''
          return `${label}\n${Number(val).toFixed(1)} (${pct}%)`
        },
        title: { formatter: () => 'Valor' },
      },
    },
    responsive: [
      { breakpoint: 520, options: { chart: { height: 300 }, legend: { fontSize: '11px' }, dataLabels: { offset: 2 } } },
      { breakpoint: 380, options: { chart: { height: 260 }, legend: { position: 'bottom', fontSize: '10px', markers: { size: 4 } } } },
    ],
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
