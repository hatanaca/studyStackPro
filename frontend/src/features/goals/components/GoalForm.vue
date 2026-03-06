<script setup lang="ts">
import { ref, computed } from 'vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import FormSection from '@/components/ui/FormSection.vue'
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
    <FormSection
      title="Tipo e valor"
      description="Escolha o tipo de meta e o valor alvo."
      grouped
    >
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
      <BaseInput
        v-model="targetValue"
        type="number"
        label="Valor alvo"
        placeholder="Ex: 300"
        :error="errors.target_value"
        min="1"
        step="1"
      />
    </FormSection>
    <FormSection
      title="Período"
      description="Data de início da meta."
      grouped
    >
      <BaseInput
        v-model="startDate"
        type="date"
        label="Data inicial"
        :error="errors.start_date"
      />
    </FormSection>
    <div class="goal-form__actions">
      <BaseButton
        type="button"
        variant="ghost"
        @click="emit('cancel')"
      >
        Cancelar
      </BaseButton>
      <BaseButton
        type="submit"
        :disabled="loading"
      >
        {{ loading ? 'Salvando...' : (initialGoal ? 'Salvar alterações' : 'Criar meta') }}
      </BaseButton>
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
.goal-form__actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-md);
}
</style>
