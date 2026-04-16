<script setup lang="ts">
import { ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import TechnologyPicker from '@/features/technologies/components/TechnologyPicker.vue'
import { useTechnologiesStore } from '@/stores/technologies.store'
import type { Technology } from '@/types/domain.types'
import type { SessionListFilters } from '@/types/api.types'

const props = withDefaults(
  defineProps<{
    modelValue?: SessionListFilters
    hideTechnology?: boolean
  }>(),
  { modelValue: () => ({}), hideTechnology: false }
)

const emit = defineEmits<{
  'update:modelValue': [value: SessionListFilters]
  change: []
}>()

const technologiesStore = useTechnologiesStore()
const technology = ref<Technology | null>(null)
const dateFrom = ref(props.modelValue?.date_from ?? '')
const dateTo = ref(props.modelValue?.date_to ?? '')
const minDuration = ref(props.modelValue?.min_duration?.toString() ?? '')
const mood = ref(props.modelValue?.mood?.toString() ?? '')

function filtersEqual(a: SessionListFilters, b: SessionListFilters): boolean {
  return (
    (a.technology_id ?? '') === (b.technology_id ?? '') &&
    (a.date_from ?? '') === (b.date_from ?? '') &&
    (a.date_to ?? '') === (b.date_to ?? '') &&
    (a.min_duration ?? undefined) === (b.min_duration ?? undefined) &&
    (a.mood ?? undefined) === (b.mood ?? undefined)
  )
}

function getTechnologyById(id: string): Technology | null {
  return technologiesStore.technologies.find((t) => t.id === id) ?? null
}

function syncFromModelValue(val: SessionListFilters | undefined) {
  const v = val ?? {}
  const techId = v.technology_id
  technology.value = techId ? getTechnologyById(techId) : null
  dateFrom.value = v.date_from ?? ''
  dateTo.value = v.date_to ?? ''
  minDuration.value = v.min_duration != null ? String(v.min_duration) : ''
  mood.value = v.mood != null ? String(v.mood) : ''
}

watch(
  () => props.modelValue,
  (val) => syncFromModelValue(val),
  { immediate: true, deep: true }
)

watch(
  () => technologiesStore.technologies,
  () => {
    const techId = props.modelValue?.technology_id
    if (techId && !technology.value) {
      technology.value = getTechnologyById(techId)
    }
  },
  { deep: true }
)

function buildFilters(): SessionListFilters {
  return {
    technology_id: technology.value?.id,
    date_from: dateFrom.value || undefined,
    date_to: dateTo.value || undefined,
    min_duration: minDuration.value ? parseInt(minDuration.value, 10) : undefined,
    mood: mood.value ? parseInt(mood.value, 10) : undefined,
  }
}

function emitIfChanged(filters: SessionListFilters) {
  if (!filtersEqual(filters, props.modelValue ?? {})) {
    emit('update:modelValue', filters)
    emit('change')
  }
}

// Selects (technology, mood) → emit imediato
watch(
  () => [technology.value, mood.value],
  () => emitIfChanged(buildFilters()),
  { deep: true }
)

// Inputs de texto (date, duration) → debounce 300ms para não disparar query a cada tecla
const emitDebounced = useDebounceFn(() => emitIfChanged(buildFilters()), 300)

watch(
  () => [dateFrom.value, dateTo.value, minDuration.value],
  () => emitDebounced()
)

function clear() {
  technology.value = null
  dateFrom.value = ''
  dateTo.value = ''
  minDuration.value = ''
  mood.value = ''
}
</script>

<template>
  <div class="session-filters">
    <div class="session-filters__row">
      <div v-if="!hideTechnology" class="filter-group">
        <label for="filter-tech">Tecnologia</label>
        <TechnologyPicker id="filter-tech" v-model="technology" placeholder="Todas" />
      </div>
      <div class="filter-group">
        <label for="filter-date-from">De</label>
        <input id="filter-date-from" v-model="dateFrom" type="date" class="filter-input" />
      </div>
      <div class="filter-group">
        <label for="filter-date-to">Até</label>
        <input id="filter-date-to" v-model="dateTo" type="date" class="filter-input" />
      </div>
      <div class="filter-group">
        <label for="filter-min-duration">Mín. duração (min)</label>
        <input
          id="filter-min-duration"
          v-model="minDuration"
          type="number"
          min="0"
          class="filter-input"
          placeholder="0"
        />
      </div>
      <div class="filter-group">
        <label for="filter-mood">Mood (1-5)</label>
        <select id="filter-mood" v-model="mood" class="filter-input">
          <option value="">Todos</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
        </select>
      </div>
      <button type="button" class="btn-clear" aria-label="Limpar filtros" @click="clear">
        Limpar
      </button>
    </div>
  </div>
</template>

<style scoped>
.session-filters {
  background: var(--color-bg-card);
  padding: var(--widget-padding);
  border-radius: var(--radius-md);
  margin-bottom: var(--page-section-gap);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
}
.session-filters__row {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-sm);
  align-items: flex-end;
}
.filter-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  flex: 1;
  min-width: clamp(8.75rem, 24vw, 12rem);
}
.filter-group label {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
}
.filter-input {
  min-height: var(--input-height-sm);
  padding: var(--spacing-xs) var(--spacing-sm);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  width: 100%;
  box-sizing: border-box;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.filter-input:focus-visible {
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focus);
  outline: none;
}
.btn-clear {
  min-height: var(--input-height-sm);
  padding: var(--spacing-xs) var(--spacing-md);
  font-size: var(--text-sm);
  font-weight: 500;
  background: var(--color-bg-card);
  color: var(--color-text-muted);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  white-space: nowrap;
  align-self: flex-end;
  transition:
    background var(--duration-fast) ease,
    color var(--duration-fast) ease,
    border-color var(--duration-fast) ease;
}
.btn-clear:hover {
  background: var(--color-primary-soft);
  color: var(--color-primary);
  border-color: var(--color-primary);
}
.btn-clear:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
@media (max-width: 640px) {
  .session-filters {
    padding: var(--widget-padding-sm);
  }
  .session-filters__row {
    align-items: stretch;
  }
  .filter-group {
    min-width: 100%;
  }
  .btn-clear {
    width: 100%;
    align-self: stretch;
  }
}
</style>
