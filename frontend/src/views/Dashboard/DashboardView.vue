<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useDashboard } from '@/features/dashboard/composables/useDashboard'
import { useAnalyticsStore } from '@/stores/analytics.store'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import DashboardHeader from '@/features/dashboard/components/DashboardHeader.vue'
import KpiCards from '@/features/dashboard/components/KpiCards.vue'
import TechDistributionWidget from '@/features/dashboard/components/TechDistributionWidget.vue'
import TimeSeriesWidget from '@/features/dashboard/components/TimeSeriesWidget.vue'
import WeeklyComparisonWidget from '@/features/dashboard/components/WeeklyComparisonWidget.vue'
import HeatmapWidget from '@/features/dashboard/components/HeatmapWidget.vue'

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
        class="widgets"
      >
        <KpiCards
          v-if="analyticsStore.userMetrics"
          :metrics="analyticsStore.userMetrics"
        />
        <p
          v-else
          class="empty"
        >
          Nenhum dado ainda. Registre sessões de estudo para ver métricas.
        </p>
        <TimeSeriesWidget class="widget-full" />
        <WeeklyComparisonWidget class="widget-full" />
        <TechDistributionWidget
          v-if="analyticsStore.technologyMetrics?.length"
          :metrics="analyticsStore.technologyMetrics"
          :loading="analyticsStore.isRecalculating"
        />
        <HeatmapWidget
          :data="analyticsStore.heatmap"
          :loading="analyticsStore.heatmapLoading"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.dashboard {
  max-width: 1400px;
}
.empty {
  color: #64748b;
  grid-column: 1 / -1;
}
.widgets {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}
@media (min-width: 640px) {
  .widgets {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (min-width: 1024px) {
  .widgets {
    grid-template-columns: repeat(4, 1fr);
  }
}
.kpi-skeleton {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
}
.kpi-card-skeleton {
  background: #fff;
  padding: 1rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.mt-2 {
  margin-top: 0.5rem;
}
.widget-full {
  grid-column: 1 / -1;
}
</style>
