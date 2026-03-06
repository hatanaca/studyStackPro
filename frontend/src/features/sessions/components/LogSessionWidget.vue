<script setup lang="ts">
import BaseCard from '@/components/ui/BaseCard.vue'
import LogSessionForm from './LogSessionForm.vue'
import type { SessionSavedPayload } from './LogSessionForm.vue'
import { useAnalyticsStore } from '@/stores/analytics.store'

const analyticsStore = useAnalyticsStore()

function onSuccess(payload: SessionSavedPayload) {
  analyticsStore.addLocalTodaySession(payload.date, payload.durationMinutes, {
    id: payload.technologyId,
    name: payload.technologyName,
    color: payload.technologyColor,
  })

  analyticsStore.fetchDashboard(true).catch(() => {})
  analyticsStore.fetchHeatmap().catch(() => {})
  analyticsStore.fetchWeekly().catch(() => {})
  if (analyticsStore.selectedPeriod) {
    analyticsStore.fetchTimeSeries(analyticsStore.selectedPeriod).catch(() => {})
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
