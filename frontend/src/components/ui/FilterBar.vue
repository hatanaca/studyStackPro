<script setup lang="ts">
import { ref, watch } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'

export interface FilterBarState {
  search?: string
  dateRange?: { start: string; end: string } | null
}

const props = withDefaults(
  defineProps<{
    modelValue?: FilterBarState
    showSearch?: boolean
    showDateRange?: boolean
    searchPlaceholder?: string
  }>(),
  {
    modelValue: () => ({}),
    showSearch: true,
    showDateRange: false,
    searchPlaceholder: 'Buscar...',
  }
)

const emit = defineEmits<{
  'update:modelValue': [value: FilterBarState]
  search: [value: string]
  filter: [value: FilterBarState]
}>()

const search = ref(props.modelValue?.search ?? '')
const dateRange = ref<{ start: string; end: string }>({
  start: props.modelValue?.dateRange?.start ?? '',
  end: props.modelValue?.dateRange?.end ?? '',
})

function emitState() {
  const dr = dateRange.value.start && dateRange.value.end ? { ...dateRange.value } : null
  const state: FilterBarState = {
    search: search.value || undefined,
    dateRange: dr ?? undefined,
  }
  emit('update:modelValue', state)
  emit('filter', state)
}

watch(search, () => {
  emit('search', search.value)
  emitState()
})
watch(dateRange, emitState, { deep: true })
watch(() => props.modelValue?.dateRange, (dr) => {
  if (dr) {
    dateRange.value = { start: dr.start ?? '', end: dr.end ?? '' }
  } else {
    dateRange.value = { start: '', end: '' }
  }
}, { immediate: true })

function clearFilters() {
  search.value = ''
  dateRange.value = { start: '', end: '' }
  emitState()
}
</script>

<template>
  <div class="filter-bar">
    <div
      v-if="showSearch"
      class="filter-bar__search"
    >
      <InputText
        v-model="search"
        type="search"
        :placeholder="searchPlaceholder"
        class="filter-bar__input"
        @keyup.enter="emitState"
      />
    </div>
    <div
      v-if="showDateRange"
      class="filter-bar__dates"
    >
      <input
        v-model="dateRange.start"
        type="date"
        class="p-inputtext p-component"
        aria-label="Início"
      >
      <input
        v-model="dateRange.end"
        type="date"
        class="p-inputtext p-component"
        aria-label="Fim"
      >
    </div>
    <Button
      label="Limpar filtros"
      variant="text"
      size="small"
      severity="secondary"
      class="filter-bar__clear"
      @click="clearFilters"
    />
  </div>
</template>

<style scoped>
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--spacing-md);
}
.filter-bar__search {
  flex: 1;
  min-width: 12rem;
}
.filter-bar__input {
  width: 100%;
}
.filter-bar__dates {
  flex-shrink: 0;
}
.filter-bar__clear {
  flex-shrink: 0;
}
</style>
