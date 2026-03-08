<script setup lang="ts">
import { computed } from 'vue'
import StatCard from '@/components/ui/StatCard.vue'
import type { UserMetrics } from '@/types/domain.types'

const props = defineProps<{
  metrics: UserMetrics | null
}>()

function formatHours(hours: number): string {
  if (hours <= 0) return '0h'
  const h = Math.floor(hours)
  const m = Math.round((hours - h) * 60)
  if (m === 0) return `${h}h`
  return `${h}h ${m}min`
}

type StatVariant = 'default' | 'primary' | 'success' | 'warning' | 'error'

const items = computed(() => {
  const streakDays = props.metrics?.current_streak_days ?? 0
  const streakVariant: StatVariant = streakDays > 0 ? 'success' : 'default'
  return [
    {
      label: 'Total de sessões',
      value: props.metrics?.total_sessions ?? 0,
      icon: '📚',
      variant: 'default' as StatVariant,
    },
    {
      label: 'Total de horas',
      value: formatHours(props.metrics?.total_hours ?? 0),
      icon: '⏱',
      variant: 'primary' as StatVariant,
    },
    {
      label: 'Streak atual',
      value: streakDays > 0 ? `${streakDays} ${streakDays === 1 ? 'dia' : 'dias'}` : '0 dias',
      icon: '🔥',
      variant: streakVariant,
    },
  ]
})
</script>

<template>
  <section
    class="kpi-cards"
    aria-label="Métricas principais"
  >
    <StatCard
      v-for="(item, i) in items"
      :key="i"
      :label="item.label"
      :value="item.value"
      :icon="item.icon"
      :variant="item.variant"
    />
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
  }
}
.kpi-cards :deep(.stat-card) {
  min-height: var(--widget-card-min-height);
  border-radius: var(--widget-radius);
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--duration-normal) var(--ease-in-out),
    border-color var(--duration-fast) ease,
    transform var(--duration-fast) var(--ease-out-expo);
}
.kpi-cards :deep(.stat-card:hover) {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}
@media (max-width: 479px) {
  .kpi-cards :deep(.stat-card) {
    flex-direction: row;
    align-items: center;
    min-height: auto;
    padding: var(--widget-padding-sm);
  }
  .kpi-cards :deep(.stat-card__content) {
    flex: 1;
    min-width: 0;
  }
}
</style>
