<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch, shallowRef } from 'vue'
import { use, init, type ECharts, type EChartsCoreOption } from 'echarts/core'
import { PieChart } from 'echarts/charts'
import { TooltipComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import Skeleton from 'primevue/skeleton'
import { useApexChartTheme } from '@/composables/useApexChartTheme'
import BarChart from '@/components/charts/BarChart.vue'
import type { TechnologyMetric } from '@/types/domain.types'

interface ChartSlice {
  label: string
  value: number
  color: string
}

const props = defineProps<{
  metrics?: TechnologyMetric[]
  loading?: boolean
}>()

use([PieChart, TooltipComponent, CanvasRenderer])

const currentType = ref<'polar' | 'bar'>('polar')
const roseChartRef = ref<HTMLElement | null>(null)
let roseChartInstance: ECharts | null = null

const barChartHeight = shallowRef(340)
function updateBarChartHeight() {
  barChartHeight.value = Math.min(Math.max(Math.round(window.innerHeight * 0.48), 280), 460)
}
onMounted(() => {
  updateBarChartHeight()
  window.addEventListener('resize', updateBarChartHeight, { passive: true })
})
onUnmounted(() => {
  window.removeEventListener('resize', updateBarChartHeight)
})

const { theme: chartTheme } = useApexChartTheme()

const fallbackColors = computed(() => {
  const p = chartTheme.value.palette
  return p.length > 6 ? p.slice(0, 6) : p
})

function toHours(minutes: number): number {
  const n = Number(minutes)
  if (!Number.isFinite(n) || n < 0) return 0
  return Math.round((n / 60) * 10) / 10
}

const topMetrics = computed(() =>
  [...(props.metrics ?? [])]
    .sort((a, b) => (b.total_minutes ?? 0) - (a.total_minutes ?? 0))
    .slice(0, 9)
)

const slices = computed<ChartSlice[]>(() => {
  const list = topMetrics.value
  if (!list.length) return []

  return list.map((m, i) => ({
    label: m.technology?.name ?? 'Sem nome',
    value: toHours(m.total_minutes ?? 0),
    color: m.technology?.color || fallbackColors.value[i % fallbackColors.value.length],
  }))
})

const totalValue = computed(() => slices.value.reduce((sum, item) => sum + item.value, 0))

const totalHoursLabel = computed(() => {
  const v = totalValue.value
  if (!Number.isFinite(v) || v <= 0) return '0h'
  if (v >= 1000) return `${(v / 1000).toFixed(1)}k h`
  return `${v.toFixed(1)}h`
})

const roseSeriesData = computed(() =>
  slices.value.map((s) => ({
    name: s.label,
    value: Math.max(0, s.value),
    itemStyle: { color: s.color },
  }))
)

function ensureRoseInstance(): boolean {
  const el = roseChartRef.value
  if (!el || currentType.value !== 'polar') return false

  const w = el.clientWidth
  const h = el.clientHeight
  if (w < 2 || h < 2) return false

  const dom = roseChartInstance?.getDom?.()
  if (roseChartInstance && dom !== el) {
    try {
      roseChartInstance.dispose()
    } catch {
      /* já descartado */
    }
    roseChartInstance = null
  }
  if (!roseChartInstance) {
    try {
      roseChartInstance = init(el)
    } catch {
      roseChartInstance = null
      return false
    }
  }
  return !!roseChartInstance
}

function renderRoseChart() {
  if (currentType.value !== 'polar' || !roseChartRef.value) return
  if (!ensureRoseInstance() || !roseChartInstance) return

  const data = roseSeriesData.value.filter((d) => Number.isFinite(d.value) && d.value >= 0)
  if (!data.length) return

  const rawTotal = Math.max(totalValue.value, 1e-6)
  const option: EChartsCoreOption = {
    animation: true,
    animationDuration: 700,
    animationEasing: 'quinticOut' as const,
    animationDelay: (idx: number) => idx * 45,
    animationDurationUpdate: 350,
    animationEasingUpdate: 'cubicOut' as const,
    tooltip: {
      trigger: 'item',
        formatter: (params: unknown) => {
        const p = params as { dataIndex?: number; name?: string; value?: number }
        const idx = p.dataIndex ?? 0
        const rawValue = typeof p.value === 'number' ? p.value : data[idx]?.value ?? 0
        const pct = ((rawValue / rawTotal) * 100).toFixed(1)
        return `${p.name ?? ''}<br/>${rawValue.toFixed(1)}h (${pct}%)`
      },
    },
    legend: { show: false },
    series: [
      {
        name: 'Tecnologias',
        type: 'pie',
        roseType: 'radius',
        radius: ['14%', '74%'],
        center: ['50%', '50%'],
        avoidLabelOverlap: true,
        selectedMode: false,
        itemStyle: {
          borderRadius: 5,
          borderColor: chartTheme.value.background,
          borderWidth: 2,
        },
        label: {
          show: true,
          color: chartTheme.value.textMuted,
          fontSize: 12,
          formatter: '{b}',
        },
        labelLine: {
          show: true,
          lineStyle: { color: chartTheme.value.gridColor, width: 1 },
          length: 8,
          length2: 12,
        },
        emphasis: { scale: false, label: { show: true } },
        data,
      },
    ],
  }

  try {
    roseChartInstance.setOption(option, true)
    roseChartInstance.resize()
  } catch (e) {
    console.warn('[TechDistributionWidget] ECharts polar:', e)
    try {
      roseChartInstance.dispose()
    } catch {
      /* ignore */
    }
    roseChartInstance = null
  }
}

const barChartData = computed(() => {
  const values = slices.value.map((s) => Number(s.value) || 0)
  const labels = slices.value.map((s) => s.label)
  const max = Math.max(...values, 0)
  const min = Math.min(...values, 0)
  const range = Math.max(max - min, 1)
  const scores = values.map((v) => (max === min ? 60 : Math.round(10 + ((v - min) / range) * 90)))
  return { labels, values, scores }
})

function onResize() {
  if (currentType.value === 'polar' && roseChartInstance) roseChartInstance.resize()
}

onMounted(() => {
  nextTick(() => {
    requestAnimationFrame(() => renderRoseChart())
  })
  window.addEventListener('resize', onResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', onResize)
  if (roseChartInstance) {
    roseChartInstance.dispose()
    roseChartInstance = null
  }
})

watch(currentType, () => {
  if (currentType.value === 'bar') {
    if (roseChartInstance) {
      try {
        roseChartInstance.dispose()
      } catch {
        /* ignore */
      }
      roseChartInstance = null
    }
  }
  if (currentType.value === 'polar') {
    nextTick(() => {
      requestAnimationFrame(() => renderRoseChart())
    })
  }
})

watch(
  () => [
    props.loading,
    slices.value.length,
    chartTheme.value.textColor,
    chartTheme.value.textMuted,
    chartTheme.value.gridColor,
    chartTheme.value.background,
  ],
  () => {
    if (props.loading || !slices.value.length || currentType.value !== 'polar') return
    nextTick(() => requestAnimationFrame(() => renderRoseChart()))
  },
  { immediate: true }
)
</script>

<template>
  <div class="tech-dist-widget">
    <div class="widget-header">
      <div class="widget-header__top">
        <h3 class="widget-title">Distribuição por tecnologia</h3>
        <span class="total-badge">{{ totalHoursLabel }}</span>
      </div>

      <div class="chart-type-toggle" role="group" aria-label="Tipo de gráfico">
        <button
          type="button"
          class="chart-type-toggle__btn"
          :class="{ active: currentType === 'polar' }"
          :aria-pressed="currentType === 'polar'"
          @click="currentType = 'polar'"
        >
          Polar
        </button>
        <button
          type="button"
          class="chart-type-toggle__btn"
          :class="{ active: currentType === 'bar' }"
          :aria-pressed="currentType === 'bar'"
          @click="currentType = 'bar'"
        >
          Barras
        </button>
      </div>
    </div>

    <div v-if="loading" class="chart-skeleton">
      <Skeleton v-for="i in 6" :key="i" height="1.25rem" class="skeleton-item" />
    </div>

    <div v-else-if="slices.length" class="chart-area">
      <Transition name="chart-switch" mode="out-in">
        <div v-if="currentType === 'polar'" key="polar" class="chart-wrap chart-wrap--svg">
          <div ref="roseChartRef" class="tech-pie-chart" />
        </div>
        <div v-else key="bar" class="chart-wrap chart-wrap--bar">
          <div class="bar-chart-slot">
            <BarChart
              :data="barChartData"
              orientation="horizontal"
              value-unit="h"
              x-axis-title="Horas"
              :show-data-labels="false"
              :show-toolbar="false"
              :gradient-fill="false"
              :chart-height="barChartHeight"
            />
          </div>
        </div>
      </Transition>

      <p class="hint">
        {{ currentType === 'polar' ? 'Toque ou passe o mouse nos setores para detalhes.' : 'Barras coloridas por intensidade relativa de horas.' }}
      </p>
    </div>
    <div v-else class="tech-dist-widget__empty">
      <p class="tech-dist-widget__empty-text">Nenhum dado ainda.</p>
      <p class="tech-dist-widget__empty-hint">Registre sessões de estudo para ver a distribuição por tecnologia.</p>
    </div>
  </div>
</template>

<style scoped>
.tech-dist-widget {
  --chart-stage-height: clamp(300px, 54vh, 500px);
  --chart-stage-min-height: 260px;
  background: var(--color-bg-card);
  border-radius: var(--widget-radius);
  padding: var(--widget-padding);
  padding-bottom: var(--spacing-lg);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  overflow: hidden;
  min-height: calc(var(--chart-stage-height) + 5rem);
  display: flex;
  flex-direction: column;
}

.widget-header {
  margin-bottom: var(--spacing-lg);
}

.widget-header__top {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-sm);
  flex-wrap: wrap;
}

.widget-title {
  font-size: var(--widget-title-size);
  font-weight: 700;
  margin: 0;
  color: var(--color-text);
}

.total-badge {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  background: var(--color-bg-soft);
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.chart-type-toggle {
  display: inline-flex;
  padding: var(--spacing-2xs);
  border-radius: var(--radius-md);
  background: var(--color-bg-soft);
  border: 1px solid var(--color-border);
  gap: var(--spacing-2xs);
}

.chart-type-toggle__btn {
  border: none;
  background: transparent;
  min-height: var(--input-height-sm);
  padding: var(--spacing-xs) var(--spacing-md);
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  border-radius: var(--radius-sm);
  cursor: pointer;
}

.chart-type-toggle__btn:hover {
  color: var(--color-text);
  background: var(--color-bg-card);
}

.chart-type-toggle__btn.active {
  background: var(--color-primary);
  color: var(--color-primary-contrast);
}
.chart-type-toggle__btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.chart-skeleton {
  min-height: var(--chart-stage-height);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-lg);
  justify-content: center;
}
.chart-skeleton--inline {
  min-height: var(--chart-stage-height);
  width: 100%;
}

.skeleton-item {
  width: 70%;
}

.chart-area {
  --chart-stage-size: var(--chart-stage-height);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  min-height: 0;
  flex: 1;
}

.chart-wrap {
  position: relative;
  border-radius: var(--radius-lg);
  overflow: hidden;
  background: var(--color-bg-soft);
  border: 1px solid var(--color-border);
  width: 100%;
  height: 100%;
  min-height: var(--chart-stage-size);
  max-height: none;
  flex: 1 1 auto;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chart-wrap--svg {
  padding: var(--spacing-sm);
  height: var(--chart-stage-height);
}

.chart-wrap--bar {
  padding: var(--spacing-sm);
  height: var(--chart-stage-height);
  align-items: stretch;
  justify-content: stretch;
}

.bar-chart-slot {
  width: 100%;
  height: 100%;
  margin: 0;
  display: flex;
  flex-direction: column;
}

.bar-chart-slot :deep(.bar-chart) {
  padding-top: 0;
}

.bar-chart-slot :deep(.bar-chart .score-scale) {
  justify-content: flex-start;
  margin-top: var(--spacing-xs);
}

.bar-chart-slot :deep(.bar-chart) {
  width: 100%;
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.bar-chart-slot :deep(.bar-chart .chart-wrap) {
  flex: 1;
  min-height: 0;
  height: 100% !important;
}

.tech-pie-chart {
  width: 100%;
  height: 100%;
  min-height: var(--chart-stage-min-height, 260px);
}

.hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: var(--spacing-xs) 0 0;
  line-height: var(--leading-snug);
  min-height: 1.25rem;
  text-align: center;
}

@media (max-width: 767px) {
  .tech-dist-widget {
    --chart-stage-height: clamp(250px, 50vh, 340px);
    min-height: calc(var(--chart-stage-height) + 7rem);
  }
}

@media (prefers-reduced-motion: no-preference) {
  .chart-switch-enter-active,
  .chart-switch-leave-active {
    transition: opacity var(--duration-normal) var(--ease-out-expo),
      transform var(--duration-normal) var(--ease-out-expo);
  }
  .chart-switch-enter-from,
  .chart-switch-leave-to {
    opacity: 0;
    transform: scale(0.985);
  }
}

.tech-dist-widget__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: var(--chart-stage-height);
  padding: var(--spacing-2xl);
  text-align: center;
}
.tech-dist-widget__empty-text {
  margin: 0;
  font-size: var(--text-base);
  font-weight: 600;
  color: var(--color-text-muted);
}
.tech-dist-widget__empty-hint {
  margin: var(--spacing-sm) 0 0;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
}
</style>
