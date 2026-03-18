<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = withDefaults(
  defineProps<{
    label: string
    value: string | number
    change?: number
    changeLabel?: string
    tag?: string
    chartData?: number[]
    color?: string
    to?: string
  }>(),
  { change: undefined, changeLabel: undefined, tag: '', chartData: () => [], color: undefined, to: undefined }
)

const hasPositiveChange = computed(() => (props.change ?? 0) > 0)
const changeText = computed(() => {
  const c = props.change
  if (c == null) return null
  const sign = c >= 0 ? '+' : ''
  return `${sign}${c.toFixed(2)}%`
})
</script>

<template>
  <article class="stakent-metric-card">
    <div class="stakent-metric-card__tag-row">
      <span class="stakent-metric-card__tag">{{ tag || 'Métrica' }}</span>
      <RouterLink
        v-if="to"
        :to="to"
        class="stakent-metric-card__more"
        aria-label="Ver mais"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
        ><path d="M5 12h14" /><path d="M12 5v14" /></svg>
      </RouterLink>
    </div>
    <div class="stakent-metric-card__content">
      <div class="stakent-metric-card__main">
        <span class="stakent-metric-card__value">{{ value }}</span>
        <span
          v-if="changeText"
          class="stakent-metric-card__change"
          :class="{ positive: hasPositiveChange, negative: !hasPositiveChange }"
        >
          {{ changeText }}
        </span>
      </div>
      <p class="stakent-metric-card__label">
        {{ label }}
      </p>
      <div
        v-if="chartData && chartData.length"
        class="stakent-metric-card__chart"
      >
        <svg
          viewBox="0 0 100 24"
          preserveAspectRatio="none"
          class="stakent-metric-card__sparkline"
        >
          <polyline
            :points="chartData.map((v, i) => `${(chartData.length > 1 ? i / (chartData.length - 1) : 0) * 100},${22 - ((Math.max(...chartData) ? v / Math.max(...chartData) : 0) * 20)}`).join(' ')"
            fill="none"
            :stroke="color ?? 'var(--color-primary)'"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </div>
    </div>
  </article>
</template>

<style scoped>
.stakent-metric-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-card, var(--radius-lg));
  border: 1px solid var(--color-border);
  padding: var(--spacing-lg);
  position: relative;
  overflow: hidden;
  box-shadow: var(--shadow-card, var(--shadow-sm));
  transition: box-shadow var(--duration-normal) ease, border-color var(--duration-fast) ease;
}
[data-theme='dark'] .app-layout.stakent-style .stakent-metric-card {
  box-shadow: var(--shadow-card), var(--shadow-glow);
}
.stakent-metric-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: var(--gradient-card-glow, none);
  pointer-events: none;
}
.stakent-metric-card:hover {
  border-color: color-mix(in srgb, var(--color-primary) 30%, transparent);
  box-shadow: var(--shadow-card-hover, var(--shadow-md));
}
.stakent-metric-card__tag-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-sm);
}
.stakent-metric-card__tag {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  font-weight: 500;
}
.stakent-metric-card__more {
  width: 1.5rem;
  height: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  color: var(--color-text-muted);
  cursor: pointer;
  border-radius: var(--radius-sm);
}
.stakent-metric-card__more:hover {
  color: var(--color-primary);
  background: var(--color-primary-soft);
}
.stakent-metric-card__content {
  position: relative;
  z-index: 1;
}
.stakent-metric-card__main {
  display: flex;
  align-items: baseline;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.stakent-metric-card__value {
  font-size: var(--text-2xl);
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.stakent-metric-card__change {
  font-size: var(--text-sm);
  font-weight: 600;
}
.stakent-metric-card__change.positive {
  color: var(--color-success);
}
.stakent-metric-card__change.negative {
  color: var(--color-error);
}
.stakent-metric-card__label {
  margin: var(--spacing-xs) 0 0;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
}
.stakent-metric-card__chart {
  margin-top: var(--spacing-sm);
  height: 2.5rem;
  border-radius: var(--radius-sm);
  overflow: hidden;
}
.stakent-metric-card__sparkline {
  width: 100%;
  height: 100%;
  display: block;
}
</style>
