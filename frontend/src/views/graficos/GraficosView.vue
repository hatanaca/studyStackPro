<script setup lang="ts">
/**
 * @module GraficosView
 *
 * Dashboard de analytics com visual moderno e jovem.
 * Hero chart com glassmorphism, gradientes vibrantes, glow effects
 * e animações suaves entre layouts.
 */
import { ref, computed, onMounted, defineAsyncComponent, watch, onErrorCaptured } from 'vue'
import PageView from '@/components/layout/PageView.vue'
import { useGraficos } from '@/features/graficos/composables/useGraficos'
import RadarChart from '@/components/charts/RadarChart.vue'
import Skeleton from 'primevue/skeleton'
import type { ApexOptions } from 'apexcharts'
import VueApexCharts from 'vue3-apexcharts'
import { useApexChartTheme } from '@/composables/useApexChartTheme'
import { useMediaQuery } from '@/composables/useMediaQuery'

const GraficosToolbar = defineAsyncComponent(() => import('./components/GraficosToolbar.vue'))
const KpiStrip = defineAsyncComponent(() => import('./components/KpiStrip.vue'))
const TechDistributionPanel = defineAsyncComponent(() => import('./components/TechDistributionPanel.vue'))
const WeeklyBarPanel = defineAsyncComponent(() => import('./components/WeeklyBarPanel.vue'))
const HeatmapPanel = defineAsyncComponent(() => import('./components/HeatmapPanel.vue'))
const RadarPanel = defineAsyncComponent(() => import('./components/RadarPanel.vue'))
const FunnelPanel = defineAsyncComponent(() => import('./components/FunnelPanel.vue'))
const TrendComparisonPanel = defineAsyncComponent(() => import('./components/TrendComparisonPanel.vue'))

const graficos = useGraficos()

const renderError = ref<string | null>(null)

onErrorCaptured((err) => {
  renderError.value = err instanceof Error ? err.message : String(err)
  return false // impede propagação
})

type HeroLayout = 'area' | 'line' | 'bar' | 'radar'
const heroLayout = ref<HeroLayout>('area')

const heroLayoutOptions: { value: HeroLayout; label: string; icon: string }[] = [
  { value: 'area', label: 'Área', icon: '◇' },
  { value: 'line', label: 'Linha', icon: '╱' },
  { value: 'bar', label: 'Barras', icon: '▥' },
  { value: 'radar', label: 'Radar', icon: '◎' },
]

const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)')
const { baseOptions, theme } = useApexChartTheme()

const heroChartOptions = computed<ApexOptions | undefined>(() => {
  try {
    const data = graficos.timeSeriesForChart.value
    if (!data?.labels?.length || !data?.values?.length) return undefined
    const isBar = heroLayout.value === 'bar'
    const isRadar = heroLayout.value === 'radar'
    const t = theme.value

    return {
      ...baseOptions.value,
      chart: {
        ...baseOptions.value.chart,
        type: isBar ? 'bar' : isRadar ? 'radar' : 'area',
        height: 420,
        background: 'transparent',
        toolbar: {
          show: true,
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
        animations: {
          enabled: !prefersReducedMotion.value,
          speed: 800,
          easing: 'easeout',
        },
        fontFamily: t.fontFamily,
      },
      colors: ['#8b5cf6', '#ec4899', '#06b6d4'],
      stroke: {
        curve: 'smooth',
        width: isBar ? 0 : heroLayout.value === 'line' ? 3 : 2,
      },
      fill: isBar ? { type: 'solid' } : {
        type: 'gradient',
        gradient: {
          shade: 'dark',
          type: 'vertical',
          shadeIntensity: 0.3,
          opacityFrom: 0.6,
          opacityTo: 0.05,
          stops: [0, 100],
          colorStops: [
            { offset: 0, color: '#8b5cf6', opacity: 0.5 },
            { offset: 50, color: '#ec4899', opacity: 0.2 },
            { offset: 100, color: '#06b6d4', opacity: 0.02 },
          ],
        },
      },
      grid: {
        borderColor: 'rgba(148,163,184,0.08)',
        strokeDashArray: 4,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
        padding: { left: 8, right: 8 },
      },
      tooltip: {
        theme: 'dark',
        shared: true,
        intersect: false,
        style: { fontSize: '13px' },
        y: { formatter: (val: number) => `${val} minutos` },
      },
      plotOptions: isBar ? {
        bar: {
          borderRadius: 8,
          columnWidth: '55%',
          borderRadiusApplication: 'end',
        },
      } : undefined,
      dataLabels: { enabled: false },
      legend: { show: false },
      markers: isRadar ? undefined : {
        size: 0,
        hover: { size: 8, sizeOffset: 3 },
        strokeWidth: 2,
        strokeColors: '#fff',
      },
      xaxis: isRadar ? {} : {
        categories: data.labels,
        labels: {
          style: {
            colors: t.textMuted,
            fontSize: '11px',
            fontFamily: t.fontFamily,
          },
          rotate: -45,
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
      },
      yaxis: isRadar ? { show: false, max: 100 } : {
        labels: {
          style: {
            colors: t.textMuted,
            fontSize: '11px',
            fontFamily: t.fontFamily,
          },
          formatter: (val: number) => {
            if (val >= 60) {
              const h = Math.floor(val / 60)
              const m = val % 60
              return m > 0 ? `${h}h${m}` : `${h}h`
            }
            return `${val}m`
          },
        },
      },
    }
  } catch {
    return undefined
  }
})

const heroSeries = computed(() => {
  try {
    const data = graficos.timeSeriesForChart.value
    if (!data?.values?.length) return [{ name: 'Minutos', data: [] }]
    if (heroLayout.value === 'radar') {
      const values = data.values.slice(-14)
      return [{
        name: 'Minutos',
        data: values.map((v) => Math.round((v / Math.max(...values, 1)) * 100)),
      }]
    }
    return [{ name: 'Minutos', data: data.values }]
  } catch {
    return [{ name: 'Minutos', data: [] }]
  }
})

const heroRadarLabels = computed(() => {
  try {
    return graficos.timeSeriesForChart.value.labels.slice(-14)
  } catch {
    return []
  }
})

const safeTimeSeries = computed(() => {
  try { return graficos.timeSeriesForChart.value }
  catch { return { labels: [] as string[], values: [] as number[] } }
})

onMounted(() => {
  try {
    graficos.fetchAll()
  } catch {
    /* swallow */
  }
})

watch(() => graficos.dateRange.value, () => {
  try {
    graficos.fetchTimeSeries(90)
    graficos.fetchHeatmap()
  } catch {
    /* swallow */
  }
})
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Gráficos' }]"
    title="Gráficos & Analytics"
    subtitle="Visualize seus dados de estudo com gráficos interativos e relatórios detalhados."
    narrow
  >
    <div v-if="renderError" style="padding:2rem;background:#fef2f2;border:1px solid #ef4444;border-radius:8px;margin-bottom:1rem;">
      <p style="color:#dc2626;font-weight:600;">Erro ao renderizar gráficos</p>
      <p style="color:#6b7280;font-size:0.875rem;">{{ renderError }}</p>
    </div>
    <div class="gv">
      <!-- Toolbar -->
      <div class="gv__toolbar animate-fade-in-up">
        <GraficosToolbar
          :date-range="graficos.dateRange.value"
          :selected-tech-ids="graficos.selectedTechIds.value"
          @update:date-range="graficos.setDateRange"
          @toggle-tech="graficos.toggleTechFilter"
        />
      </div>

      <!-- ═══════════════ HERO CHART ═══════════════ -->
      <div class="hero animate-fade-in-up stagger-1">
        <!-- Gradient glow backdrop -->
        <div class="hero__glow" />

        <div class="hero__header">
          <div class="hero__title-group">
            <div class="hero__badge">AO VIVO</div>
            <h2 class="hero__title">Evolução de estudo</h2>
            <p class="hero__subtitle">Minutos estudados por dia — escolha o visual</p>
          </div>

          <div class="hero__layout-picker">
            <label
              v-for="opt in heroLayoutOptions"
              :key="opt.value"
              class="hero__pill"
              :class="{ 'hero__pill--active': heroLayout === opt.value }"
            >
              <input
                v-model="heroLayout"
                type="radio"
                :value="opt.value"
                class="hero__radio"
              />
              <span class="hero__pill-icon">{{ opt.icon }}</span>
              <span class="hero__pill-label">{{ opt.label }}</span>
            </label>
          </div>
        </div>

        <div class="hero__chart">
          <Skeleton v-if="graficos.loadingStates.value.timeSeries" height="420px" class="hero__skeleton" />
          <div v-else-if="!heroChartOptions || (heroChartOptions as any).categories?.length === 0" class="hero__empty">
            <p class="hero__empty-text">Nenhum dado disponível para exibir.</p>
          </div>
          <template v-else>
            <VueApexCharts
              v-if="heroLayout === 'area' || heroLayout === 'line'"
              type="area"
              height="420"
              width="100%"
              :options="heroChartOptions"
              :series="heroSeries"
            />
            <VueApexCharts
              v-else-if="heroLayout === 'bar'"
              type="bar"
              height="420"
              width="100%"
              :options="heroChartOptions"
              :series="heroSeries"
            />
            <RadarChart
              v-else
              :series="heroSeries"
              :labels="heroRadarLabels"
              :chart-height="420"
            />
          </template>
        </div>
      </div>

      <!-- ═══════════════ KPI STRIP ═══════════════ -->
      <div class="gv__kpi animate-fade-in-up stagger-2">
        <KpiStrip :data="graficos.kpiData.value" :loading="graficos.loadingStates.value.dashboard" />
      </div>

      <!-- ═══════════════ CHILD CHARTS ═══════════════ -->
      <div class="grid">
        <div class="grid__half animate-fade-in-up stagger-3">
          <TechDistributionPanel
            :data="graficos.techDistributionForChart.value"
            :treemap-data="graficos.treemapData.value"
            :loading="graficos.loadingStates.value.techStats"
          />
        </div>
        <div class="grid__half animate-fade-in-up stagger-4">
          <WeeklyBarPanel
            :data="graficos.weeklyForChart.value"
            :loading="graficos.loadingStates.value.weekly"
          />
        </div>

        <div class="grid__half animate-fade-in-up stagger-5">
          <HeatmapPanel
            :data="graficos.heatmapData.value"
            :loading="graficos.loadingStates.value.heatmap"
          />
        </div>
        <div class="grid__half animate-fade-in-up stagger-6">
          <RadarPanel
            :data="graficos.radarData.value"
            :loading="graficos.loadingStates.value.dashboard"
          />
        </div>

        <div class="grid__half animate-fade-in-up stagger-7">
          <FunnelPanel
            :data="graficos.funnelData.value"
            :loading="graficos.loadingStates.value.dashboard"
          />
        </div>
        <div class="grid__half animate-fade-in-up stagger-8">
          <TrendComparisonPanel
            :data="safeTimeSeries"
            :loading="graficos.loadingStates.value.timeSeries"
          />
        </div>
      </div>
    </div>
  </PageView>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════
   GRAFICOS VIEW — Modern Glassmorphism Theme
   ═══════════════════════════════════════════════════ */

.gv {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
}
.gv__toolbar {
  position: sticky;
  top: 0;
  z-index: 10;
}

/* ── HERO CHART ───────────────────────────────────── */
.hero {
  position: relative;
  background: linear-gradient(135deg,
    color-mix(in srgb, var(--color-bg-card) 95%, transparent) 0%,
    color-mix(in srgb, var(--color-bg-soft) 90%, transparent) 50%,
    color-mix(in srgb, var(--color-bg) 85%, transparent) 100%
  );
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border: 1px solid color-mix(in srgb, var(--color-primary) 12%, transparent);
  border-radius: 1.25rem;
  padding: var(--spacing-2xl);
  overflow: hidden;
  transition: box-shadow 0.3s ease, border-color 0.3s ease;
}
.hero:hover {
  border-color: color-mix(in srgb, var(--color-primary) 30%, transparent);
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--color-primary) 8%, transparent),
    0 8px 40px color-mix(in srgb, var(--color-bg) 40%, transparent),
    0 2px 8px color-mix(in srgb, var(--color-primary) 8%, transparent);
}

/* Animated gradient glow behind hero */
.hero__glow {
  position: absolute;
  top: -50%;
  left: -20%;
  width: 60%;
  height: 200%;
  background: radial-gradient(ellipse, color-mix(in srgb, var(--color-primary) 12%, transparent) 0%, transparent 60%);
  animation: heroGlow 8s ease-in-out infinite alternate;
  pointer-events: none;
}
@keyframes heroGlow {
  0% { transform: translate(0, 0) scale(1); opacity: 0.6; }
  50% { transform: translate(30%, 10%) scale(1.1); opacity: 1; }
  100% { transform: translate(-10%, -5%) scale(0.95); opacity: 0.7; }
}

.hero__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-xl);
  flex-wrap: wrap;
  position: relative;
  z-index: 1;
}

.hero__title-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xs);
}

.hero__badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  background: linear-gradient(135deg, var(--color-primary), var(--color-error));
  color: var(--color-text);
  font-size: var(--text-xs);
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  border-radius: var(--radius-full);
  width: fit-content;
  margin-bottom: 4px;
  animation: badgePulse 2s ease-in-out infinite;
}
.hero__badge::before {
  content: '';
  width: 6px;
  height: 6px;
  background: var(--color-text);
  border-radius: 50%;
  animation: dotBlink 1.5s ease-in-out infinite;
}
@keyframes badgePulse {
  0%, 100% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--color-primary) 40%, transparent); }
  50% { box-shadow: 0 0 0 6px transparent; }
}
@keyframes dotBlink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

.hero__title {
  font-family: var(--font-display);
  font-size: var(--text-2xl);
  font-weight: 700;
  background: linear-gradient(135deg, var(--color-text) 0%, var(--color-text-muted) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0;
  letter-spacing: -0.02em;
  line-height: 1.2;
}

.hero__subtitle {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
  opacity: 0.8;
}

/* ── Layout Picker ────────────────────────────────── */
.hero__layout-picker {
  display: flex;
  gap: 6px;
  background: color-mix(in srgb, var(--color-bg) 40%, transparent);
  backdrop-filter: blur(10px);
  border-radius: 1rem;
  padding: 4px;
  border: 1px solid color-mix(in srgb, var(--color-primary-contrast) 4%, transparent);
}

.hero__pill {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: var(--radius-lg, 0.75rem);
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}
.hero__pill:hover {
  background: color-mix(in srgb, var(--color-primary) 12%, transparent);
}
.hero__pill--active {
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
  box-shadow:
    0 0 12px color-mix(in srgb, var(--color-primary) 40%, transparent),
    0 2px 8px color-mix(in srgb, var(--color-bg) 20%, transparent);
}
.hero__pill--active .hero__pill-label {
  color: var(--color-text);
  font-weight: 600;
}
.hero__pill--active .hero__pill-icon {
  color: var(--color-text);
  transform: scale(1.1);
}

.hero__radio {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
  pointer-events: none;
}

.hero__pill-icon {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  transition: all 0.2s ease;
}

.hero__pill-label {
  font-size: var(--text-xs);
  font-weight: 500;
  color: var(--color-text-muted);
  white-space: nowrap;
  transition: color 0.2s ease;
}

.hero__chart {
  min-height: 420px;
  position: relative;
  z-index: 1;
}

.hero__skeleton {
  border-radius: var(--radius-lg, 0.75rem);
}

.hero__empty {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 420px;
}

.hero__empty-text {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  opacity: 0.7;
}

/* ── CHILD GRID ───────────────────────────────────── */
.grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-lg);
}
.grid > * {
  min-height: var(--widget-chart-min-height, 220px);
}
@media (min-width: 640px) {
  .grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (min-width: 1024px) {
  .grid {
    grid-template-columns: repeat(12, 1fr);
  }
  .grid__wide {
    grid-column: 1 / -1;
  }
  .grid__half {
    grid-column: span 6;
  }
}

@media (max-width: 640px) {
  .hero {
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
  }
  .hero__header {
    flex-direction: column;
  }
  .hero__layout-picker {
    overflow-x: auto;
    flex-wrap: nowrap;
    -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;
    gap: 4px;
    scrollbar-width: none;
  }
  .hero__layout-picker::-webkit-scrollbar {
    display: none;
  }
  .hero__pill {
    scroll-snap-align: start;
  }
  .hero__chart {
    min-height: 220px;
  }
  .grid {
    grid-template-columns: 1fr;
    gap: var(--spacing-md);
  }
  .grid > * {
    min-height: auto;
  }
}
</style>
