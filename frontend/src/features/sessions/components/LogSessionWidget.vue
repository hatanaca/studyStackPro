<script setup lang="ts">
import BaseCard from '@/components/ui/BaseCard.vue'
import LogSessionForm from './LogSessionForm.vue'
import { useDashboard } from '@/features/dashboard/composables/useDashboard'
import { useAnalyticsStore } from '@/stores/analytics.store'

const { fetchDashboard } = useDashboard()
const analyticsStore = useAnalyticsStore()

async function onSuccess() {
  await Promise.all([
    fetchDashboard(true),
    analyticsStore.fetchHeatmap(),
    analyticsStore.fetchWeekly(),
  ])
  if (analyticsStore.selectedPeriod) {
    await analyticsStore.fetchTimeSeries(analyticsStore.selectedPeriod)
  }
}
</script>

<template>
  <BaseCard title="Registrar estudo">
    <LogSessionForm
      @success="onSuccess"
    />
  </BaseCard>
</template>
