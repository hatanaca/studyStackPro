<script setup lang="ts">
import { defineAsyncComponent, onMounted, watch, computed, inject } from 'vue'
import { useDashboardQuery } from '@/features/dashboard/composables/useDashboardQuery'
import { useDashboard } from '@/features/dashboard/composables/useDashboard'
import { useAnalyticsStore } from '@/stores/analytics.store'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import DashboardHeader from '@/features/dashboard/components/DashboardHeader.vue'
import KpiCards from '@/features/dashboard/components/KpiCards.vue'
import TodaySummaryCard from '@/features/dashboard/components/TodaySummaryCard.vue'
import LogSessionWidget from '@/features/sessions/components/LogSessionWidget.vue'
import OnboardingBanner from '@/components/onboarding/OnboardingBanner.vue'
import StakentMetricCard from '@/features/dashboard/components/StakentMetricCard.vue'
import StakentFeatureCard from '@/features/dashboard/components/StakentFeatureCard.vue'
import StakentActiveCard from '@/features/dashboard/components/StakentActiveCard.vue'

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

const stakentStyle = inject<{ value: boolean }>('stakentStyle', { value: false })
const dashboardQuery = useDashboardQuery()
const { initDashboard } = useDashboard({
  refetchDashboard: () => dashboardQuery.refetch(),
})
const analyticsStore = useAnalyticsStore()
const hasError = computed(() => dashboardQuery.isError.value)

function formatHours(h: number): string {
  if (h <= 0) return '0h'
  const int = Math.floor(h)
  const m = Math.round((h - int) * 60)
  return m === 0 ? `${int}h` : `${int}h ${m}min`
}

const stakentSparkline = computed(() => {
  const data = analyticsStore.timeSeriesData['30d'] ?? []
  if (!data.length) return []
  return data.slice(-14).map(d => d.total_minutes / 60)
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

onMounted(async () => {
  try {
    await initDashboard()
  } catch {
    // initDashboard só carrega heatmap/weekly/timeSeries; erro de dashboard vem do query
  }
})

async function retry() {
  await dashboardQuery.refetch()
  await analyticsStore.fetchHeatmap()
  await analyticsStore.fetchWeekly()
}
</script>

<template>
  <div class="dashboard">
    <DashboardHeader v-if="!stakentStyle?.value" />
    <ErrorCard
      v-if="hasError"
      message="Não foi possível carregar o dashboard."
      :on-retry="retry"
    />
    <template v-else>
      <template v-if="stakentStyle?.value">
        <section class="stakent-dashboard">
          <div class="stakent-dashboard__top">
            <div class="stakent-dashboard__recommended">
              <header class="stakent-dashboard__section-head">
                <span class="stakent-dashboard__section-title">Recomendado para as próximas 24h</span>
                <span class="stakent-dashboard__section-tag">3 métricas</span>
              </header>
              <div class="stakent-dashboard__cards">
                <StakentMetricCard
                  tag="Total de horas"
                  :value="formatHours(analyticsStore.userMetrics?.total_hours ?? 0)"
                  label="Tempo total de estudo"
                  :chart-data="stakentSparkline.length ? stakentSparkline : undefined"
                  color="#8b5cf6"
                />
                <StakentMetricCard
                  tag="Sessões"
                  :value="analyticsStore.userMetrics?.total_sessions ?? 0"
                  label="Total de sessões"
                  color="#22c55e"
                />
                <StakentMetricCard
                  tag="Streak"
                  :value="`${analyticsStore.userMetrics?.current_streak_days ?? 0} dias`"
                  label="Sequência atual"
                  color="#f59e0b"
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
              <div
                v-for="i in 3"
                :key="i"
                class="kpi-card-skeleton"
              >
                <SkeletonLoader
                  width="60%"
                  height="0.875rem"
                />
                <SkeletonLoader
                  width="80%"
                  height="1.5rem"
                  class="mt-2"
                />
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
          <div
            v-else
            class="widgets__item widgets__item--3 widgets__item--full dashboard__empty"
          >
            <EmptyState
              title="Nenhum dado ainda"
              description="Registre sua primeira sessão no card acima para desbloquear métricas, gráficos e metas. Use o período (7d, 30d, 90d) no gráfico para filtrar os dados."
              icon="📊"
              hide-action
            />
          </div>
          <div class="widgets__item widgets__item--4">
            <GoalsWidget />
          </div>
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
        </div>
      </template>
    </template>
  </div>
</template>

<style scoped>
/* Card "Registrar estudo" usa o mesmo padding dos demais widgets */
.dashboard-register :deep(.base-card__header) {
  padding: var(--widget-padding) var(--widget-padding) 0;
}
.dashboard-register :deep(.base-card__body) {
  padding: var(--widget-padding);
}
.dashboard__content {
  background: color-mix(in srgb, var(--color-bg-soft) 40%, var(--color-bg));
  border-radius: var(--radius-lg);
  padding: var(--spacing-md);
  margin-top: var(--spacing-xs);
}
.dashboard__empty {
  min-height: var(--widget-card-min-height);
}
.dashboard__empty :deep(.empty-state) {
  background: var(--color-bg-card);
  border: 1px dashed var(--color-border);
  border-radius: var(--widget-radius);
  padding: var(--spacing-xl);
  box-shadow: var(--shadow-sm);
}
.widgets {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--widget-gap);
  align-items: start;
  grid-auto-rows: minmax(min-content, auto);
}
.dashboard {
  max-width: var(--page-max-width);
  margin: 0 auto;
}
.widgets--animate .widgets__item {
  animation: fadeUpIn var(--duration-slow) var(--ease-out-expo) backwards;
}
.widgets--animate .widgets__item--0 { animation-delay: 0.05s; }
.widgets--animate .widgets__item--1 { animation-delay: 0.08s; }
.widgets--animate .widgets__item--2 { animation-delay: 0.11s; }
.widgets--animate .widgets__item--3 { animation-delay: 0.14s; }
.widgets--animate .widgets__item--4 { animation-delay: 0.17s; }
.widgets--animate .widgets__item--5 { animation-delay: 0.2s; }
.widgets--animate .widgets__item--6 { animation-delay: 0.23s; }
.widgets--animate .widgets__item--7 { animation-delay: 0.26s; }
@keyframes fadeUpIn {
  from {
    opacity: 0;
    transform: translateY(12px);
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
.widgets__item--0 :deep(.base-card) {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}
.widgets__item--0 :deep(.base-card__body) {
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
    gap: var(--widget-gap);
  }
  .widgets__item--0 { grid-column: 1; }
  .widgets__item--1 { grid-column: 2; }
  .widgets__item--2 { grid-column: 1; }
  .widgets__item--3 { grid-column: 2; }
  .widgets__item--4 { grid-column: 1 / -1; }
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
/* Desktop: grid 12 colunas com register/reminders no topo */
@media (min-width: 1024px) {
  .widgets {
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: var(--widget-gap);
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
    grid-column: 1 / 5;
    grid-row: 3;
  }
  .widgets__item--5 {
    grid-column: 5 / 13;
    grid-row: 3;
    min-height: var(--widget-chart-min-height);
  }
  .widgets__item--6 {
    grid-column: 5 / 13;
    grid-row: 4;
    min-height: var(--widget-chart-min-height);
  }
  .widgets__item--7 {
    grid-column: 1 / 13;
    grid-row: 5;
    min-height: var(--widget-chart-min-height);
  }
}
.kpi-skeleton {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--widget-gap);
}
@media (min-width: 480px) {
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
.mt-2 {
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
  gap: var(--spacing-lg);
}
.stakent-dashboard__top {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-lg);
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
  margin-bottom: var(--spacing-md);
  flex-wrap: wrap;
}
.stakent-dashboard__section-title {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text-muted);
}
.stakent-dashboard__section-tag {
  font-size: var(--text-xs);
  padding: 0.2rem 0.5rem;
  border-radius: 9999px;
  background: var(--color-bg-soft);
  color: var(--color-text-muted);
  font-weight: 500;
}
.stakent-dashboard__cards {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
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
