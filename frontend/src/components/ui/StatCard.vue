<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string
    value: string | number
    /** Ícone (emoji ou texto) */
    icon?: string
    /** Cor de destaque: primary, success, warning, error */
    variant?: 'default' | 'primary' | 'success' | 'warning' | 'error'
    /** Tendência: up, down, neutral */
    trend?: 'up' | 'down' | 'neutral'
    trendLabel?: string
  }>(),
  { icon: '', variant: 'default', trend: undefined, trendLabel: '' }
)
</script>

<template>
  <div
    class="stat-card"
    :class="[`stat-card--${variant}`, trend ? `stat-card--trend-${trend}` : '']"
  >
    <div
      v-if="icon"
      class="stat-card__icon"
      aria-hidden="true"
    >
      {{ icon }}
    </div>
    <div class="stat-card__content">
      <span class="stat-card__label">{{ label }}</span>
      <span class="stat-card__value">{{ value }}</span>
      <span
        v-if="trend && trendLabel"
        class="stat-card__trend"
        :aria-label="trendLabel"
      >
        {{ trend === 'up' ? '↑' : trend === 'down' ? '↓' : '→' }} {{ trendLabel }}
      </span>
    </div>
  </div>
</template>

<style scoped>
.stat-card {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-md);
  padding: var(--widget-padding);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  transition: box-shadow var(--duration-fast) ease, border-color var(--duration-fast) ease;
}
.stat-card:hover {
  box-shadow: var(--shadow-sm);
  border-color: color-mix(in srgb, var(--color-primary) 25%, var(--color-border));
}
.stat-card__icon {
  font-size: 1.25rem;
  line-height: 1;
  opacity: 0.9;
}
.stat-card__content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  min-width: 0;
}
.stat-card__label {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  font-weight: 600;
}
.stat-card__value {
  font-size: var(--text-xl);
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: -0.02em;
  line-height: 1.2;
}
.stat-card--primary .stat-card__value { color: var(--color-primary); }
.stat-card--success .stat-card__value { color: var(--color-success); }
.stat-card--warning .stat-card__value { color: var(--color-warning); }
.stat-card--error .stat-card__value { color: var(--color-error); }
.stat-card__trend {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
.stat-card--trend-up .stat-card__trend { color: var(--color-success); }
.stat-card--trend-down .stat-card__trend { color: var(--color-error); }
</style>
