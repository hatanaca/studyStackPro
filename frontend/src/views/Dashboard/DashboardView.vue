<script setup lang="ts">
/**
 * View principal do dashboard. KPIs, resumo do dia, sessão ativa, widgets (distribuição, séries, weekly, metas, lembretes).
 * Usa TanStack Query para dashboard. Lazy load de widgets pesados. Suporta tema stakent.
 */
import {
  defineAsyncComponent,
  h,
  onMounted,
  onBeforeUnmount,
  watch,
  computed,
  inject,
  ref,
} from 'vue'
import { useRouter } from 'vue-router'
import { useApexChartTheme } from '@/composables/useApexChartTheme'
import { useDashboardQuery } from '@/features/dashboard/composables/useDashboardQuery'
import { useDashboard } from '@/features/dashboard/composables/useDashboard'
import { useAnalyticsStore } from '@/stores/analytics.store'
import Skeleton from 'primevue/skeleton'
import DashboardHeader from '@/features/dashboard/components/DashboardHeader.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import KpiCards from '@/features/dashboard/components/KpiCards.vue'
import TodaySummaryCard from '@/features/dashboard/components/TodaySummaryCard.vue'
import OnboardingBanner from '@/components/onboarding/OnboardingBanner.vue'

const LogSessionWidget = defineAsyncComponent({
  loader: () => import('@/features/sessions/components/LogSessionWidget.vue'),
  loadingComponent: {
    name: 'LogSessionWidgetLoading',
    setup() {
      return () =>
        h(
          'section',
          {
            class: 'dashboard-log-session-loading',
            role: 'status',
            'aria-live': 'polite',
            'aria-label': 'Carregando formulário de registro',
          },
          [
            h(Skeleton, {
              width: '42%',
              height: '1rem',
              class: 'dashboard-log-session-loading__title',
            }),
            h(Skeleton, { width: '100%', height: '11rem' }),
          ]
        )
    },
  },
  delay: 120,
})
const StakentMetricCard = defineAsyncComponent(
  () => import('@/features/dashboard/components/StakentMetricCard.vue')
)
const StakentFeatureCard = defineAsyncComponent(
  () => import('@/features/dashboard/components/StakentFeatureCard.vue')
)
const StakentActiveCard = defineAsyncComponent(
  () => import('@/features/dashboard/components/StakentActiveCard.vue')
)

const TechDistributionWidget = defineAsyncComponent(
  () => import('@/features/dashboard/components/TechDistributionWidget.vue')
)
const TimeSeriesWidget = defineAsyncComponent(
  () => import('@/features/dashboard/components/TimeSeriesWidget.vue')
)
const WeeklyComparisonWidget = defineAsyncComponent(
  () => import('@/features/dashboard/components/WeeklyComparisonWidget.vue')
)
const RemindersWidget = defineAsyncComponent(
  () => import('@/features/dashboard/components/RemindersWidget.vue')
)
const GoalsWidget = defineAsyncComponent(
  () => import('@/features/dashboard/components/GoalsWidget.vue')
)

const router = useRouter()
const stakentStyle = inject<{ value: boolean }>('stakentStyle', { value: false })
const { theme: chartTheme } = useApexChartTheme()
const stakentMetricColors = computed(() => ({
  primary: chartTheme.value.palette[0],
  success: chartTheme.value.palette[1],
  warning: chartTheme.value.palette[2],
}))
const dashboardQuery = useDashboardQuery()
const { initDashboard } = useDashboard({
  refetchDashboard: () => dashboardQuery.refetch(),
})
const analyticsStore = useAnalyticsStore()
const hasError = computed(() => dashboardQuery.isError.value)
const showHeavyWidgets = ref(false)
type IdleCapableGlobal = typeof globalThis & {
  requestIdleCallback?: (cb: () => void, opts?: { timeout: number }) => void
}

function formatHours(h: number): string {
  if (h <= 0) return '0h'
  const int = Math.floor(h)
  const m = Math.round((h - int) * 60)
  return m === 0 ? `${int}h` : `${int}h ${m}min`
}

const stakentSparkline = computed(() => {
  const data = analyticsStore.timeSeriesData['30d'] ?? []
  if (!data.length) return []
  return data.slice(-14).map((d) => d.total_minutes / 60)
})

// Lazy fetch para 90d quando usuário selecionar
watch(
  () => analyticsStore.selectedPeriod,
  async (period) => {
    if (period === '90d' && !analyticsStore.timeSeriesData['90d']?.length) {
      await analyticsStore.fetchTimeSeries('90d')
    }
  }
)

let _heavyWidgetTimer: ReturnType<typeof setTimeout> | null = null

onMounted(async () => {
  try {
    await initDashboard()
  } catch {
    // initDashboard só carrega heatmap/weekly/timeSeries; erro de dashboard vem do query
  }

  const loadHeavyWidgets = () => {
    showHeavyWidgets.value = true
    _heavyWidgetTimer = null
  }

  const idleGlobal = globalThis as IdleCapableGlobal
  if (typeof idleGlobal.requestIdleCallback === 'function') {
    idleGlobal.requestIdleCallback(loadHeavyWidgets, { timeout: 1200 })
  } else {
    _heavyWidgetTimer = setTimeout(loadHeavyWidgets, 500)
  }
})

onBeforeUnmount(() => {
  if (_heavyWidgetTimer) {
    clearTimeout(_heavyWidgetTimer)
    _heavyWidgetTimer = null
  }
})

async function retry() {
  await Promise.all([
    dashboardQuery.refetch(),
    analyticsStore.fetchHeatmap(),
    analyticsStore.fetchWeekly(),
  ])
}

function goRegisterSession() {
  router.push({ name: 'sessions' })
}
</script>

<template>
  <div class="dashboard">
    <DashboardHeader v-if="!stakentStyle?.value" />
    <div v-if="hasError" class="dashboard__error">
      <ErrorCard
        title="Dashboard indisponível"
        message="Não foi possível carregar o dashboard. Verifique a conexão e tente de novo."
        :on-retry="retry"
      />
    </div>
    <template v-else>
      <template v-if="stakentStyle?.value">
        <section class="stakent-dashboard">
          <div class="stakent-dashboard__top">
            <div class="stakent-dashboard__recommended">
              <header class="stakent-dashboard__section-head">
                <span class="stakent-dashboard__section-title"
                  >Recomendado para as próximas 24h</span
                >
                <span class="stakent-dashboard__section-tag">3 métricas</span>
              </header>
              <div class="stakent-dashboard__cards">
                <StakentMetricCard
                  tag="Total de horas"
                  :value="formatHours(analyticsStore.userMetrics?.total_hours ?? 0)"
                  label="Tempo total de estudo"
                  :chart-data="stakentSparkline.length ? stakentSparkline : undefined"
                  :color="stakentMetricColors.primary"
                />
                <StakentMetricCard
                  tag="Sessões"
                  :value="analyticsStore.userMetrics?.total_sessions ?? 0"
                  label="Total de sessões"
                  :color="stakentMetricColors.success"
                />
                <StakentMetricCard
                  tag="Streak"
                  :value="`${analyticsStore.userMetrics?.current_streak_days ?? 0} dias`"
                  label="Sequência atual"
                  :color="stakentMetricColors.warning"
                />
              </div>
            </div>
            <div class="stakent-dashboard__feature">
              <StakentFeatureCard />
            </div>
          </div>
          <div class="stakent-dashboard__active">
            <StakentActiveCard />
          </div>
        </section>
      </template>
      <template v-else>
        <OnboardingBanner />
        <template v-if="analyticsStore.isLoading && !analyticsStore.dashboard">
          <div
            class="widgets widgets--skeleton"
            role="status"
            aria-live="polite"
            aria-label="Carregando dashboard"
          >
            <section class="kpi-skeleton">
              <div v-for="i in 3" :key="i" class="kpi-card-skeleton">
                <Skeleton width="60%" height="0.875rem" />
                <Skeleton width="80%" height="1.5rem" class="skeleton-spacer" />
              </div>
            </section>
          </div>
        </template>
        <div
          v-else
          class="widgets widgets--animate dashboard__content"
          role="region"
          aria-label="Métricas e widgets do dashboard"
        >
          <div class="widgets__item widgets__item--0">
            <LogSessionWidget class="dashboard-register" />
          </div>
          <div class="widgets__item widgets__item--1">
            <RemindersWidget />
          </div>
          <div class="widgets__item widgets__item--2">
            <TodaySummaryCard />
          </div>
          <template v-if="analyticsStore.userMetrics">
            <div class="widgets__item widgets__item--3">
              <KpiCards :metrics="analyticsStore.userMetrics" />
            </div>
          </template>
          <div v-else class="widgets__item widgets__item--3 widgets__item--full dashboard__empty">
            <EmptyState
              title="Nenhum dado ainda"
              description="Sua primeira sessão desbloqueia métricas e gráficos. Registre uma sessão para ver totais, evolução e metas."
              icon="📊"
              action-label="Registrar sessão"
              :hide-action="false"
              @action="goRegisterSession"
            />
          </div>
          <div class="widgets__item widgets__item--4">
            <GoalsWidget />
          </div>
          <template v-if="showHeavyWidgets">
            <div class="widgets__item widgets__item--5 widget-full">
              <TimeSeriesWidget />
            </div>
            <div class="widgets__item widgets__item--6 widget-full">
              <WeeklyComparisonWidget />
            </div>
            <template v-if="analyticsStore.technologyMetrics?.length">
              <div class="widgets__item widgets__item--7 widget-full">
                <TechDistributionWidget
                  :metrics="analyticsStore.technologyMetrics"
                  :loading="analyticsStore.isRecalculating"
                />
              </div>
            </template>
          </template>
          <template v-else>
            <div class="widgets__item widgets__item--5 widget-full">
              <section class="kpi-card-skeleton" aria-hidden="true">
                <Skeleton width="45%" height="1rem" />
                <Skeleton width="100%" height="14rem" class="skeleton-spacer" />
              </section>
            </div>
          </template>
        </div>
      </template>
    </template>
  </div>
</template>

<style scoped>
.dashboard-register :deep(.p-card-body),
.dashboard-register :deep(.p-card-content) {
  padding: var(--widget-padding);
}
.dashboard__content {
  background: color-mix(in srgb, var(--color-bg-soft) 40%, var(--color-bg));
  border-radius: var(--radius-lg);
  padding: var(--spacing-lg);
  margin-top: var(--spacing-xs);
}
.dashboard__error {
  margin: var(--spacing-lg) 0;
  max-width: 28rem;
}
.dashboard__empty {
  min-height: var(--widget-card-min-height);
}
.dashboard__empty :deep(.empty-state) {
  border-radius: var(--widget-radius);
  min-height: min(var(--empty-state-min-height), 100%);
}
.widgets {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-lg);
  align-items: start;
  grid-auto-rows: minmax(min-content, auto);
}
.widgets__item {
  min-width: 0;
}
.dashboard {
  max-width: var(--page-max-width);
  margin: 0 auto;
}
@media (prefers-reduced-motion: no-preference) {
  .widgets--animate .widgets__item {
    animation: fadeUpIn var(--duration-slow) var(--ease-out-expo) backwards;
  }
  .widgets--animate .widgets__item--0 {
    animation-delay: 0.05s;
  }
  .widgets--animate .widgets__item--1 {
    animation-delay: 0.08s;
  }
  .widgets--animate .widgets__item--2 {
    animation-delay: 0.11s;
  }
  .widgets--animate .widgets__item--3 {
    animation-delay: 0.14s;
  }
  .widgets--animate .widgets__item--4 {
    animation-delay: 0.17s;
  }
  .widgets--animate .widgets__item--5 {
    animation-delay: 0.2s;
  }
  .widgets--animate .widgets__item--6 {
    animation-delay: 0.23s;
  }
  .widgets--animate .widgets__item--7 {
    animation-delay: 0.26s;
  }
}
@keyframes fadeUpIn {
  from {
    opacity: 0;
    transform: translateY(var(--spacing-md));
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.widgets__item--0,
.widgets__item--1,
.widgets__item--2,
.widgets__item--3,
.widgets__item--4 {
  min-width: 0;
}
/* Mesma altura: Registrar estudo e Lembretes rápidos */
.widgets__item--0,
.widgets__item--1 {
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.widgets__item--0 :deep(.p-card) {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}
.widgets__item--0 :deep(.p-card-content) {
  flex: 1;
  min-height: 0;
}
.widgets__item--1 .reminders-widget {
  flex: 1;
  min-height: 100%;
  display: flex;
  flex-direction: column;
}
.widgets__item--full {
  grid-column: 1 / -1;
}
/* Tablet: 2 colunas com registro e lembretes lado a lado */
@media (min-width: 640px) {
  .widgets {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .widgets__item--0 {
    grid-column: 1;
  }
  .widgets__item--1 {
    grid-column: 2;
  }
  .widgets__item--2 {
    grid-column: 1;
  }
  .widgets__item--3 {
    grid-column: 2;
  }
  .widgets__item--4 {
    grid-column: 1 / -1;
  }
  .widgets__item--5,
  .widgets__item--6,
  .widgets__item--7 {
    grid-column: 1 / -1;
  }
  .widgets__item--5,
  .widgets__item--6,
  .widgets__item--7 {
    min-height: var(--widget-chart-min-height-sm);
  }
}
/* Desktop: grid 12 colunas */
@media (min-width: 1024px) {
  .widgets {
    grid-template-columns: repeat(12, minmax(0, 1fr));
    grid-auto-rows: minmax(var(--widget-card-min-height), auto);
  }
  .widgets__item--0 {
    grid-column: 1 / 7;
    grid-row: 1;
  }
  .widgets__item--1 {
    grid-column: 7 / 13;
    grid-row: 1;
  }
  .widgets__item--2 {
    grid-column: 1 / 5;
    grid-row: 2;
  }
  .widgets__item--3 {
    grid-column: 5 / 13;
    grid-row: 2;
  }
  .widgets__item--4 {
    grid-column: 1 / -1;
    grid-row: 3;
    grid-row-start: 3;
    grid-row-end: 4;
    min-width: 0;
  }
  .widgets__item--5 {
    grid-column: 1 / -1;
    grid-row: 4;
    grid-row-start: 4;
    grid-row-end: 5;
    min-height: var(--widget-chart-min-height);
    min-width: 0;
  }
  .widgets__item--6 {
    grid-column: 1 / 13;
    grid-row: 5;
    grid-row-start: 5;
    grid-row-end: 6;
    min-height: var(--widget-chart-min-height);
  }
  .widgets__item--7 {
    grid-column: 1 / 13;
    grid-row: 6;
    grid-row-start: 6;
    grid-row-end: 7;
    min-height: var(--widget-chart-min-height);
  }
}
.kpi-skeleton {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--widget-gap);
}
@media (min-width: 640px) {
  .kpi-skeleton {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  }
}
@media (min-width: 1024px) {
  .kpi-skeleton {
    grid-template-columns: repeat(3, 1fr);
  }
}
.kpi-card-skeleton {
  background: var(--color-bg-card);
  padding: var(--widget-padding);
  border-radius: var(--widget-radius);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  min-height: var(--widget-card-min-height);
}
.kpi-card-skeleton :deep(.p-skeleton),
.widgets--skeleton :deep(.p-skeleton) {
  border-radius: var(--radius-sm);
}
.skeleton-spacer {
  margin-top: var(--spacing-sm);
}
.widget-full {
  grid-column: 1 / -1;
}
.dashboard-register {
  margin-bottom: 0;
}

/* Layout estilo Stakent */
.stakent-dashboard {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
}
.stakent-dashboard__top {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-xl);
}
@media (min-width: 1024px) {
  .stakent-dashboard__top {
    grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
    align-items: start;
  }
}
.stakent-dashboard__section-head {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-lg);
  flex-wrap: wrap;
}
.stakent-dashboard__section-title {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text-muted);
}
.stakent-dashboard__section-tag {
  font-size: var(--text-xs);
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-full);
  background: var(--color-bg-soft);
  color: var(--color-text-muted);
  font-weight: 500;
}
.stakent-dashboard__cards {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-lg);
}
@media (min-width: 640px) {
  .stakent-dashboard__cards {
    grid-template-columns: repeat(3, 1fr);
  }
}
.stakent-dashboard__feature {
  min-width: 0;
}
.stakent-dashboard__active {
  min-width: 0;
}
</style>

<style>
/* Placeholder do LogSessionWidget (defineAsyncComponent); fora do scoped para o loadingComponent inline. */
.dashboard-log-session-loading {
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  box-shadow: var(--shadow-sm);
  min-height: min(var(--widget-card-min-height, 12rem), 100%);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}
.dashboard-log-session-loading__title {
  display: block;
}
</style>
