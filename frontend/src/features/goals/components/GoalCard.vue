<script setup lang="ts">
import { computed } from 'vue'
import Card from 'primevue/card'
import ProgressBar from 'primevue/progressbar'
import Button from 'primevue/button'
import type { Goal } from '@/types/goals.types'
import { GOAL_TYPE_LABELS, GOAL_STATUS_LABELS } from '@/types/goals.types'
import { useGoalsStore } from '@/stores/goals.store'
import { useGoalProgress } from '@/features/goals/composables/useGoalProgress'

const props = defineProps<{
  goal: Goal
}>()

const emit = defineEmits<{
  edit: [goal: Goal]
  delete: [goal: Goal]
}>()

const goalsStore = useGoalsStore()
const { currentValue } = useGoalProgress(props.goal)
const progress = computed(() => goalsStore.getProgress(props.goal, currentValue.value))
const isCompleted = computed(() => props.goal.status === 'completed' || progress.value >= 100)
const typeLabel = computed(() => GOAL_TYPE_LABELS[props.goal.type])
const statusLabel = computed(() => GOAL_STATUS_LABELS[props.goal.status])

function formatValue(): string {
  const g = props.goal
  const cur = currentValue.value
  if (g.type === 'minutes_per_week') return `${cur} / ${g.target_value} min`
  if (g.type === 'sessions_per_week') return `${cur} / ${g.target_value} sessões`
  return `${cur} / ${g.target_value} dias`
}
</script>

<template>
  <Card class="goal-card">
    <template #title>{{ typeLabel }}</template>
    <template #header>
      <span class="goal-card__actions">
        <Button label="Editar" link size="small" severity="secondary" @click="emit('edit', goal)" />
        <Button label="Excluir" link size="small" severity="secondary" @click="emit('delete', goal)" />
      </span>
    </template>
    <template #content>
      <div class="goal-card__body">
        <div class="goal-card__meta">
          <span class="goal-card__status">{{ statusLabel }}</span>
        </div>
        <p class="goal-card__value">{{ formatValue() }}</p>
        <ProgressBar
          :value="progress"
          :show-value="true"
          :severity="isCompleted ? 'success' : 'primary'"
          class="goal-card__progress"
        />
      </div>
    </template>
  </Card>
</template>

<style scoped>
.goal-card {
  transition: box-shadow var(--duration-normal) var(--ease-in-out),
    border-color var(--duration-normal) var(--ease-in-out);
}
.goal-card:hover {
  box-shadow: var(--shadow-card-hover);
  border-color: color-mix(in srgb, var(--color-primary) 30%, var(--color-border));
}
.goal-card__actions {
  display: flex;
  gap: var(--spacing-sm);
}
.goal-card__actions :deep(.p-button) {
  min-height: var(--touch-target-min);
  padding: var(--spacing-sm) var(--spacing-md);
}
.goal-card__actions :deep(.p-button:focus-visible) {
  box-shadow: var(--shadow-focus);
}
.goal-card__body {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
.goal-card__meta {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}
.goal-card__status {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  padding: var(--spacing-xs) var(--spacing-sm);
  background: color-mix(in srgb, var(--color-bg-soft) 80%, var(--color-bg-card));
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  letter-spacing: var(--tracking-wide);
}
.goal-card__value {
  font-size: var(--text-base);
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.goal-card__progress {
  margin-top: var(--spacing-sm);
}
</style>
