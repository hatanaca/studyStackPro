<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import LineChart from '@/components/charts/LineChart.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
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
  router.push('/sessions')
}
</script>

<template>
  <div
    class="time-series-widget"
    role="region"
    aria-label="Tempo estudado por dia"
  >
    <div class="header">
      <div class="header__title-row">
        <h3 class="title">
          Tempo estudado por dia ({{ periodLabel }})
        </h3>
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
      <p
        v-if="totalInPeriod && hasData"
        class="total-period"
      >
        Total no período: {{ totalInPeriod }}
      </p>
    </div>
    <div
      v-if="analyticsStore.timeSeriesLoading || analyticsStore.isRecalculating"
      class="chart-skeleton"
    >
      <SkeletonLoader
        v-for="i in 8"
        :key="i"
        height="1.25rem"
        class="skeleton-line"
      />
    </div>
    <LineChart
      v-else-if="hasData"
      :data="chartData"
      value-unit="hours"
      tall
      aria-label="Gráfico de tempo estudado por dia no período selecionado"
    />
    <EmptyState
      v-else
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
  overflow: hidden;
  min-height: var(--widget-chart-min-height-sm);
}
@media (min-width: var(--screen-sm)) {
  .time-series-widget {
    min-height: var(--widget-chart-min-height-tall);
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
.chart-skeleton {
  min-height: var(--widget-chart-min-height-sm);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-md) 0;
}
.skeleton-line {
  width: 90%;
}
@media (max-width: 480px) {
  .time-series-widget {
    padding: var(--widget-padding-sm);
  }
  .header__title-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
