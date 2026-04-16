<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue'
import { useRouter } from 'vue-router'
const LineChart = defineAsyncComponent(() => import('@/components/charts/LineChart.vue'))
import Skeleton from 'primevue/skeleton'
import EmptyState from '@/components/ui/EmptyState.vue'
import { formatMinutesToHoursLabel } from '@/utils/formatters'
import { formatShortDate } from '@/utils/formatters'
import { useAnalyticsStore } from '@/stores/analytics.store'
import PeriodSelector from './PeriodSelector.vue'

const router = useRouter()
const analyticsStore = useAnalyticsStore()

const chartData = computed(() => {
  const data = analyticsStore.timeSeries
  if (!data?.length) return undefined
  return {
    labels: data.map((d) => formatShortDate(d.date)),
    values: data.map((d) => d.total_minutes),
  }
})

const periodLabel = computed(() => {
  const map: Record<string, string> = { '7d': '7 dias', '30d': '30 dias', '90d': '90 dias' }
  return map[analyticsStore.selectedPeriod] ?? analyticsStore.selectedPeriod
})

const totalInPeriod = computed(() => {
  const data = analyticsStore.timeSeries
  if (!data?.length) return null
  const total = data.reduce((sum, d) => sum + (d.total_minutes ?? 0), 0)
  return formatMinutesToHoursLabel(total)
})

const hasData = computed(() => (chartData.value?.values?.length ?? 0) > 0)

function goToRegisterSession() {
  router.push({ name: 'sessions' })
}
</script>

<template>
  <div class="time-series-widget" role="region" aria-label="Tempo estudado por dia">
    <div class="header">
      <div class="header__title-row">
        <h3 class="title">Tempo estudado por dia ({{ periodLabel }})</h3>
        <PeriodSelector
          :model-value="analyticsStore.selectedPeriod"
          @update:model-value="
            (p) => {
              analyticsStore.setSelectedPeriod(p)
              const hasDataForPeriod = analyticsStore.timeSeriesData[p]?.length
              if (!hasDataForPeriod) analyticsStore.fetchTimeSeries(p)
            }
          "
        />
      </div>
      <p v-if="totalInPeriod && hasData" class="total-period">
        Total no período: {{ totalInPeriod }}
      </p>
      <p v-if="hasData && analyticsStore.isRecalculating" class="recalc-hint" role="status">
        Atualizando métricas…
      </p>
    </div>
    <div v-if="analyticsStore.timeSeriesLoading" class="chart-skeleton">
      <Skeleton v-for="i in 8" :key="i" height="1.25rem" class="skeleton-line" />
    </div>
    <LineChart
      v-else-if="hasData"
      :data="chartData"
      value-unit="hours"
      preset="stock"
      tall
      aria-label="Gráfico de tempo estudado por dia no período selecionado"
    />
    <EmptyState
      v-else
      class="time-series-widget__empty"
      title="Nenhum registro neste período"
      description="Registre sessões para ver sua evolução aqui. O gráfico mostra as horas estudadas por dia."
      icon="📈"
      action-label="Registrar sessão"
      :hide-action="false"
      @action="goToRegisterSession"
    />
  </div>
</template>

<style scoped>
.time-series-widget {
  background: var(--color-bg-card);
  border-radius: var(--widget-radius);
  padding: var(--widget-padding);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  min-height: calc(var(--widget-chart-min-height-sm) + 3rem);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  overflow: hidden;
}
@media (min-width: 640px) {
  .time-series-widget {
    min-height: calc(var(--widget-chart-min-height-tall) + 7rem);
  }
}
.header {
  margin-bottom: var(--spacing-xs);
}
.header__title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--spacing-sm);
}
.title {
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  color: var(--widget-title-color);
  margin: 0;
}
.total-period {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: var(--spacing-2xs) 0 0;
  font-variant-numeric: tabular-nums;
}
.recalc-hint {
  font-size: var(--text-xs);
  color: var(--color-primary);
  margin: var(--spacing-2xs) 0 0;
}
.chart-skeleton {
  min-height: var(--widget-chart-min-height);
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) 0;
}
.skeleton-line {
  width: 90%;
}
.time-series-widget__empty {
  margin-top: var(--spacing-xs);
  flex: 1;
  min-height: var(--widget-chart-min-height-sm);
}
.time-series-widget__empty :deep(.empty-state) {
  min-height: 100%;
  border-radius: var(--radius-sm);
}
@media (max-width: 640px) {
  .time-series-widget {
    padding: var(--widget-padding-sm);
  }
  .header__title-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
