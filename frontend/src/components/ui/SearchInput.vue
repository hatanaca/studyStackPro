<script setup lang="ts">
import { ref, watch } from 'vue'
import InputText from 'primevue/inputtext'
import { useDebounceFn } from '@/composables/useDebounce'

const props = withDefaults(
  defineProps<{
    modelValue?: string
    placeholder?: string
    debounceMs?: number
    minLength?: number
  }>(),
  { modelValue: '', placeholder: 'Buscar...', debounceMs: 300, minLength: 0 }
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
  search: [value: string]
}>()

const local = ref(props.modelValue)
const debouncedSearch = useDebounceFn((...args: unknown[]) => {
  emit('search', String(args[0] ?? ''))
}, props.debounceMs)

watch(
  () => props.modelValue,
  (v) => { local.value = v }
)

watch(local, (v) => {
  emit('update:modelValue', v)
  if (v.length >= props.minLength) debouncedSearch(v)
})
</script>

<template>
  <InputText
    v-model="local"
    type="search"
    :placeholder="placeholder"
    class="search-input"
    autocomplete="off"
  />
</template>

<style scoped>
.search-input {
  width: 100%;
}
</style>
