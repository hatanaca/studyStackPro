<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
import {
  Chart as ChartJS,
  ArcElement,
  BarController,
  BarElement,
  Tooltip,
  Legend,
  type ChartOptions
} from 'chart.js'
import { Pie, Doughnut, Bar } from 'vue-chartjs'
import { useUiStore } from '@/stores/ui.store'
import type { TechnologyMetric } from '@/types/domain.types'

ChartJS.register(ArcElement, BarController, BarElement, Tooltip, Legend)

const props = defineProps<{
  metrics?: TechnologyMetric[]
  loading?: boolean
}>()

const router = useRouter()
const uiStore = useUiStore()
const showTechList = ref(true)
const currentType = ref<'pie' | 'doughnut' | 'bar'>('pie')

/* Paleta vibrante com boa leitura em claro e escuro */
const FALLBACK_COLORS = [
  '#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6',
  '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#0ea5e9',
  '#14b8a6', '#e11d48', '#a855f7', '#d946ef', '#22c55e',
  '#eab308', '#64748b', '#fb923c', '#7c3aed', '#2dd4bf',
]

function toHours(minutes: number): number {
  const n = Number(minutes)
  if (!Number.isFinite(n) || n < 0) return 0
  return Math.round((n / 60) * 10) / 10
}

const sorted = computed(() =>
  [...(props.metrics ?? [])].sort((a, b) => (b.total_minutes ?? 0) - (a.total_minutes ?? 0))
)

const chartData = computed(() => {
  if (!sorted.value.length) return { labels: [] as string[], datasets: [] as { data: number[]; backgroundColor: string[]; borderWidth: number; borderColor: string }[] }

  const labels = sorted.value.map((m) => m.technology?.name ?? 'Sem nome')
  const data = sorted.value.map((m) => toHours(m.total_minutes ?? 0))
  const colors = sorted.value.map(
    (m, i) => m.technology?.color || FALLBACK_COLORS[i % FALLBACK_COLORS.length]
  )

  const borderColor = uiStore.isDarkMode ? 'rgba(30, 41, 59, 0.7)' : 'rgba(255, 255, 255, 0.95)'
  const isPieOrDoughnut = currentType.value === 'pie' || currentType.value === 'doughnut'
  return {
    labels,
    datasets: [
      {
        label: 'Horas',
        data,
        backgroundColor: colors,
        borderWidth: 2,
        borderColor,
        ...(isPieOrDoughnut && {
          borderRadius: 6,
        }),
      }
    ]
  }
})

const chartComponent = computed(() => {
  if (currentType.value === 'doughnut') return Doughnut
  if (currentType.value === 'bar') return Bar
  return Pie
})

function getTooltipHours(ctx: { parsed: unknown; dataset?: { data?: unknown[] }; label?: string }): number {
  const p = ctx.parsed
  if (typeof p === 'number' && Number.isFinite(p)) return p
  if (p && typeof p === 'object' && 'x' in (p as object)) return Number((p as { x: number }).x)
  if (p && typeof p === 'object' && 'y' in (p as object)) return Number((p as { y: number }).y)
  const idx = ctx.dataset?.data?.length
  if (idx != null && ctx.dataset?.data?.[idx] != null) return Number(ctx.dataset.data[idx])
  return 0
}

const options = computed(() => {
  const isBar = currentType.value === 'bar'
  const isDark = uiStore.isDarkMode
  const gridColor = isDark ? 'rgba(148, 163, 184, 0.15)' : 'rgba(0, 0, 0, 0.06)'
  const tickColor = isDark ? '#94a3b8' : '#64748b'
  const isPieOrDoughnut = currentType.value === 'pie' || currentType.value === 'doughnut'
  return {
    responsive: true,
    maintainAspectRatio: false,
    animation: false,
    layout: { padding: { top: 8, right: 12, bottom: 8, left: 12 } },
    ...(isPieOrDoughnut && { spacing: 4 }),
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: isDark ? 'rgba(15, 23, 42, 0.95)' : 'rgba(30, 41, 59, 0.95)',
        titleColor: isDark ? '#e2e8f0' : '#fff',
        bodyColor: isDark ? '#cbd5e1' : '#e2e8f0',
        padding: 12,
        cornerRadius: 8,
        titleFont: { size: 12, weight: 600 },
        bodyFont: { size: 13 },
        callbacks: {
          label: (ctx) => {
            const hours = getTooltipHours(ctx as Parameters<typeof getTooltipHours>[0])
            const arr = (ctx.dataset?.data ?? []) as number[]
            const total = arr.reduce((s, v) => s + (Number.isFinite(Number(v)) ? Number(v) : 0), 0)
            const pct = total > 0 ? ((hours / total) * 100).toFixed(1) : '0'
            return ` ${ctx.label}: ${hours}h (${pct}%)`
          },
        },
      },
    },
    onClick: (_, elements) => {
      if (elements[0] && sorted.value[elements[0].index]) {
        const tech = sorted.value[elements[0].index]
        router.push(`/sessions?technology_id=${tech.technology?.id}`)
      }
    },
    ...(isBar
      ? {
          indexAxis: 'y' as const,
          scales: {
            x: {
              beginAtZero: true,
              grid: { color: gridColor },
              ticks: {
                color: tickColor,
                callback: (value: unknown) => (Number.isFinite(Number(value)) ? `${value}h` : String(value)),
              },
            },
            y: {
              grid: { display: false },
              ticks: { color: tickColor },
            },
          },
        }
      : {}),
  } as ChartOptions
})

const totalHours = computed(() => {
  if (!sorted.value.length) return 0
  return sorted.value.reduce((s, m) => s + toHours(m.total_minutes ?? 0), 0)
})

const totalHoursLabel = computed(() => {
  const value = totalHours.value
  if (!Number.isFinite(value)) return '0h'
  if (value >= 1000) {
    const k = Math.round((value / 1000) * 10) / 10
    return `${k.toFixed(1)}k h`
  }
  return `${value.toFixed(1)}h`
})

function goToTech(metric: TechnologyMetric) {
  if (metric.technology?.id) {
    router.push(`/sessions?technology_id=${metric.technology.id}`)
  }
}
</script>

<template>
  <div class="tech-dist-widget">
    <div class="widget-header">
      <div class="widget-header__top">
        <h3 class="widget-title">
          Distribuição por tecnologia
        </h3>
        <span class="total-badge">{{ totalHoursLabel }}</span>
      </div>
      <div class="chart-type-toggle">
        <button
          type="button"
          class="chart-type-toggle__btn"
          :class="{ active: currentType === 'pie' }"
          @click="currentType = 'pie'"
        >
          Pizza
        </button>
        <button
          type="button"
          class="chart-type-toggle__btn"
          :class="{ active: currentType === 'doughnut' }"
          @click="currentType = 'doughnut'"
        >
          Rosquinha
        </button>
        <button
          type="button"
          class="chart-type-toggle__btn"
          :class="{ active: currentType === 'bar' }"
          @click="currentType = 'bar'"
        >
          Barras
        </button>
      </div>
    </div>

    <div
      v-if="loading"
      class="chart-skeleton"
    >
      <SkeletonLoader
        v-for="i in 6"
        :key="i"
        height="1.25rem"
        class="skeleton-item"
      />
    </div>

    <template v-else-if="chartData.labels.length">
      <div class="chart-and-list">
        <div class="chart-area">
          <div class="chart-wrap">
            <component
              :is="chartComponent"
              :data="chartData"
              :options="(options as Record<string, unknown>)"
            />
          </div>
          <p class="hint">
            Clique em uma fatia ou barra para ver sessões
          </p>
        </div>
        <aside class="tech-panel">
          <button
            type="button"
            class="tech-panel-toggle"
            :aria-expanded="showTechList"
            @click="showTechList = !showTechList"
          >
            <span>Tecnologias ({{ sorted.length }})</span>
            <span class="tech-panel-toggle__icon">{{ showTechList ? '▼' : '▶' }}</span>
          </button>
          <Transition name="slide">
            <div
              v-show="showTechList"
              class="tech-list"
            >
              <button
                v-for="(m, i) in sorted"
                :key="m.technology?.id ?? i"
                type="button"
                class="tech-list-item"
                @click="goToTech(m)"
              >
                <span
                  class="tech-list-item__dot"
                  :style="{ background: m.technology?.color || FALLBACK_COLORS[i % FALLBACK_COLORS.length] }"
                />
                <span class="tech-list-item__name">{{ m.technology?.name ?? 'Sem nome' }}</span>
                <span class="tech-list-item__hours">{{ toHours(m.total_minutes ?? 0) }}h</span>
              </button>
            </div>
          </Transition>
        </aside>
      </div>
    </template>

    <p
      v-else
      class="empty"
    >
      Nenhum dado ainda. Registre sessões para ver a distribuição.
    </p>
  </div>
</template>

<style scoped>
.tech-dist-widget {
  background: var(--color-bg-card);
  border-radius: var(--widget-radius);
  padding: var(--widget-padding);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  overflow: hidden;
  min-height: var(--widget-chart-min-height);
  max-height: 520px;
  display: flex;
  flex-direction: column;
}

.widget-header {
  margin-bottom: var(--spacing-md);
  flex-shrink: 0;
}
.widget-header__top {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-sm);
  flex-wrap: wrap;
}
.widget-title {
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  color: var(--color-text);
  margin: 0;
  letter-spacing: -0.01em;
}
.total-badge {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  background: var(--color-primary-soft);
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-md);
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
  padding: 0.35rem 0.7rem;
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease;
}
.chart-type-toggle__btn:hover {
  color: var(--color-text);
  background: var(--color-bg-card);
}
.chart-type-toggle__btn.active {
  background: var(--color-primary);
  color: #fff;
  box-shadow: var(--shadow-sm);
}

.chart-skeleton {
  min-height: var(--widget-chart-min-height-sm);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-md) 0;
}
.skeleton-item {
  width: 70%;
}

.chart-and-list {
  display: flex;
  flex-direction: column;
  gap: var(--widget-gap);
  min-height: 0;
  flex: 1;
  overflow: hidden;
}
@media (min-width: 900px) {
  .chart-and-list {
    flex-direction: row;
    align-items: stretch;
    gap: var(--spacing-lg);
  }
  .chart-area {
    flex: 1;
    min-width: 0;
    min-height: 0;
  }
  .tech-panel {
    width: 220px;
    flex-shrink: 0;
    min-height: 0;
    display: flex;
    flex-direction: column;
  }
}

.chart-area {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.chart-wrap {
  height: var(--widget-chart-min-height);
  min-height: var(--widget-chart-min-height-sm);
  max-height: 360px;
  flex-shrink: 0;
  position: relative;
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--color-bg-soft);
}
.hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: 0;
  line-height: 1.4;
}

.tech-panel {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--color-bg-soft);
}
.tech-panel-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--color-bg-card);
  border: none;
  cursor: pointer;
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease;
}
.tech-panel-toggle:hover {
  background: var(--color-bg-soft);
  color: var(--color-primary);
}
.tech-panel-toggle__icon {
  font-size: 0.6rem;
  color: var(--color-text-muted);
}
.tech-list {
  max-height: 280px;
  overflow-y: auto;
  padding: var(--spacing-xs) 0;
  background: var(--color-bg-card);
  scrollbar-width: thin;
}
.tech-list::-webkit-scrollbar {
  width: 6px;
}
.tech-list::-webkit-scrollbar-track {
  background: var(--color-bg-soft);
  border-radius: var(--radius-sm);
}
.tech-list::-webkit-scrollbar-thumb {
  background: var(--color-border);
  border-radius: var(--radius-sm);
}
.tech-list::-webkit-scrollbar-thumb:hover {
  background: var(--color-text-muted);
}
.tech-list-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  width: 100%;
  padding: var(--spacing-sm) var(--spacing-md);
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: var(--text-xs);
  text-align: left;
  color: var(--color-text);
  transition: background var(--duration-fast) ease;
}
.tech-list-item:hover {
  background: var(--color-bg-soft);
}
.tech-list-item__dot {
  flex-shrink: 0;
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
}
.tech-list-item__name {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.tech-list-item__hours {
  flex-shrink: 0;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  color: var(--color-text-muted);
  font-size: var(--text-xs);
}

.slide-enter-active,
.slide-leave-active {
  transition: max-height var(--duration-normal) var(--ease-in-out), opacity var(--duration-fast) ease;
}
.slide-enter-from,
.slide-leave-to {
  max-height: 0;
  opacity: 0;
  overflow: hidden;
}
.slide-enter-to,
.slide-leave-from {
  max-height: 280px;
  opacity: 1;
}

.empty {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  text-align: center;
  padding: var(--spacing-xl) var(--spacing-sm);
  margin: 0;
  line-height: 1.5;
}

@media (min-width: 640px) {
  .chart-wrap {
    height: 340px;
    min-height: var(--widget-chart-min-height);
  }
}
@media (min-width: 1024px) {
  .chart-wrap {
    height: 360px;
  }
  .tech-panel {
    width: 240px;
  }
  .tech-list {
    max-height: 260px;
  }
}
@media (max-width: 480px) {
  .chart-wrap {
    height: 280px;
    max-height: 280px;
  }
}
</style>
