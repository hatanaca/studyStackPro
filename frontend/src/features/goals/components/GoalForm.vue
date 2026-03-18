<script setup lang="ts">
import { ref, computed } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import type { CreateGoalPayload, GoalType } from '@/types/goals.types'
import { GOAL_TYPE_LABELS } from '@/types/goals.types'

const props = withDefaults(
  defineProps<{
    loading?: boolean
    defaultType?: GoalType
    /** Em modo edição, preenche o formulário e emite update em vez de submit (criar) */
    initialGoal?: { id: string; type: GoalType; target_value: number; start_date: string } | null
  }>(),
  { loading: false, defaultType: 'minutes_per_week', initialGoal: null }
)

const emit = defineEmits<{
  submit: [payload: CreateGoalPayload]
  update: [payload: { id: string; target_value: number }]
  cancel: []
}>()

const type = ref<GoalType>(props.initialGoal?.type ?? props.defaultType)
const targetValue = ref(String(props.initialGoal?.target_value ?? ''))
const targetValueNum = computed({
  get: () => (targetValue.value === '' ? null : Number(targetValue.value)),
  set: (v) => { targetValue.value = v == null ? '' : String(v) },
})
const startDate = ref(props.initialGoal?.start_date ?? new Date().toISOString().slice(0, 10))
const errors = ref<Record<string, string>>({})

const typeOptions = computed(() =>
  (Object.keys(GOAL_TYPE_LABELS) as GoalType[]).map(key => ({
    value: key,
    label: GOAL_TYPE_LABELS[key],
  }))
)

function validate(): boolean {
  const e: Record<string, string> = {}
  const num = Number(targetValue.value)
  if (!Number.isInteger(num) || num < 1) {
    e.target_value = 'Informe um número inteiro maior que zero.'
  }
  if (!startDate.value) e.start_date = 'Data inicial é obrigatória.'
  errors.value = e
  return Object.keys(e).length === 0
}

function submit() {
  if (!validate()) return
  if (props.initialGoal) {
    emit('update', { id: props.initialGoal.id, target_value: Number(targetValue.value) })
  } else {
    emit('submit', {
      type: type.value,
      target_value: Number(targetValue.value),
      start_date: startDate.value,
      end_date: null,
    })
  }
}
</script>

<template>
  <form
    class="goal-form"
    @submit.prevent="submit"
  >
    <fieldset class="goal-form__fieldset">
      <legend class="goal-form__legend">Tipo e valor</legend>
      <p class="goal-form__desc">Escolha o tipo de meta e o valor alvo.</p>
      <div class="goal-form__field">
        <label class="goal-form__label" for="goal-type">Tipo</label>
        <select
          id="goal-type"
          v-model="type"
          class="goal-form__select"
          aria-label="Tipo de meta"
        >
          <option
            v-for="opt in typeOptions"
            :key="opt.value"
            :value="opt.value"
          >
            {{ opt.label }}
          </option>
        </select>
      </div>
      <div class="goal-form__field">
        <label class="goal-form__label" for="goal-target">Valor alvo</label>
        <input
          id="goal-target"
          v-model="targetValue"
          type="number"
          min="1"
          step="1"
          placeholder="Ex: 300"
          class="goal-form__input"
          :class="{ 'goal-form__input--error': errors.target_value }"
        >
        <span v-if="errors.target_value" class="goal-form__error">{{ errors.target_value }}</span>
      </div>
    </fieldset>

    <fieldset class="goal-form__fieldset">
      <legend class="goal-form__legend">Período</legend>
      <p class="goal-form__desc">Data de início da meta.</p>
      <div class="goal-form__field">
        <label class="goal-form__label" for="goal-start">Data inicial</label>
        <input
          id="goal-start"
          v-model="startDate"
          type="date"
          class="goal-form__input"
          :class="{ 'goal-form__input--error': errors.start_date }"
        >
        <span v-if="errors.start_date" class="goal-form__error">{{ errors.start_date }}</span>
      </div>
    </fieldset>

    <div class="goal-form__actions">
      <BaseButton
        type="button"
        variant="secondary"
        @click="emit('cancel')"
      >
        Cancelar
      </BaseButton>
      <BaseButton
        type="submit"
        variant="primary"
        :disabled="loading"
      >
        {{ loading ? 'Salvando...' : (initialGoal ? 'Salvar alterações' : 'Criar meta') }}
      </BaseButton>
    </div>
  </form>
</template>

<style scoped>
.goal-form {
  display: flex;
  flex-direction: column;
  gap: var(--form-section-gap);
}
.goal-form__fieldset {
  border: 1px solid var(--form-input-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-md) var(--spacing-lg);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
.goal-form__legend {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text);
  padding: 0 var(--spacing-xs);
}
.goal-form__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
  line-height: var(--leading-normal);
}
.goal-form__field {
  display: flex;
  flex-direction: column;
  gap: var(--form-field-gap);
}
.goal-form__label {
  font-size: var(--form-label-size);
  font-weight: var(--form-label-weight);
  letter-spacing: var(--form-label-tracking);
  color: var(--form-label-color);
}
.goal-form__select,
.goal-form__input {
  width: 100%;
  min-height: var(--form-input-height);
  padding: var(--form-input-padding);
  font-size: var(--form-input-font-size);
  font-family: inherit;
  color: var(--form-input-text);
  background: var(--form-input-bg);
  border: 1px solid var(--form-input-border);
  border-radius: var(--form-input-radius);
  outline: none;
  box-sizing: border-box;
  transition: border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease,
    background var(--duration-fast) ease;
}
.goal-form__select {
  cursor: pointer;
}
.goal-form__select::placeholder,
.goal-form__input::placeholder {
  color: var(--form-input-placeholder);
}
.goal-form__select:focus,
.goal-form__input:focus {
  border-color: var(--form-input-border-focus);
  box-shadow: var(--form-input-shadow-focus);
}
.goal-form__input--error {
  border-color: var(--form-input-border-error);
  box-shadow: var(--form-input-shadow-error);
}
.goal-form__error {
  font-size: var(--form-label-size);
  color: var(--form-input-border-error);
  line-height: var(--leading-snug);
}
.goal-form__actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-md);
  margin-top: var(--spacing-sm);
}
</style>
