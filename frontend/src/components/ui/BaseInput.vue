<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  modelValue?: string | number
  type?: string
  placeholder?: string
  label?: string
  id?: string
  name?: string
  disabled?: boolean
  error?: string
  autocomplete?: string
}>()

defineEmits<{
  'update:modelValue': [value: string]
}>()

const inputId = computed(() => props.id ?? props.name ?? undefined)
</script>

<template>
  <div class="base-input">
    <label
      v-if="label"
      class="base-input__label"
      :for="inputId"
    >{{ label }}</label>
    <input
      :id="inputId"
      :type="type ?? 'text'"
      :value="modelValue"
      :placeholder="placeholder"
      :name="name"
      :disabled="disabled"
      :autocomplete="autocomplete"
      :class="{ 'base-input__field--error': error }"
      class="base-input__field"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    >
    <p
      v-if="error"
      class="base-input__error"
    >
      {{ error }}
    </p>
  </div>
</template>

<style scoped>
.base-input {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.base-input__label {
  display: block;
  font-size: var(--text-xs);
  font-weight: 600;
  letter-spacing: 0.02em;
  color: var(--color-text-muted);
}
.base-input__field {
  width: 100%;
  min-height: var(--input-height-sm);
  padding: 0.45rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  color: var(--color-text);
  background: var(--color-bg-card);
  transition: border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease,
    background var(--duration-fast) ease;
  box-sizing: border-box;
}
.base-input__field::placeholder {
  color: color-mix(in srgb, var(--color-text-muted) 78%, transparent);
}
.base-input__field:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
}
.base-input__field:disabled {
  background: color-mix(in srgb, var(--color-bg-soft) 85%, transparent);
  color: var(--color-text-muted);
  cursor: not-allowed;
}
.base-input__field--error {
  border-color: var(--color-error);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-error) 22%, transparent);
}
.base-input__error {
  font-size: var(--text-xs);
  color: var(--color-error);
  margin-top: 0;
  line-height: 1.35;
}
</style>
