<script setup lang="ts">
import { computed } from 'vue'
import type { DateRange } from '@/types/chart.types'
import { toISODateString } from '@/utils/dateUtils'

const props = defineProps<{
  dateRange: DateRange
  selectedTechIds: string[]
}>()

const emit = defineEmits<{
  'update:dateRange': [range: DateRange]
  'toggleTech': [techId: string]
}>()

const quickRanges = [
  { label: '7d', days: 7 },
  { label: '30d', days: 30 },
  { label: '90d', days: 90 },
  { label: '1y', days: 365 },
]

function setQuickRange(days: number) {
  const end = new Date()
  const start = new Date()
  start.setDate(start.getDate() - days)
  emit('update:dateRange', {
    start: toISODateString(start),
    end: toISODateString(end),
  })
}

const startModel = computed({
  get: () => props.dateRange.start,
  set: (val: string) => emit('update:dateRange', { ...props.dateRange, start: val }),
})

const endModel = computed({
  get: () => props.dateRange.end,
  set: (val: string) => emit('update:dateRange', { ...props.dateRange, end: val }),
})
</script>

<template>
  <div class="tb">
    <div class="tb__range">
      <label class="tb__label">Período</label>
      <div class="tb__dates">
        <input v-model="startModel" type="date" class="tb__input" aria-label="Data inicial" />
        <span class="tb__sep">→</span>
        <input v-model="endModel" type="date" class="tb__input" aria-label="Data final" />
      </div>
      <div class="tb__presets">
        <button
          v-for="range in quickRanges"
          :key="range.days"
          class="tb__preset"
          @click="setQuickRange(range.days)"
        >
          {{ range.label }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tb {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  padding: var(--spacing-sm) var(--spacing-lg);
  background: linear-gradient(135deg,
    rgba(8, 8, 14, 0.95) 0%,
    rgba(12, 10, 20, 0.85) 100%
  );
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(139, 92, 246, 0.1);
  border-radius: var(--radius-xl);
  flex-wrap: wrap;
}
.tb__range {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.tb__label {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  white-space: nowrap;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}
.tb__dates {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}
.tb__input {
  padding: 6px 12px;
  border: 1px solid rgba(139, 92, 246, 0.1);
  border-radius: var(--radius-lg);
  background: rgba(0, 0, 0, 0.35);
  color: var(--color-text);
  font-size: var(--text-xs);
  font-family: var(--font-sans);
  transition: all 0.2s ease;
}
.tb__input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
}
.tb__sep {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  opacity: 0.5;
}
.tb__presets {
  display: flex;
  gap: 4px;
}
.tb__preset {
  padding: 4px 12px;
  border: 1px solid rgba(139, 92, 246, 0.08);
  border-radius: var(--radius-md);
  background: rgba(139, 92, 246, 0.04);
  color: var(--color-text-muted);
  font-size: var(--text-2xs);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}
.tb__preset:hover {
  background: rgba(139, 92, 246, 0.15);
  color: var(--color-text);
  border-color: rgba(139, 92, 246, 0.25);
}
</style>
