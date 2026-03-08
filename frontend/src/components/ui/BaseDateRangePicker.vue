<script setup lang="ts">
import { computed, ref } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue?: { start: string; end: string } | null
    minDate?: string
    maxDate?: string
    placeholderStart?: string
    placeholderEnd?: string
    disabled?: boolean
  }>(),
  {
    modelValue: null,
    minDate: undefined,
    maxDate: undefined,
    placeholderStart: 'Data inicial',
    placeholderEnd: 'Data final',
    disabled: false,
  }
)

const emit = defineEmits<{
  'update:modelValue': [value: { start: string; end: string } | null]
}>()

const start = ref(props.modelValue?.start ?? '')
const end = ref(props.modelValue?.end ?? '')

const startMin = computed(() => props.minDate ?? '')
const startMax = computed(() => props.maxDate ?? '')
const endMin = computed(() => start.value || props.minDate || '')
const endMax = computed(() => props.maxDate ?? '')

function emitValue() {
  if (start.value && end.value) {
    const s = start.value
    const e = end.value
    if (s <= e) {
      emit('update:modelValue', { start: s, end: e })
    } else {
      emit('update:modelValue', null)
    }
  } else {
    emit('update:modelValue', null)
  }
}

function onStartInput() {
  if (end.value && start.value > end.value) end.value = start.value
  emitValue()
}

function onEndInput() {
  if (start.value && end.value && end.value < start.value) start.value = end.value
  emitValue()
}

function clear() {
  start.value = ''
  end.value = ''
  emit('update:modelValue', null)
}
</script>

<template>
  <div class="base-date-range">
    <div class="base-date-range__inputs">
      <input
        v-model="start"
        type="date"
        class="base-date-range__input"
        :min="startMin"
        :max="startMax"
        :placeholder="placeholderStart"
        :disabled="disabled"
        :aria-label="placeholderStart"
        @input="onStartInput"
      >
      <span
        class="base-date-range__sep"
        aria-hidden="true"
      >até</span>
      <input
        v-model="end"
        type="date"
        class="base-date-range__input"
        :min="endMin"
        :max="endMax"
        :placeholder="placeholderEnd"
        :disabled="disabled"
        :aria-label="placeholderEnd"
        @input="onEndInput"
      >
    </div>
    <button
      v-if="start || end"
      type="button"
      class="base-date-range__clear"
      aria-label="Limpar datas"
      :disabled="disabled"
      @click="clear"
    >
      Limpar
    </button>
  </div>
</template>

<style scoped>
.base-date-range {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.base-date-range__inputs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.base-date-range__input {
  min-height: var(--input-height-sm);
  padding: 0.45rem 0.75rem;
  font-size: var(--text-sm);
  font-family: inherit;
  color: var(--color-text);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  min-width: 10rem;
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.base-date-range__input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
}
.base-date-range__input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.base-date-range__sep {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}
.base-date-range__clear {
  min-height: var(--input-height-sm);
  padding: 0.35rem 0.75rem;
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  background: transparent;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: color var(--duration-fast) ease, border-color var(--duration-fast) ease, background var(--duration-fast) ease;
}
.base-date-range__clear:hover:not(:disabled) {
  color: var(--color-primary);
  border-color: var(--color-primary);
  background: var(--color-primary-soft);
}
</style>
