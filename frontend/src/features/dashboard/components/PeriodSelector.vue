<script setup lang="ts">
import { computed } from 'vue'
import type { TimeSeriesPeriod } from '@/stores/analytics.store'

const emit = defineEmits<{
  'update:modelValue': [value: TimeSeriesPeriod]
}>()

const props = withDefaults(
  defineProps<{
    modelValue: TimeSeriesPeriod
    options?: Array<{ value: TimeSeriesPeriod; label: string }>
  }>(),
  {
    options: () =>
      [
        { value: '7d', label: '7 dias' },
        { value: '30d', label: '30 dias' },
        { value: '90d', label: '90 dias' },
      ] as Array<{ value: TimeSeriesPeriod; label: string }>,
  }
)

const resolvedOptions = computed(() => props.options)

function select(period: TimeSeriesPeriod) {
  emit('update:modelValue', period)
}
</script>

<template>
  <div class="period-selector">
    <button
      v-for="opt in resolvedOptions"
      :key="opt.value"
      type="button"
      class="period-btn"
      :class="{ active: modelValue === opt.value }"
      @click="select(opt.value)"
    >
      {{ opt.label }}
    </button>
  </div>
</template>

<style scoped>
.period-selector {
  display: flex;
  gap: var(--spacing-2xs);
}
.period-btn {
  min-height: var(--input-height-sm);
  padding: var(--spacing-xs) var(--spacing-sm);
  font-size: var(--text-xs);
  font-weight: 600;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
  color: var(--color-text-muted);
  cursor: pointer;
  transition:
    background var(--duration-fast) ease,
    color var(--duration-fast) ease,
    border-color var(--duration-fast) ease;
}
.period-btn:hover {
  background: var(--color-bg-soft);
  color: var(--color-text);
  border-color: var(--color-primary);
}
.period-btn.active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-primary-contrast);
}
.period-btn:focus-visible {
  outline: 2px solid var(--color-focus-ring);
  outline-offset: 2px;
}
</style>
