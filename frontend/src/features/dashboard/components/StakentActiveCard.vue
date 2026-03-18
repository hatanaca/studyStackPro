<script setup lang="ts">
import { ref, computed, defineAsyncComponent } from 'vue'
import { useAnalyticsStore } from '@/stores/analytics.store'
import { useDashboard } from '@/features/dashboard/composables/useDashboard'
const LogSessionWidget = defineAsyncComponent(() => import('@/features/sessions/components/LogSessionWidget.vue'))
const TechDistributionWidget = defineAsyncComponent(() => import('@/features/dashboard/components/TechDistributionWidget.vue'))
const TimeSeriesWidget = defineAsyncComponent(() => import('@/features/dashboard/components/TimeSeriesWidget.vue'))
const GoalsWidget = defineAsyncComponent(() => import('@/features/dashboard/components/GoalsWidget.vue'))

const analyticsStore = useAnalyticsStore()
const { fetchDashboard } = useDashboard()
const activeTab = ref('overview')

async function handleRefresh() {
  await fetchDashboard(true)
}

const tabs = [
  { id: 'overview', label: 'Visão geral' },
  { id: 'tech', label: 'Por tecnologia' },
  { id: 'period', label: 'Por período' },
  { id: 'goals', label: 'Metas' },
]

const lastUpdate = computed(() => {
  const t = analyticsStore.lastFetchAt
  if (!t) return 'Nunca'
  const diff = (Date.now() - t.getTime()) / 60000
  if (diff < 1) return 'Agora'
  if (diff < 60) return `${Math.floor(diff)} min atrás`
  return `${Math.floor(diff / 60)} h atrás`
})

</script>

<template>
  <section class="stakent-active">
    <header class="stakent-active__header">
      <h3 class="stakent-active__title">
        Sua atividade de estudo
      </h3>
      <div class="stakent-active__meta">
        <span class="stakent-active__updated">Última atualização – {{ lastUpdate }}</span>
        <div class="stakent-active__actions">
          <button
            type="button"
            class="stakent-active__icon-btn"
            aria-label="Atualizar"
            @click="handleRefresh"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="18"
              height="18"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            ><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" /><path d="M3 3v5h5" /><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" /><path d="M16 21h5v-5" /></svg>
          </button>
        </div>
      </div>
    </header>
    <div
      class="stakent-active__tabs"
      role="tablist"
    >
      <button
        v-for="t in tabs"
        :id="`stakent-tab-${t.id}`"
        :key="t.id"
        type="button"
        role="tab"
        :aria-selected="activeTab === t.id"
        :aria-controls="`stakent-panel-${t.id}`"
        class="stakent-active__tab"
        :class="{ active: activeTab === t.id }"
        @click="activeTab = t.id"
      >
        {{ t.label }}
      </button>
    </div>
    <div
      :id="`stakent-panel-${activeTab}`"
      class="stakent-active__body"
      role="tabpanel"
      :aria-labelledby="`stakent-tab-${activeTab}`"
    >
      <div v-if="activeTab === 'overview'" class="stakent-active__panel">
        <LogSessionWidget />
      </div>
      <div v-else-if="activeTab === 'tech'" class="stakent-active__panel">
        <TechDistributionWidget
          v-if="analyticsStore.technologyMetrics?.length"
          :metrics="analyticsStore.technologyMetrics"
          :loading="analyticsStore.isRecalculating"
        />
        <p
          v-else
          class="stakent-active__empty"
        >
          Nenhum dado ainda. Registre sessões para ver a distribuição por tecnologia.
        </p>
      </div>
      <div v-else-if="activeTab === 'period'" class="stakent-active__panel">
        <TimeSeriesWidget />
      </div>
      <div v-else-if="activeTab === 'goals'" class="stakent-active__panel">
        <GoalsWidget />
      </div>
    </div>
  </section>
</template>

<style scoped>
.stakent-active {
  background: var(--color-bg-card);
  border-radius: var(--radius-card, var(--radius-lg));
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-card, var(--shadow-sm));
  overflow: hidden;
}
[data-theme='dark'] .app-layout.stakent-style .stakent-active {
  box-shadow: var(--shadow-card), var(--shadow-glow);
}
.stakent-active__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-sm);
  padding: var(--spacing-lg) var(--spacing-xl);
  border-bottom: 1px solid var(--color-border);
}
.stakent-active__title {
  margin: 0;
  font-size: var(--text-base);
  font-weight: 700;
  color: var(--color-text);
}
.stakent-active__meta {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
}
.stakent-active__updated {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
.stakent-active__actions {
  display: flex;
  gap: var(--spacing-xs);
}
.stakent-active__icon-btn {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  color: var(--color-text-muted);
  border-radius: var(--radius-md);
  cursor: pointer;
}
.stakent-active__icon-btn:hover {
  background: var(--color-bg-soft);
  color: var(--color-primary);
}
.stakent-active__tabs {
  display: flex;
  gap: 0;
  padding: 0 var(--spacing-xl);
  border-bottom: 1px solid var(--color-border);
  background: var(--color-bg-soft);
}
.stakent-active__tab {
  padding: var(--spacing-sm) var(--spacing-lg);
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-text-muted);
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  cursor: pointer;
  transition: color var(--duration-fast) ease, border-color var(--duration-fast) ease;
}
.stakent-active__tab:hover {
  color: var(--color-text);
}
.stakent-active__tab.active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
}
.stakent-active__body {
  padding: var(--spacing-xl);
  min-height: 280px;
}
.stakent-active__panel {
  animation: fadeIn 0.2s ease;
}
.stakent-active__empty {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  text-align: center;
  padding: var(--spacing-2xl);
}
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
