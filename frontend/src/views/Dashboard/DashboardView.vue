<script setup lang="ts">
import { ref, defineAsyncComponent, onMounted, watch } from 'vue'
import { useDashboard } from '@/features/dashboard/composables/useDashboard'
import { useAnalyticsStore } from '@/stores/analytics.store'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import DashboardHeader from '@/features/dashboard/components/DashboardHeader.vue'
import KpiCards from '@/features/dashboard/components/KpiCards.vue'
import TodaySummaryCard from '@/features/dashboard/components/TodaySummaryCard.vue'
import LogSessionWidget from '@/features/sessions/components/LogSessionWidget.vue'
import OnboardingBanner from '@/components/onboarding/OnboardingBanner.vue'

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

const { initDashboard, fetchDashboard } = useDashboard()
const analyticsStore = useAnalyticsStore()
const hasError = ref(false)

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
    hasError.value = true
  }
})

async function retry() {
  hasError.value = false
  await fetchDashboard(true)
  await analyticsStore.fetchHeatmap()
  await analyticsStore.fetchWeekly()
}
</script>

<template>
  <div class="dashboard">
    <DashboardHeader />
    <ErrorCard
      v-if="hasError"
      message="Não foi possível carregar o dashboard."
      :on-retry="retry"
    />
    <template v-else>
      <OnboardingBanner />
      <LogSessionWidget class="widget-full dashboard-register" />
      <template v-if="analyticsStore.isLoading && !analyticsStore.dashboard">
        <div class="widgets widgets--skeleton">
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
        class="widgets widgets--animate"
      >
        <div class="widgets__item widgets__item--0">
          <TodaySummaryCard />
        </div>
        <template v-if="analyticsStore.userMetrics">
          <div class="widgets__item widgets__item--1">
            <KpiCards :metrics="analyticsStore.userMetrics" />
          </div>
        </template>
        <div
          v-else
          class="empty widgets__item widgets__item--1 widgets__item--full"
          role="status"
          aria-live="polite"
        >
          <span
            class="empty__icon"
            aria-hidden="true"
          >📊</span>
          <div class="empty__content">
            <p class="empty__text">
              Nenhum dado ainda. Registre sua primeira sessão no card acima para desbloquear métricas, gráficos e metas.
            </p>
            <p class="empty__hint">
              Use o período (7d, 30d, 90d) no topo para filtrar os dados exibidos.
            </p>
          </div>
        </div>
        <div class="widgets__item widgets__item--2">
          <GoalsWidget />
        </div>
        <div class="widgets__item widgets__item--2b widget-full">
          <TimeSeriesWidget />
        </div>
        <div class="widgets__item widgets__item--3 widget-full">
          <WeeklyComparisonWidget />
        </div>
        <template v-if="analyticsStore.technologyMetrics?.length">
          <div class="widgets__item widgets__item--4 widget-full">
            <TechDistributionWidget
              :metrics="analyticsStore.technologyMetrics"
              :loading="analyticsStore.isRecalculating"
            />
          </div>
        </template>
        <div class="widgets__item widgets__item--5">
          <RemindersWidget />
        </div>
      </div>
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
.empty {
  grid-column: 1 / -1;
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-lg) var(--spacing-xl);
  min-height: 4rem;
  background: color-mix(in srgb, var(--color-bg-soft) 60%, var(--color-bg-card));
  border: 1px dashed var(--color-border);
  border-radius: var(--widget-radius);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  line-height: 1.55;
}
.empty__icon {
  font-size: 1.5rem;
  opacity: 0.85;
  flex-shrink: 0;
}
.empty__content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.empty__text {
  margin: 0;
  font-weight: 500;
}
.empty__hint {
  margin: 0;
  font-size: var(--text-xs);
  opacity: 0.9;
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
.widgets--animate .widgets__item--2b { animation-delay: 0.14s; }
.widgets--animate .widgets__item--3 { animation-delay: 0.17s; }
.widgets--animate .widgets__item--4 { animation-delay: 0.2s; }
.widgets--animate .widgets__item--5 { animation-delay: 0.23s; }
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
.widgets__item--5 {
  min-width: 0;
}
.widgets__item--full {
  grid-column: 1 / -1;
}
/* Tablet: 2 colunas — Today+KPI, Goals+TimeSeries, Weekly full, Tech full, Reminders */
@media (min-width: 640px) {
  .widgets {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--widget-gap);
  }
  .widgets__item--0 { grid-column: 1; }
  .widgets__item--1 { grid-column: 2; }
  .widgets__item--2 { grid-column: 1; }
  .widgets__item--2b { grid-column: 2; }
  .widgets__item--3,
  .widgets__item--4 {
    grid-column: 1 / -1;
  }
  .widgets__item--5 { grid-column: 1; }
  .widgets__item--2b,
  .widgets__item--3,
  .widgets__item--4 {
    min-height: var(--widget-chart-min-height-sm);
  }
}
/* Desktop: grid 12 colunas — coluna esquerda (1–4) Today, Goals, Reminders; direita (5–12) KPI, TimeSeries, Weekly; Tech full */
@media (min-width: 1024px) {
  .widgets {
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: var(--widget-gap);
    grid-auto-rows: minmax(var(--widget-card-min-height), auto);
  }
  .widgets__item--0 {
    grid-column: 1 / 5;
    grid-row: 1;
  }
  .widgets__item--1 {
    grid-column: 5 / 13;
    grid-row: 1;
  }
  .widgets__item--2 {
    grid-column: 1 / 5;
    grid-row: 2;
  }
  .widgets__item--2b {
    grid-column: 5 / 13;
    grid-row: 2;
    min-height: var(--widget-chart-min-height);
  }
  .widgets__item--3 {
    grid-column: 5 / 13;
    grid-row: 3;
    min-height: var(--widget-chart-min-height);
  }
  .widgets__item--5 {
    grid-column: 1 / 5;
    grid-row: 3;
  }
  .widgets__item--4 {
    grid-column: 1 / 13;
    grid-row: 4;
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
  margin-bottom: var(--widget-gap);
}
</style>
