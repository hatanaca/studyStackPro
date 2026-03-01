<script setup lang="ts">
import { useAnalyticsStore } from '@/stores/analytics.store'
import { useDashboard } from '@/features/dashboard/composables/useDashboard'
import BaseButton from '@/components/ui/BaseButton.vue'

const analyticsStore = useAnalyticsStore()
const { fetchDashboard } = useDashboard()

async function handleRefresh() {
  await fetchDashboard(true)
}
</script>

<template>
  <header class="dashboard-header">
    <h1 class="dashboard-header__title">
      Dashboard
    </h1>
    <div class="dashboard-header__actions">
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
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 0.75rem;
}
.dashboard-header__title {
  font-size: 1.5rem;
  margin: 0;
  color: #1e293b;
}
.dashboard-header__actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.recalculating-spinner {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #64748b;
}
.spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
