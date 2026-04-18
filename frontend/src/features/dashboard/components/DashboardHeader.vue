<script setup lang="ts">
import { computed } from 'vue'
import { useAnalyticsStore } from '@/stores/analytics.store'
import { useDashboard } from '@/features/dashboard/composables/useDashboard'
import Button from 'primevue/button'
import NotificationCenter from '@/features/notifications/components/NotificationCenter.vue'

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
  const streak = metrics.current_streak_days ?? 0
  return [
    { label: 'Total de horas', value: formatHours(metrics.total_hours ?? 0) },
    { label: 'Sessões', value: String(metrics.total_sessions ?? 0) },
    {
      label: 'Streak atual',
      value: streak > 0 ? `${streak} ${streak === 1 ? 'dia' : 'dias'}` : '0 dias',
    },
  ]
})

async function handleRefresh() {
  await Promise.all([
    fetchDashboard(true),
    analyticsStore.fetchHeatmap(),
    analyticsStore.fetchWeekly(),
    analyticsStore.fetchTimeSeries('7d'),
    analyticsStore.fetchTimeSeries('30d'),
    analyticsStore.fetchTimeSeries('90d'),
  ]).catch(() => {})
}
</script>

<template>
  <header class="dashboard-header">
    <div class="dashboard-header__head">
      <h1 class="dashboard-header__title">Dashboard</h1>
      <p class="dashboard-header__desc">
        Visão geral das suas métricas, metas e atividade de estudo.
      </p>
      <div v-if="headerSummary.length" class="dashboard-header__summary">
        <div v-for="item in headerSummary" :key="item.label" class="dashboard-header__summary-item">
          <span class="dashboard-header__summary-label">{{ item.label }}</span>
          <strong class="dashboard-header__summary-value">{{ item.value }}</strong>
        </div>
      </div>
    </div>
    <div class="dashboard-header__toolbar">
      <div class="dashboard-header__actions">
        <NotificationCenter />
        <span
          v-if="analyticsStore.isRecalculating"
          class="recalculating-spinner"
          title="Atualizando métricas..."
          role="status"
          aria-live="polite"
        >
          <span class="spinner" aria-hidden="true" />
          Atualizando...
        </span>
        <Button
          label="Atualizar"
          severity="secondary"
          variant="outlined"
          size="small"
          :disabled="analyticsStore.isRecalculating"
          aria-label="Atualizar dados do dashboard"
          @click="handleRefresh"
        />
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
  gap: var(--spacing-lg);
  padding: var(--spacing-xl);
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
  font-family: var(--font-display);
  font-size: var(--text-xl);
  font-weight: 700;
  letter-spacing: var(--tracking-tight);
  margin: 0;
  color: var(--color-text);
  line-height: var(--leading-tight);
}
.dashboard-header__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
  line-height: var(--leading-snug);
  max-width: 42ch;
}
.dashboard-header__summary {
  margin-top: var(--spacing-sm);
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--spacing-sm);
}
.dashboard-header__summary-item {
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: color-mix(in srgb, var(--color-bg-soft) 88%, var(--color-bg-card));
  min-width: 0;
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.dashboard-header__summary-item:hover {
  border-color: color-mix(in srgb, var(--color-primary) 32%, var(--color-border));
  box-shadow: var(--shadow-sm);
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
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  color: var(--color-text);
  line-height: var(--leading-snug);
}
.dashboard-header__toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--spacing-lg);
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
  width: var(--icon-size-sm);
  height: var(--icon-size-sm);
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
@media (min-width: 768px) {
  .dashboard-header__title {
    font-size: var(--text-2xl);
  }
}
@media (max-width: 640px) {
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
