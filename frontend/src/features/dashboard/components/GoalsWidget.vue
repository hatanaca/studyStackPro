<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseProgress from '@/components/ui/BaseProgress.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useAnalyticsStore } from '@/stores/analytics.store'
import { useGoalsStore } from '@/stores/goals.store'
const analyticsStore = useAnalyticsStore()
const goalsStore = useGoalsStore()

/** Meta ativa de minutos/semana da store ou valor padrão (5h) */
const weeklyGoal = computed(() => goalsStore.getActiveWeeklyMinutesGoal())
const weeklyGoalMinutes = computed(() => weeklyGoal.value?.target_value ?? 300)
const currentWeekMinutes = computed(() => {
  const series = analyticsStore.timeSeriesData['7d'] ?? []
  return series.reduce((acc, d) => acc + (d.total_minutes ?? 0), 0)
})
const progressPercent = computed(() =>
  Math.min(100, Math.round((currentWeekMinutes.value / weeklyGoalMinutes.value) * 100))
)
const remainingMinutes = computed(() => Math.max(0, weeklyGoalMinutes.value - currentWeekMinutes.value))
const goalReached = computed(() => currentWeekMinutes.value >= weeklyGoalMinutes.value)
const hasCustomGoal = computed(() => !!weeklyGoal.value)

onMounted(() => {
  goalsStore.fetchGoals()
})

function formatMinutes(m: number): string {
  const h = Math.floor(m / 60)
  const min = m % 60
  if (h > 0 && min > 0) return `${h}h ${min}min`
  if (h > 0) return `${h}h`
  return `${min}min`
}
</script>

<template>
  <BaseCard
    title="Meta semanal"
    class="goals-widget"
  >
    <template #actions>
      <RouterLink to="/goals">
        <BaseButton
          variant="ghost"
          size="sm"
        >
          {{ hasCustomGoal ? 'Gerenciar' : 'Criar meta' }}
        </BaseButton>
      </RouterLink>
    </template>
    <div class="goals-widget__content">
      <p class="goals-widget__desc">
        Esta semana você estudou
        <strong>{{ formatMinutes(currentWeekMinutes) }}</strong>
        de uma meta de
        <strong>{{ formatMinutes(weeklyGoalMinutes) }}</strong>.
      </p>
      <BaseProgress
        :value="progressPercent"
        :max="100"
        size="lg"
        :variant="goalReached ? 'success' : 'primary'"
        show-label
        class="goals-widget__progress"
      />
      <p
        v-if="!goalReached && remainingMinutes > 0"
        class="goals-widget__remaining"
      >
        Faltam {{ formatMinutes(remainingMinutes) }} para bater a meta.
      </p>
      <p
        v-else-if="goalReached"
        class="goals-widget__success"
      >
        Meta da semana atingida.
      </p>
      <p
        v-if="!hasCustomGoal"
        class="goals-widget__hint"
      >
        Crie uma meta em Metas para personalizar seu objetivo.
      </p>
    </div>
  </BaseCard>
</template>

<style scoped>
.goals-widget :deep(.base-card__body) {
  padding: var(--widget-padding);
}
.goals-widget__content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  min-height: var(--widget-card-min-height);
  justify-content: center;
}
.goals-widget__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
  line-height: 1.5;
}
.goals-widget__desc strong {
  color: var(--color-text);
  font-weight: 600;
}
.goals-widget__progress {
  margin-top: var(--spacing-xs);
}
.goals-widget__remaining {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: 0;
}
.goals-widget__success {
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-success);
  margin: 0;
}
.goals-widget__hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: var(--spacing-xs) 0 0;
}
.goals-widget a {
  text-decoration: none;
}
</style>
