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
  maxlength?: number
}>()

defineEmits<{
  'update:modelValue': [value: string]
}>()

const inputId = computed(() => props.id ?? props.name ?? undefined)
</script>

<template>
  <div class="base-input">
    <label v-if="label" class="base-input__label" :for="inputId">{{ label }}</label>
    <input
      :id="inputId"
      :type="type ?? 'text'"
      :value="modelValue"
      :placeholder="placeholder"
      :name="name"
      :disabled="disabled"
      :maxlength="maxlength"
      :autocomplete="autocomplete"
      :class="{ 'base-input__field--error': error }"
      class="base-input__field"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <p v-if="error" class="base-input__error">
      {{ error }}
    </p>
  </div>
</template>

<style scoped>
.base-input {
  display: flex;
  flex-direction: column;
  gap: var(--form-field-gap);
}
.base-input__label {
  display: block;
  font-size: var(--form-label-size);
  font-weight: var(--form-label-weight);
  letter-spacing: var(--form-label-tracking);
  color: var(--form-label-color);
}
.base-input__field {
  width: 100%;
  min-height: var(--form-input-height);
  padding: var(--form-input-padding);
  border: 1px solid var(--form-input-border);
  border-radius: var(--form-input-radius);
  font-size: var(--form-input-font-size);
  color: var(--form-input-text);
  background: var(--form-input-bg);
  outline: none;
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease,
    background var(--duration-fast) ease;
  box-sizing: border-box;
}
.base-input__field::placeholder {
  color: var(--form-input-placeholder);
}
.base-input__field:focus-visible {
  border-color: var(--form-input-border-focus);
  box-shadow: var(--form-input-shadow-focus);
}
.base-input__field:disabled {
  background: var(--form-input-bg-disabled);
  color: var(--form-label-color);
  cursor: not-allowed;
  opacity: var(--state-disabled-opacity);
}
.base-input__field--error {
  border-color: var(--form-input-border-error);
  box-shadow: var(--form-input-shadow-error);
}
.base-input__error {
  font-size: var(--form-label-size);
  color: var(--form-input-border-error);
  margin-top: 0;
  line-height: var(--leading-snug);
}
</style>
