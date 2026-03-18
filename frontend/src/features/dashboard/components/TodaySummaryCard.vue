<script setup lang="ts">
import { computed } from 'vue'
import { useAnalyticsStore } from '@/stores/analytics.store'

const analyticsStore = useAnalyticsStore()

const formattedTime = computed(() => {
  const mins = analyticsStore.todayMinutes
  if (mins <= 0) return '0min'
  const h = Math.floor(mins / 60)
  const m = mins % 60
  if (h > 0 && m > 0) return `${h}h ${m}min`
  if (h > 0) return `${h}h`
  return `${m}min`
})

const hasActivity = computed(() => analyticsStore.todayMinutes > 0)
</script>

<template>
  <section
    class="today-summary"
    aria-labelledby="today-summary-title"
  >
    <h3
      id="today-summary-title"
      class="today-summary__title"
    >
      <span
        class="today-summary__title-icon"
        aria-hidden="true"
      >📅</span>
      Resumo de Hoje
    </h3>

    <div
      v-if="hasActivity"
      class="today-summary__content"
    >
      <div class="today-summary__stats">
        <div class="today-summary__stat today-summary__stat--primary">
          <span class="today-summary__stat-value">{{ formattedTime }}</span>
          <span class="today-summary__stat-label">Tempo estudado</span>
        </div>
        <div class="today-summary__stat">
          <span class="today-summary__stat-value">{{ analyticsStore.todaySessions }}</span>
          <span class="today-summary__stat-label">{{
            analyticsStore.todaySessions === 1 ? 'Sessão' : 'Sessões'
          }}</span>
        </div>
      </div>

      <div
        v-if="analyticsStore.todayTechnologies.length"
        class="today-summary__techs"
      >
        <span class="today-summary__techs-label">Tecnologias:</span>
        <div class="today-summary__tech-chips">
          <span
            v-for="tm in analyticsStore.todayTechnologies"
            :key="tm.technology.id"
            class="today-summary__chip"
            :style="{ '--chip-color': tm.technology.color || 'var(--color-primary)' }"
          >
            {{ tm.technology.name }}
          </span>
        </div>
      </div>
    </div>

    <p
      v-else
      class="today-summary__empty"
    >
      Nenhuma sessão hoje. Registre um estudo acima para acompanhar seu progresso!
    </p>
  </section>
</template>

<style scoped>
.today-summary {
  background: var(--color-bg-soft);
  color: var(--color-text);
  border-radius: var(--widget-radius);
  padding: var(--widget-padding);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  min-height: var(--widget-card-min-height);
  display: flex;
  flex-direction: column;
  justify-content: center;
  transition: box-shadow var(--duration-normal) var(--ease-in-out),
    border-color var(--duration-fast) ease;
}
.today-summary:hover {
  box-shadow: var(--shadow-md);
  border-color: var(--color-primary);
}
.today-summary__title {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  text-transform: uppercase;
  letter-spacing: var(--tracking-wide);
  color: var(--widget-title-color);
  margin: 0 0 var(--spacing-sm);
}
.today-summary__title-icon {
  font-size: var(--icon-size-sm);
  opacity: 0.9;
}
.today-summary__content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}
.today-summary__stats {
  display: flex;
  gap: var(--spacing-xl);
  flex-wrap: wrap;
}
.today-summary__stat {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xs);
}
.today-summary__stat-value {
  font-size: var(--text-xl);
  font-weight: 700;
  line-height: var(--leading-tight);
  color: var(--color-text);
  letter-spacing: var(--tracking-tight);
}
.today-summary__stat--primary .today-summary__stat-value {
  color: var(--color-primary);
}
.today-summary__stat-label {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
.today-summary__techs {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.today-summary__techs-label {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  flex-shrink: 0;
}
.today-summary__tech-chips {
  display: flex;
  gap: var(--spacing-xs);
  flex-wrap: wrap;
}
.today-summary__chip {
  display: inline-block;
  padding: var(--spacing-2xs) var(--spacing-sm);
  border-radius: 9999px;
  font-size: var(--text-xs);
  font-weight: 500;
  background: color-mix(in srgb, var(--chip-color) 18%, transparent);
  color: var(--chip-color);
  border: 1px solid color-mix(in srgb, var(--chip-color) 35%, transparent);
}
.today-summary__empty {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  margin: 0;
  line-height: var(--leading-normal);
}
@media (max-width: 479px) {
  .today-summary {
    padding: var(--widget-padding-sm);
  }
  .today-summary__stats {
    gap: var(--spacing-xl);
  }
  .today-summary__stat-value {
    font-size: var(--text-xl);
  }
}
</style>
