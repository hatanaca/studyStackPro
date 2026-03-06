<script setup lang="ts">
import { computed } from 'vue'
import type { UserMetrics } from '@/types/domain.types'

const props = defineProps<{
  metrics: UserMetrics | null
}>()

const items = computed(() => [
  { label: 'Total de sessões', value: props.metrics?.total_sessions ?? 0 },
  { label: 'Total de horas', value: props.metrics?.total_hours ?? 0 },
  {
    label: 'Streak atual',
    value: props.metrics?.current_streak_days != null
      ? `${props.metrics.current_streak_days} dias`
      : '0 dias',
  },
])
</script>

<template>
  <section class="kpi-cards">
    <div
      v-for="(item, i) in items"
      :key="i"
      class="kpi-card"
    >
      <span class="label">{{ item.label }}</span>
      <span class="value">{{ item.value }}</span>
    </div>
  </section>
</template>

<style scoped>
.kpi-cards {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--widget-gap);
}
@media (min-width: 480px) {
  .kpi-cards {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--widget-gap);
  }
}
.kpi-card {
  background: var(--color-bg-card);
  padding: var(--widget-padding);
  border-radius: var(--widget-radius);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  min-height: var(--widget-card-min-height);
  display: flex;
  flex-direction: column;
  justify-content: center;
  transition: box-shadow var(--duration-normal) var(--ease-in-out),
    border-color var(--duration-fast) ease,
    transform var(--duration-fast) var(--ease-out-expo);
}
.kpi-card:hover {
  box-shadow: var(--shadow-md);
  border-color: var(--color-primary-soft);
  transform: translateY(-2px);
}
.kpi-card .label {
  display: block;
  font-size: var(--widget-title-size);
  color: var(--widget-title-color);
  margin-bottom: var(--spacing-xs);
  font-weight: var(--widget-title-weight);
}
.kpi-card .value {
  font-size: var(--text-xl);
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: -0.02em;
  line-height: 1.25;
}
@media (max-width: 479px) {
  .kpi-card {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    min-height: auto;
    padding: var(--widget-padding-sm);
  }
  .kpi-card .label {
    margin-bottom: 0;
  }
  .kpi-card .value {
    font-size: var(--text-lg);
  }
}
</style>
