<script setup lang="ts">
import { computed } from 'vue'
import type { TimeSeriesPeriod } from '@/stores/analytics.store'

const emit = defineEmits<{
  'update:modelValue': [value: TimeSeriesPeriod]
}>()

const defaultOptions: Array<{ value: TimeSeriesPeriod; label: string }> = [
  { value: '7d', label: '7 dias' },
  { value: '30d', label: '30 dias' },
  { value: '90d', label: '90 dias' },
]

const props = withDefaults(
  defineProps<{
    modelValue: TimeSeriesPeriod
    options?: Array<{ value: TimeSeriesPeriod; label: string }>
  }>(),
  { options: () => defaultOptions }
)

const resolvedOptions = computed(() => props.options ?? defaultOptions)

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
  gap: 0.25rem;
}
.period-btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  border-radius: 0.25rem;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  cursor: pointer;
}
.period-btn:hover {
  background: #f8fafc;
  color: #334155;
}
.period-btn.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: #fff;
}
</style>
