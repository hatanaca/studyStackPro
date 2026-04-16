<script setup lang="ts">
import { computed } from 'vue'
import Card from 'primevue/card'
import type { UserMetrics } from '@/types/domain.types'

const props = defineProps<{
  metrics: UserMetrics | null
}>()

function formatTotalHours(hours: number): string {
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
      value: formatTotalHours(props.metrics?.total_hours ?? 0),
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
  <section class="kpi-cards" aria-label="Métricas principais">
    <Card
      v-for="item in items"
      :key="item.label"
      class="kpi-card"
      :class="`kpi-card--${item.variant}`"
    >
      <template #content>
        <div class="kpi-card__inner">
          <span v-if="item.icon" class="kpi-card__icon" aria-hidden="true">{{ item.icon }}</span>
          <div class="kpi-card__content">
            <span class="kpi-card__label">{{ item.label }}</span>
            <span class="kpi-card__value">{{ item.value }}</span>
          </div>
        </div>
      </template>
    </Card>
  </section>
</template>

<style scoped>
.kpi-cards {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--widget-gap);
}
@media (min-width: 640px) {
  .kpi-cards {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
.kpi-card {
  min-height: var(--widget-card-min-height);
  border-radius: var(--widget-radius);
  box-shadow: var(--shadow-sm);
  transition:
    box-shadow var(--duration-normal) var(--ease-in-out),
    border-color var(--duration-fast) ease,
    transform var(--duration-fast) var(--ease-out-expo);
}
.kpi-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(calc(-1 * var(--spacing-2xs)));
}
.kpi-card:focus-within {
  box-shadow: var(--shadow-md);
}
.kpi-card__inner {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-lg);
}
.kpi-card__icon {
  font-size: var(--icon-size-md);
  line-height: var(--leading-tight);
  opacity: 0.9;
}
.kpi-card__content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  min-width: 0;
}
.kpi-card__label {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  font-weight: 600;
}
.kpi-card__value {
  font-size: var(--text-xl);
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  color: var(--color-text);
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.kpi-card--primary .kpi-card__value {
  color: var(--color-primary);
}
.kpi-card--success .kpi-card__value {
  color: var(--color-success);
}
.kpi-card--warning .kpi-card__value {
  color: var(--color-warning);
}
.kpi-card--error .kpi-card__value {
  color: var(--color-error);
}
@media (max-width: 640px) {
  .kpi-card__inner {
    flex-direction: row;
    align-items: center;
  }
  .kpi-card {
    min-height: auto;
    padding: var(--widget-padding-sm);
  }
  .kpi-card__content {
    flex: 1;
    min-width: 0;
  }
}
</style>
