<script setup lang="ts">
import { ref, watch } from 'vue'
import TechnologyPicker from '@/features/technologies/components/TechnologyPicker.vue'
import type { Technology } from '@/types/domain.types'

const props = defineProps<{
  modelValue: {
    technology_id?: string
    date_from?: string
    date_to?: string
    min_duration?: number
    mood?: number
  }
}>()

const emit = defineEmits<{
  'update:modelValue': [typeof props.modelValue]
  change: []
}>()

const technology = ref<Technology | null>(null)
const dateFrom = ref(props.modelValue.date_from ?? '')
const dateTo = ref(props.modelValue.date_to ?? '')
const minDuration = ref(props.modelValue.min_duration?.toString() ?? '')
const mood = ref(props.modelValue.mood?.toString() ?? '')

watch(
  () => [technology.value, dateFrom.value, dateTo.value, minDuration.value, mood.value],
  () => {
    const filters = {
      technology_id: technology.value?.id,
      date_from: dateFrom.value || undefined,
      date_to: dateTo.value || undefined,
      min_duration: minDuration.value ? parseInt(minDuration.value, 10) : undefined,
      mood: mood.value ? parseInt(mood.value, 10) : undefined
    }
    emit('update:modelValue', filters)
    emit('change')
  },
  { deep: true }
)

function clear() {
  technology.value = null
  dateFrom.value = ''
  dateTo.value = ''
  minDuration.value = ''
  mood.value = ''
  emit('change')
}
</script>

<template>
  <div class="session-filters">
    <div class="session-filters__row">
      <div class="filter-group">
        <label>Tecnologia</label>
        <TechnologyPicker
          v-model="technology"
          placeholder="Todas"
        />
      </div>
      <div class="filter-group">
        <label>De</label>
        <input
          v-model="dateFrom"
          type="date"
          class="filter-input"
        >
      </div>
      <div class="filter-group">
        <label>Até</label>
        <input
          v-model="dateTo"
          type="date"
          class="filter-input"
        >
      </div>
      <div class="filter-group">
        <label>Mín. duração (min)</label>
        <input
          v-model="minDuration"
          type="number"
          min="0"
          class="filter-input"
          placeholder="0"
        >
      </div>
      <div class="filter-group">
        <label>Mood (1-5)</label>
        <select
          v-model="mood"
          class="filter-input"
        >
          <option value="">
            Todos
          </option>
          <option value="1">
            1
          </option>
          <option value="2">
            2
          </option>
          <option value="3">
            3
          </option>
          <option value="4">
            4
          </option>
          <option value="5">
            5
          </option>
        </select>
      </div>
      <button
        type="button"
        class="btn-clear"
        @click="clear"
      >
        Limpar
      </button>
    </div>
  </div>
</template>

<style scoped>
.session-filters {
  background: #fff;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}
.session-filters__row {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: flex-end;
}
.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.filter-group label {
  font-size: 0.75rem;
  color: #64748b;
}
.filter-input {
  padding: 0.375rem 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  min-width: 120px;
}
.btn-clear {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
  background: #f1f5f9;
  color: #64748b;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}
.btn-clear:hover {
  background: #e2e8f0;
}
</style>
