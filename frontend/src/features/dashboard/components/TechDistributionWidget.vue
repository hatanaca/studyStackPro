<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'
import Skeleton from 'primevue/skeleton'
import { useApexChartTheme } from '@/composables/useApexChartTheme'
import { useUiStore } from '@/stores/ui.store'
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

const currentType = ref<'polar' | 'bar'>('polar')

const viewport = ref({ w: 1024, h: 800 })
function syncViewport() {
  if (typeof window === 'undefined') return
  viewport.value = { w: window.innerWidth, h: window.innerHeight }
}
onMounted(() => {
  syncViewport()
  window.addEventListener('resize', syncViewport, { passive: true })
})
onUnmounted(() => {
  window.removeEventListener('resize', syncViewport)
})

/** Altura do gráfico de barras: sobe no mobile conforme N categorias (evita área vazia + barras minúsculas). */
const barChartHeight = computed(() => {
  const n = Math.max(slices.value.length, 1)
  const { w, h } = viewport.value
  const narrow = w < 640
  const base = Math.min(Math.max(Math.round(h * 0.45), 260), 480)
  const perBar = narrow ? 50 : 42
  const minByCategories = n * perBar + 108
  return Math.min(narrow ? 680 : 580, Math.max(base, minByCategories))
})

const { theme: chartTheme } = useApexChartTheme()
const uiStore = useUiStore()

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

const polarSeries = computed(() => slices.value.map((s) => s.value))

const polarOptions = computed<ApexOptions>(() => {
  const colors = slices.value.map((s) => s.color)
  const labels = slices.value.map((s) => s.label)
  const total = Math.max(totalValue.value, 1e-6)

  return {
    chart: {
      type: 'polarArea',
      background: 'transparent',
      fontFamily: chartTheme.value.fontFamily,
      toolbar: { show: false },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 600,
        dynamicAnimation: { enabled: true, speed: 350 },
      },
    },
    labels,
    colors,
    stroke: {
      width: 1,
      colors: [chartTheme.value.background],
    },
    fill: {
      opacity: 0.85,
    },
    plotOptions: {
      polarArea: {
        rings: { strokeWidth: 0 },
        spokes: { strokeWidth: 0.5 },
      },
    },
    yaxis: { show: false },
    legend: {
      show: true,
      position: 'bottom',
      fontSize: chartTheme.value.fontSize,
      fontFamily: chartTheme.value.fontFamily,
      labels: { colors: chartTheme.value.textMuted },
      markers: { size: 5, offsetX: -2 },
      itemMargin: { horizontal: 8, vertical: 4 },
    },
    dataLabels: { enabled: false },
    tooltip: {
      theme: uiStore.theme === 'dark' ? 'dark' : 'light',
      style: { fontSize: chartTheme.value.fontSize, fontFamily: chartTheme.value.fontFamily },
      y: {
        formatter: (val: number) => {
          const pct = ((val / total) * 100).toFixed(1)
          return `${val.toFixed(1)}h (${pct}%)`
        },
      },
    },
    responsive: [
      {
        breakpoint: 640,
        options: {
          legend: { position: 'bottom', fontSize: '11px' },
        },
      },
    ],
  }
})

const polarChartHeight = computed(() =>
  Math.min(Math.max(Math.round(viewport.value.h * 0.44), 260), 420)
)

const barChartData = computed(() => {
  const values = slices.value.map((s) => Number(s.value) || 0)
  const labels = slices.value.map((s) => s.label)
  const max = Math.max(...values, 0)
  const min = Math.min(...values, 0)
  const range = Math.max(max - min, 1)
  const scores = values.map((v) => (max === min ? 60 : Math.round(10 + ((v - min) / range) * 90)))
  return { labels, values, scores }
})
</script>

<template>
  <div
    class="tech-dist-widget"
    :class="{ 'tech-dist-widget--bar-mode': currentType === 'bar' }"
  >
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
        <div v-if="currentType === 'polar'" key="polar" class="chart-wrap chart-wrap--polar">
          <VueApexCharts
            type="polarArea"
            :height="polarChartHeight"
            :options="polarOptions"
            :series="polarSeries"
            class="apex-polar"
          />
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

.tech-dist-widget--bar-mode {
  --chart-stage-height: clamp(340px, min(56vh, 600px), 620px);
  overflow-x: hidden;
  overflow-y: visible;
  min-height: calc(var(--chart-stage-height) + 5rem);
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

.chart-wrap--polar {
  padding: var(--spacing-sm);
  height: var(--chart-stage-height);
}

/* Barras horizontais: sem clip no SVG (eixo X, grade e última barra). */
.chart-wrap--bar {
  overflow: visible;
  align-items: flex-start;
  justify-content: flex-start;
  padding: var(--spacing-sm);
  height: var(--chart-stage-height);
}

.apex-polar {
  width: 100%;
  height: 100%;
  display: block;
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
  width: 100%;
  height: 100%;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.bar-chart-slot :deep(.bar-chart .score-scale) {
  justify-content: flex-start;
  margin-top: var(--spacing-xs);
}

.bar-chart-slot :deep(.bar-chart .chart-wrap) {
  flex: 1 1 auto;
  min-height: min(var(--widget-chart-min-height-sm), 100%);
  height: auto;
}

.hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: var(--spacing-xs) 0 0;
  line-height: var(--leading-snug);
  min-height: 1.25rem;
  text-align: center;
}

/* Abaixo de --screen-md (768px) */
@media (max-width: calc(768px - 1px)) {
  .tech-dist-widget {
    --chart-stage-height: clamp(250px, 50vh, 340px);
    min-height: calc(var(--chart-stage-height) + 7rem);
  }
  .tech-dist-widget--bar-mode {
    --chart-stage-height: clamp(300px, min(68vh, 620px), 640px);
    min-height: calc(var(--chart-stage-height) + 6.5rem);
  }
  .tech-dist-widget--bar-mode .chart-wrap--bar {
    height: auto;
    min-height: var(--chart-stage-height);
    max-height: none;
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
