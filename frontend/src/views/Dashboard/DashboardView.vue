<script setup lang="ts">
import { onMounted } from 'vue'
import { useAnalyticsStore } from '@/stores/analytics.store'

const analyticsStore = useAnalyticsStore()

onMounted(() => {
  analyticsStore.fetchDashboard()
})
</script>

<template>
  <div class="dashboard">
    <h1>Dashboard</h1>
    <div v-if="analyticsStore.isLoading" class="loading">Carregando...</div>
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
    </div>
  </div>
</template>

<style scoped>
.dashboard h1 {
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
}
.loading,
.empty {
  color: #64748b;
}
.kpi-cards {
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
</style>
