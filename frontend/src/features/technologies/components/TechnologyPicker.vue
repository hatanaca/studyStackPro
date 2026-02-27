<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useTechnologiesStore } from '@/stores/technologies.store'
import type { Technology } from '@/types/domain.types'

const props = withDefaults(
  defineProps<{
    modelValue?: Technology | null
    placeholder?: string
    minSearchLength?: number
  }>(),
  { placeholder: 'Buscar tecnologia...', minSearchLength: 2 }
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

watch(query, async (q) => {
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
})

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
</script>

<template>
  <div class="technology-picker">
    <div class="technology-picker__input-wrap">
      <input
        v-model="query"
        type="text"
        :placeholder="placeholder"
        class="technology-picker__input"
        autocomplete="off"
        @focus="onFocus"
        @blur="onBlur"
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
      class="technology-picker__dropdown"
    >
      <button
        v-for="(tech, i) in results"
        :key="tech.id"
        type="button"
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
  padding: 0.5rem 2rem 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  box-sizing: border-box;
}
.technology-picker__clear {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.25rem;
  line-height: 1;
}
.technology-picker__clear:hover {
  color: #64748b;
}
.technology-picker__dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 0.25rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  max-height: 200px;
  overflow-y: auto;
  z-index: 10;
}
.technology-picker__option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: none;
  background: none;
  text-align: left;
  font-size: 0.875rem;
  cursor: pointer;
}
.technology-picker__option:hover,
.technology-picker__option--highlight {
  background: #f1f5f9;
}
.technology-picker__color-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}
.technology-picker__loading {
  padding: 0.5rem 0.75rem;
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0;
}
</style>
