<script setup lang="ts">
import Skeleton from 'primevue/skeleton'
import SparklineChart from '@/components/charts/SparklineChart.vue'
import type { KpiCard } from '@/types/chart.types'

defineProps<{
  data: KpiCard[]
  loading?: boolean
}>()
</script>

<template>
  <div class="kpi">
    <template v-if="loading">
      <div v-for="i in 6" :key="i" class="kpi__card kpi__card--skeleton">
        <Skeleton width="60%" height="0.75rem" />
        <Skeleton width="40%" height="1.25rem" />
      </div>
    </template>
    <template v-else>
      <div
        v-for="(kpi, idx) in data"
        :key="kpi.label"
        class="kpi__card animate-bounce-in"
        :class="`stagger-${idx + 1}`"
      >
        <div
          class="kpi__glow"
          :style="{ background: `radial-gradient(ellipse, ${kpi.color}15 0%, transparent 70%)` }"
        />
        <span class="kpi__label">{{ kpi.label }}</span>
        <span class="kpi__value" :style="{ color: kpi.color }">{{ kpi.value }}</span>
        <SparklineChart
          v-if="kpi.sparkline?.length"
          :data="kpi.sparkline"
          :color="kpi.color"
          :height="36"
          class="kpi__sparkline"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.kpi {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-sm);
}
@media (min-width: 640px) {
  .kpi {
    grid-template-columns: repeat(3, 1fr);
  }
}
@media (min-width: 1024px) {
  .kpi {
    grid-template-columns: repeat(6, 1fr);
  }
}
.kpi__card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xs);
  padding: var(--spacing-lg) var(--spacing-md);
  background: linear-gradient(
    135deg,
    color-mix(in srgb, var(--color-bg-card) 95%, transparent) 0%,
    color-mix(in srgb, var(--color-bg-soft) 85%, transparent) 100%
  );
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid color-mix(in srgb, var(--color-primary) 8%, transparent);
  border-radius: var(--radius-xl);
  overflow: hidden;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.kpi__card:hover {
  border-color: color-mix(in srgb, var(--color-primary) 20%, transparent);
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--color-primary) 5%, transparent),
    0 4px 20px color-mix(in srgb, var(--color-primary) 10%, transparent);
  transform: translateY(-3px) scale(1.02);
}
.kpi__glow {
  position: absolute;
  top: -30%;
  right: -20%;
  width: 60%;
  height: 100%;
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}
.kpi__card:hover .kpi__glow {
  opacity: 1;
}
.kpi__card--skeleton {
  min-height: 5.5rem;
  justify-content: center;
  gap: var(--spacing-sm);
}
.kpi__label {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  position: relative;
  z-index: 1;
}
.kpi__value {
  font-size: var(--text-xl);
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  line-height: 1.1;
  position: relative;
  z-index: 1;
}
.kpi__sparkline {
  margin-top: auto;
  position: relative;
  z-index: 1;
  opacity: 0.8;
}
</style>
