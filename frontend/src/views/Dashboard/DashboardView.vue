<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useDashboard } from '@/composables/useDashboard'
import { useAnalyticsStore } from '@/stores/analytics.store'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import TechDistributionWidget from '@/components/dashboard/TechDistributionWidget.vue'
import HeatmapWidget from '@/components/dashboard/HeatmapWidget.vue'
import { analyticsApi } from '@/api/modules/analytics.api'

const { fetchDashboard } = useDashboard()
const analyticsStore = useAnalyticsStore()
const hasError = ref(false)
const heatmapData = ref<{ date: string; total_minutes: number }[]>([])
const heatmapLoading = ref(false)

async function fetchHeatmap() {
  heatmapLoading.value = true
  try {
    const { data } = await analyticsApi.getHeatmap()
    if (data.success && Array.isArray(data.data)) heatmapData.value = data.data
  } finally {
    heatmapLoading.value = false
  }
}

onMounted(async () => {
  try {
    await Promise.all([fetchDashboard(), fetchHeatmap()])
  } catch {
    hasError.value = true
  }
})

async function retry() {
  hasError.value = false
  await fetchDashboard()
}
</script>

<template>
  <div class="dashboard">
    <h1>Dashboard</h1>
    <ErrorCard
      v-if="hasError"
      message="Não foi possível carregar o dashboard."
      :on-retry="retry"
    />
    <template v-else>
      <template v-if="analyticsStore.isLoading && !analyticsStore.dashboard">
        <div class="widgets widgets--skeleton">
          <section class="kpi-cards">
            <div v-for="i in 3" :key="i" class="kpi-card">
              <SkeletonLoader width="60%" height="0.875rem" />
              <SkeletonLoader width="80%" height="1.5rem" class="mt-2" />
            </div>
          </section>
        </div>
      </template>
      <div v-else class="widgets">
        <section v-if="analyticsStore.userMetrics" class="kpi-cards">
          <div class="kpi-card">
            <span class="label">Total de sessões</span>
            <span class="value">{{ analyticsStore.userMetrics.total_sessions }}</span>
          </div>
          <div class="kpi-card">
            <span class="label">Total de horas</span>
            <span class="value">{{ analyticsStore.userMetrics.total_hours ?? 0 }}</span>
          </div>
          <div class="kpi-card">
            <span class="label">Streak atual</span>
            <span class="value">{{ analyticsStore.userMetrics.current_streak_days ?? 0 }} dias</span>
          </div>
        </section>
        <p v-else class="empty">Nenhum dado ainda. Registre sessões de estudo para ver métricas.</p>
        <TechDistributionWidget
          v-if="analyticsStore.technologyMetrics?.length"
          :metrics="analyticsStore.technologyMetrics"
          :loading="analyticsStore.isRecalculating"
        />
        <HeatmapWidget
          :data="heatmapData"
          :loading="heatmapLoading"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.dashboard h1 {
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
}
.empty {
  color: #64748b;
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
.kpi-cards {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
}
.kpi-card {
  background: #fff;
  padding: 1rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.kpi-card .label {
  display: block;
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}
.kpi-card .value {
  font-size: 1.5rem;
  font-weight: 600;
}
.mt-2 {
  margin-top: 0.5rem;
}
</style>
