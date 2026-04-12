<script setup lang="ts">
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
  <section class="log-session-widget">
    <h2 class="log-session-widget__title">Registrar estudo</h2>
    <LogSessionForm @success="onSuccess" />
  </section>
</template>

<style scoped>
.log-session-widget {
  background: var(--color-bg-card);
  border: var(--card-chrome-border);
  border-radius: var(--card-chrome-radius);
  padding: var(--spacing-xl);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
  box-shadow: var(--card-chrome-shadow);
}
.log-session-widget__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--text-lg);
  font-weight: 700;
  color: var(--color-text);
  line-height: var(--leading-tight);
  letter-spacing: var(--tracking-tight);
}
</style>
