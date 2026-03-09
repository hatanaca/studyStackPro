<script setup lang="ts">
import { computed } from 'vue'
import type { ApexOptions } from 'apexcharts'
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
  }>(),
  { filled: true, valueUnit: 'minutes', ariaLabel: 'Gráfico de evolução', tall: false }
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

const chartOptions = computed<ApexOptions>(() => ({
  ...baseOptions.value,
  chart: {
    ...baseOptions.value.chart,
    type: 'area',
    background: 'transparent',
    toolbar: { show: false },
    zoom: { enabled: false },
    stacked: false,
    animations: {
      enabled: !prefersReducedMotion.value,
      easing: 'easeinout',
      speed: 400,
    },
  },
  colors: [theme.value.palette[0]],
  stroke: {
    curve: 'smooth',
    width: 2,
  },
  fill: {
    type: props.filled !== false ? 'gradient' : 'solid',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.25,
      opacityTo: 0.02,
      stops: [0, 90],
    },
  },
  xaxis: {
    ...baseOptions.value.xaxis,
    categories: props.data?.labels ?? [],
    tickAmount: props.data?.labels?.length
      ? Math.min(12, Math.max(4, Math.floor(props.data.labels.length / 2)))
      : undefined,
    labels: {
      rotate: -45,
      maxHeight: 60,
    },
  },
  yaxis: {
    ...baseOptions.value.yaxis,
    min: 0,
    labels: {
      formatter: (val: number) => valueFormatter(val),
    },
  },
  legend: { show: false },
  tooltip: {
    ...baseOptions.value.tooltip,
    y: { formatter: valueFormatter },
  },
}))
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
      <apexchart
        type="area"
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
@media (min-width: var(--screen-sm)) {
  .chart-wrap {
    height: var(--widget-chart-min-height);
    min-height: var(--widget-chart-min-height);
  }
  .line-chart--tall .chart-wrap {
    height: var(--widget-chart-min-height-tall);
    min-height: var(--widget-chart-min-height-tall);
  }
}
.apex-line {
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
