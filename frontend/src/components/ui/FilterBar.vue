<script setup lang="ts">
import { ref, watch } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseDateRangePicker from '@/components/ui/BaseDateRangePicker.vue'

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
const dateRange = ref<{ start: string; end: string } | null>(props.modelValue?.dateRange ?? null)

function emitState() {
  const state: FilterBarState = {
    search: search.value || undefined,
    dateRange: dateRange.value ?? undefined,
  }
  emit('update:modelValue', state)
  emit('filter', state)
}

watch(search, () => {
  emit('search', search.value)
  emitState()
})
watch(dateRange, emitState, { deep: true })

function clearFilters() {
  search.value = ''
  dateRange.value = null
  emitState()
}
</script>

<template>
  <div class="filter-bar">
    <div
      v-if="showSearch"
      class="filter-bar__search"
    >
      <BaseInput
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
      <BaseDateRangePicker
        v-model="dateRange"
        placeholder-start="Início"
        placeholder-end="Fim"
      />
    </div>
    <BaseButton
      variant="ghost"
      size="sm"
      class="filter-bar__clear"
      @click="clearFilters"
    >
      Limpar filtros
    </BaseButton>
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
