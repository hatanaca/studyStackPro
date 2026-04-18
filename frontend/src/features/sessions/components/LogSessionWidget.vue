<script setup lang="ts">
import { ref } from 'vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import LogSessionForm from './LogSessionForm.vue'
import type { SessionSavedPayload } from './LogSessionForm.vue'
import { useAnalyticsStore } from '@/stores/analytics.store'

const analyticsStore = useAnalyticsStore()
const showDialog = ref(false)

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
  showDialog.value = false
}
</script>

<template>
  <section class="log-session-widget">
    <div class="log-session-widget__head">
      <h2 class="log-session-widget__title">Registrar estudo</h2>
      <Button
        label="Registrar sessão"
        icon="pi pi-pencil"
        size="small"
        @click="showDialog = true"
      />
    </div>

    <Dialog
      v-model:visible="showDialog"
      header="Registrar sessão"
      modal
      :style="{ width: 'min(90vw, 420px)' }"
      :dismissable-mask="true"
      @hide="showDialog = false"
    >
      <LogSessionForm
        show-cancel
        @success="onSuccess"
        @cancel="showDialog = false"
      />
    </Dialog>
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
  gap: var(--spacing-md);
  box-shadow: var(--card-chrome-shadow);
}
.log-session-widget__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-lg);
  flex-wrap: wrap;
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
