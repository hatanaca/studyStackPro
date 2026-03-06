<script setup lang="ts">
import { useAnalyticsStore } from '@/stores/analytics.store'
import { useDashboard } from '@/features/dashboard/composables/useDashboard'
import BaseButton from '@/components/ui/BaseButton.vue'
import NotificationCenter from '@/features/notifications/components/NotificationCenter.vue'

const analyticsStore = useAnalyticsStore()
const { fetchDashboard } = useDashboard()

async function handleRefresh() {
  await fetchDashboard(true)
  analyticsStore.fetchHeatmap().catch(() => {})
  analyticsStore.fetchWeekly().catch(() => {})
  analyticsStore.fetchTimeSeries('7d').catch(() => {})
  analyticsStore.fetchTimeSeries('30d').catch(() => {})
  analyticsStore.fetchTimeSeries('90d').catch(() => {})
}
</script>

<template>
  <header class="dashboard-header">
    <div class="dashboard-header__head">
      <h1 class="dashboard-header__title">
        Dashboard
      </h1>
      <p class="dashboard-header__desc">
        Visão geral das suas métricas, metas e atividade de estudo.
      </p>
    </div>
    <div class="dashboard-header__actions">
      <NotificationCenter />
      <span
        v-if="analyticsStore.isRecalculating"
        class="recalculating-spinner"
        title="Atualizando métricas..."
      >
        <span class="spinner" />
        Atualizando...
      </span>
      <BaseButton
        variant="outline"
        size="sm"
        :disabled="analyticsStore.isRecalculating"
        @click="handleRefresh"
      >
        Atualizar
      </BaseButton>
    </div>
  </header>
</template>

<style scoped>
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--page-header-margin-bottom);
  flex-wrap: wrap;
  gap: var(--spacing-sm);
  padding: var(--spacing-md) var(--spacing-lg);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}
.dashboard-header__head {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xs);
}
.dashboard-header__title {
  font-size: var(--text-xl);
  font-weight: 700;
  letter-spacing: -0.025em;
  margin: 0;
  color: var(--color-text);
  line-height: 1.25;
}
.dashboard-header__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
  line-height: 1.4;
  max-width: 42ch;
}
.dashboard-header__actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}
.recalculating-spinner {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}
.spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
@media (max-width: 480px) {
  .dashboard-header__title {
    font-size: var(--text-lg);
  }
  .recalculating-spinner {
    font-size: var(--text-xs);
  }
}
</style>
