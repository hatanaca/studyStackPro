<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useTechnologiesStore } from '@/stores/technologies.store'
import type { Technology } from '@/types/domain.types'

const props = withDefaults(
  defineProps<{
    modelValue?: Technology | null
    placeholder?: string
    minSearchLength?: number
    id?: string
    ariaLabel?: string
  }>(),
  { modelValue: null, placeholder: 'Buscar tecnologia...', minSearchLength: 2 }
)

const emit = defineEmits<{
  'update:modelValue': [Technology | null]
}>()

const store = useTechnologiesStore()
const query = ref('')
const results = ref<Technology[]>([])
const loading = ref(false)
const open = ref(false)
const highlightIndex = ref(0)

const debouncedSearch = useDebounceFn(async (q: string) => {
  if (q.length < props.minSearchLength) {
    results.value = []
    return
  }
  const local = store.searchLocal(q)
  if (local.length > 0) {
    results.value = local
    open.value = true
    highlightIndex.value = 0
  } else {
    loading.value = true
    try {
      results.value = await store.searchFromApi(q)
      open.value = results.value.length > 0
      highlightIndex.value = 0
    } finally {
      loading.value = false
    }
  }
}, 300)

watch(query, (q) => debouncedSearch(q))

watch(
  () => props.modelValue,
  (v) => {
    query.value = v?.name ?? ''
  },
  { immediate: true }
)

onMounted(() => {
  store.fetchTechnologies()
})

function select(tech: Technology) {
  emit('update:modelValue', tech)
  query.value = tech.name
  open.value = false
}

function clear() {
  emit('update:modelValue', null)
  query.value = ''
  open.value = false
}

function onFocus() {
  if (query.value.length >= props.minSearchLength && results.value.length) {
    open.value = true
  }
}

function onBlur() {
  setTimeout(() => { open.value = false }, 150)
}

function onKeydown(e: KeyboardEvent) {
  if (!open.value || !results.value.length) {
    if (e.key === 'Escape') open.value = false
    return
  }
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    highlightIndex.value = (highlightIndex.value + 1) % results.value.length
    return
  }
  if (e.key === 'ArrowUp') {
    e.preventDefault()
    highlightIndex.value = highlightIndex.value <= 0
      ? results.value.length - 1
      : highlightIndex.value - 1
    return
  }
  if (e.key === 'Enter') {
    e.preventDefault()
    select(results.value[highlightIndex.value])
    return
  }
  if (e.key === 'Escape') {
    e.preventDefault()
    open.value = false
  }
}
</script>

<template>
  <div class="technology-picker">
    <div class="technology-picker__input-wrap">
      <input
        :id="props.id"
        v-model="query"
        type="text"
        :placeholder="placeholder"
        class="technology-picker__input"
        autocomplete="off"
        role="combobox"
        :aria-label="props.ariaLabel"
        :aria-expanded="open && results.length > 0"
        :aria-activedescendant="open && results.length ? `tech-picker-option-${highlightIndex}` : undefined"
        aria-controls="tech-picker-listbox"
        aria-autocomplete="list"
        @focus="onFocus"
        @blur="onBlur"
        @keydown="onKeydown"
      >
      <button
        v-if="modelValue"
        type="button"
        class="technology-picker__clear"
        aria-label="Limpar"
        @click="clear"
      >
        ×
      </button>
    </div>
    <div
      v-if="open && results.length"
      id="tech-picker-listbox"
      role="listbox"
      class="technology-picker__dropdown"
    >
      <button
        v-for="(tech, i) in results"
        :id="`tech-picker-option-${i}`"
        :key="tech.id"
        type="button"
        role="option"
        :aria-selected="i === highlightIndex"
        class="technology-picker__option"
        :class="{ 'technology-picker__option--highlight': i === highlightIndex }"
        @mousedown.prevent="select(tech)"
      >
        <span
          class="technology-picker__color-dot"
          :style="{ background: tech.color }"
        />
        {{ tech.name }}
      </button>
      <p
        v-if="loading"
        class="technology-picker__loading"
      >
        Buscando...
      </p>
    </div>
  </div>
</template>

<style scoped>
.technology-picker {
  position: relative;
}
.technology-picker__input-wrap {
  position: relative;
}
.technology-picker__input {
  width: 100%;
  min-height: var(--input-height-sm);
  padding: var(--spacing-sm) var(--spacing-2xl) var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  box-sizing: border-box;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.technology-picker__input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
}
.technology-picker__clear {
  position: absolute;
  right: var(--spacing-sm);
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  font-size: var(--text-lg);
  color: var(--color-text-muted);
  cursor: pointer;
  padding: var(--spacing-xs);
  line-height: 1;
  transition: color var(--duration-fast) ease;
}
.technology-picker__clear:hover {
  color: var(--color-primary);
}
.technology-picker__dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: var(--spacing-xs);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  max-height: 200px;
  overflow-y: auto;
  z-index: 10;
}
.technology-picker__option {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  width: 100%;
  padding: var(--spacing-sm) var(--spacing-lg);
  border: none;
  background: none;
  text-align: left;
  font-size: var(--text-sm);
  cursor: pointer;
  color: var(--color-text);
  transition: background var(--duration-fast) ease;
}
.technology-picker__option:hover,
.technology-picker__option--highlight {
  background: var(--color-bg-soft);
}
.technology-picker__color-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  flex-shrink: 0;
}
.technology-picker__loading {
  padding: var(--spacing-sm) var(--spacing-lg);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: 0;
}
</style>
