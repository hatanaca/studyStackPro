<script setup lang="ts">
import { ref, computed } from 'vue'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import Fieldset from 'primevue/fieldset'
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
    <Fieldset legend="Tipo e valor">
      <p class="goal-form__desc">Escolha o tipo de meta e o valor alvo.</p>
      <div class="goal-form__row">
        <label class="goal-form__label">Tipo</label>
        <select
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
      <div class="p-field">
        <label for="goal-target">Valor alvo</label>
        <InputNumber
          id="goal-target"
          v-model="targetValueNum"
          placeholder="Ex: 300"
          :min="1"
          :max-fraction-digits="0"
          class="w-full"
          :class="{ 'p-invalid': errors.target_value }"
        />
        <small v-if="errors.target_value" class="p-error">{{ errors.target_value }}</small>
      </div>
    </Fieldset>
    <Fieldset legend="Período">
      <p class="goal-form__desc">Data de início da meta.</p>
      <div class="p-field">
        <label for="goal-start">Data inicial</label>
        <input
          id="goal-start"
          v-model="startDate"
          type="date"
          class="p-inputtext p-component w-full"
          :class="{ 'p-invalid': errors.start_date }"
        >
        <small v-if="errors.start_date" class="p-error">{{ errors.start_date }}</small>
      </div>
    </Fieldset>
    <div class="goal-form__actions">
      <Button
        type="button"
        label="Cancelar"
        severity="secondary"
        variant="text"
        @click="emit('cancel')"
      />
      <Button
        type="submit"
        :label="loading ? 'Salvando...' : (initialGoal ? 'Salvar alterações' : 'Criar meta')"
        :loading="loading"
      />
    </div>
  </form>
</template>

<style scoped>
.goal-form__row {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.goal-form__label {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
}
.goal-form__select {
  min-height: var(--input-height-sm);
  padding: 0.45rem 0.75rem;
  font-size: var(--text-sm);
  font-family: inherit;
  color: var(--color-text);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.goal-form__select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
}
.goal-form__desc { font-size: var(--text-sm); color: var(--color-text-muted); margin: 0 0 var(--spacing-sm); }
.p-field { margin-bottom: var(--spacing-md); display: flex; flex-direction: column; gap: 0.25rem; }
.p-field label { font-size: 0.75rem; font-weight: 600; color: var(--color-text-muted); }
.w-full { width: 100%; }
.goal-form__actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-md);
}
</style>
