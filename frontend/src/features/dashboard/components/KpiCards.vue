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
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
}
.kpi-card {
  background: #fff;
  padding: 1rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.kpi-card .label {
  display: block;
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}
.kpi-card .value {
  font-size: 1.5rem;
  font-weight: 600;
}
</style>
