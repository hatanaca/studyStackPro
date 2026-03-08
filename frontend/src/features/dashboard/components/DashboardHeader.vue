<script setup lang="ts">
import { computed } from 'vue'
import { useAnalyticsStore } from '@/stores/analytics.store'
import { useDashboard } from '@/features/dashboard/composables/useDashboard'
import BaseButton from '@/components/ui/BaseButton.vue'
import NotificationCenter from '@/features/notifications/components/NotificationCenter.vue'
import PeriodSelector from './PeriodSelector.vue'
import type { TimeSeriesPeriod } from '@/stores/analytics.store'

const analyticsStore = useAnalyticsStore()
const { fetchDashboard } = useDashboard()

function formatHours(hours: number): string {
  if (hours <= 0) return '0h'
  const h = Math.floor(hours)
  const m = Math.round((hours - h) * 60)
  if (m === 0) return `${h}h`
  return `${h}h ${m}min`
}

const headerSummary = computed(() => {
  const metrics = analyticsStore.userMetrics
  if (!metrics) return []
  return [
    { label: 'Total de horas', value: formatHours(metrics.total_hours ?? 0) },
    { label: 'Sessões', value: String(metrics.total_sessions ?? 0) },
    {
      label: 'Último estudo',
      value: metrics.last_session_at
        ? new Date(metrics.last_session_at).toLocaleDateString('pt-BR')
        : 'Sem registro',
    },
  ]
})

async function handleRefresh() {
  await fetchDashboard(true)
  analyticsStore.fetchHeatmap().catch(() => {})
  analyticsStore.fetchWeekly().catch(() => {})
  analyticsStore.fetchTimeSeries('7d').catch(() => {})
  analyticsStore.fetchTimeSeries('30d').catch(() => {})
  analyticsStore.fetchTimeSeries('90d').catch(() => {})
}

function onPeriodChange(period: TimeSeriesPeriod) {
  analyticsStore.setSelectedPeriod(period)
  const hasData = analyticsStore.timeSeriesData[period]?.length
  if (!hasData) analyticsStore.fetchTimeSeries(period)
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
      <div
        v-if="headerSummary.length"
        class="dashboard-header__summary"
      >
        <div
          v-for="item in headerSummary"
          :key="item.label"
          class="dashboard-header__summary-item"
        >
          <span class="dashboard-header__summary-label">{{ item.label }}</span>
          <strong class="dashboard-header__summary-value">{{ item.value }}</strong>
        </div>
      </div>
    </div>
    <div class="dashboard-header__toolbar">
      <div
        v-if="analyticsStore.dashboard"
        class="dashboard-header__period"
        aria-label="Período dos gráficos"
      >
        <span class="dashboard-header__period-label">Período:</span>
        <PeriodSelector
          :model-value="analyticsStore.selectedPeriod"
          @update:model-value="onPeriodChange"
        />
      </div>
      <div class="dashboard-header__actions">
        <NotificationCenter />
        <span
          v-if="analyticsStore.isRecalculating"
          class="recalculating-spinner"
          title="Atualizando métricas..."
          role="status"
          aria-live="polite"
        >
          <span
            class="spinner"
            aria-hidden="true"
          />
          Atualizando...
        </span>
        <BaseButton
          variant="outline"
          size="sm"
          :disabled="analyticsStore.isRecalculating"
          aria-label="Atualizar dados do dashboard"
          @click="handleRefresh"
        >
          Atualizar
        </BaseButton>
      </div>
    </div>
  </header>
</template>

<style scoped>
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--page-header-margin-bottom);
  flex-wrap: wrap;
  gap: var(--spacing-md);
  padding: var(--spacing-lg);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}
.dashboard-header__head {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xs);
  min-width: 0;
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
.dashboard-header__summary {
  margin-top: var(--spacing-sm);
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--spacing-sm);
}
.dashboard-header__summary-item {
  padding: var(--spacing-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-soft);
  min-width: 0;
}
.dashboard-header__summary-label {
  display: block;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin-bottom: var(--spacing-2xs);
}
.dashboard-header__summary-value {
  display: block;
  font-size: var(--text-sm);
  color: var(--color-text);
  line-height: 1.3;
}
.dashboard-header__toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--spacing-md);
}
.dashboard-header__period {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}
.dashboard-header__period-label {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
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
  .dashboard-header__summary {
    grid-template-columns: 1fr;
  }
  .dashboard-header__title {
    font-size: var(--text-lg);
  }
  .recalculating-spinner {
    font-size: var(--text-xs);
  }
}
</style>
