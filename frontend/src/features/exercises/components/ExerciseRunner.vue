<script setup lang="ts">
/**
 * Runner de exercícios de matemática: gera uma variante (randomizada),
 * mostra o enunciado com LaTeX (KaTeX), corrige a resposta no backend
 * (SymPy via math-service) e exibe feedback + solução.
 */
import { computed, ref, watch } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import FormulaText from '@/components/math/FormulaText.vue'
import { useToast } from '@/composables/useToast'
import {
  useGenerateVariantMutation,
  useGradeMutation,
} from '@/features/exercises/composables/useExercisesQuery'
import type {
  ExerciseAttempt,
  ExerciseTemplate,
  ExerciseVariant,
} from '@/features/exercises/types/exercises.types'
import { splitLatex } from '@/features/exercises/utils/latex'

const props = defineProps<{ template: ExerciseTemplate }>()

const toast = useToast()
const variant = ref<ExerciseVariant | null>(null)
const answer = ref('')
const attempt = ref<ExerciseAttempt | null>(null)

const generateMutation = useGenerateVariantMutation()
const gradeMutation = useGradeMutation()

const promptSegments = computed(() =>
  variant.value ? splitLatex(variant.value.prompt_latex) : []
)

watch(
  () => props.template.id,
  () => {
    variant.value = null
    attempt.value = null
    answer.value = ''
  }
)

async function generate() {
  variant.value = null
  attempt.value = null
  answer.value = ''
  try {
    variant.value = await generateMutation.mutateAsync({ templateId: props.template.id })
  } catch {
    toast.error('Falha ao gerar o exercício. Tente novamente.')
  }
}

async function submit() {
  if (!variant.value || !answer.value.trim()) return
  try {
    attempt.value = await gradeMutation.mutateAsync({
      variantId: variant.value.id,
      answer: answer.value.trim(),
    })
  } catch {
    toast.error('Falha ao corrigir a resposta. Tente novamente.')
  }
}
</script>

<template>
  <div class="exercise-runner">
    <div v-if="!variant" class="exercise-runner__empty">
      <p class="exercise-runner__hint">
        Pronto para praticar <strong>{{ template.title }}</strong>?
      </p>
      <Button
        label="Gerar exercício"
        icon="pi pi-refresh"
        :loading="generateMutation.isPending.value"
        @click="generate"
      />
    </div>

    <template v-else>
      <div class="exercise-runner__prompt">
        <template v-for="(seg, i) in promptSegments" :key="i">
          <FormulaText v-if="seg.type === 'latex'" :latex="seg.content" display />
          <span v-else>{{ seg.content }}</span>
        </template>
      </div>

      <form v-if="!attempt" class="exercise-runner__form" @submit.prevent="submit">
        <InputText
          v-model="answer"
          placeholder="Sua resposta (ex.: 3, -2/5, x^2 - 1)"
          class="exercise-runner__input"
          data-testid="answer-input"
        />
        <Button
          type="submit"
          label="Corrigir"
          icon="pi pi-check"
          :loading="gradeMutation.isPending.value"
        />
      </form>

      <div
        v-else
        class="exercise-runner__feedback"
        :class="attempt.is_correct ? 'exercise-runner__feedback--correct' : 'exercise-runner__feedback--wrong'"
      >
        <p class="exercise-runner__feedback-title">{{ attempt.feedback_latex }}</p>
        <div v-if="attempt.expected_latex" class="exercise-runner__feedback-answer">
          <span>Resposta correta:</span>
          <FormulaText :latex="attempt.expected_latex" />
        </div>
        <FormulaText
          v-if="variant.solution_latex"
          :latex="variant.solution_latex"
          display
          class="exercise-runner__feedback-solution"
        />
        <Button
          label="Novo exercício"
          icon="pi pi-refresh"
          severity="secondary"
          @click="generate"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.exercise-runner {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}
.exercise-runner__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-xl) 0;
  text-align: center;
}
.exercise-runner__hint {
  margin: 0;
  color: var(--color-text-secondary);
}
.exercise-runner__prompt {
  font-size: var(--text-lg);
  line-height: var(--leading-relaxed);
}
.exercise-runner__form {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.exercise-runner__input {
  flex: 1;
  min-width: 12rem;
}
.exercise-runner__feedback {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-md);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}
.exercise-runner__feedback--correct {
  border-color: color-mix(in srgb, var(--color-success) 40%, var(--color-border));
  background: color-mix(in srgb, var(--color-success) 8%, transparent);
}
.exercise-runner__feedback--wrong {
  border-color: color-mix(in srgb, var(--color-danger) 40%, var(--color-border));
  background: color-mix(in srgb, var(--color-danger) 8%, transparent);
}
.exercise-runner__feedback-title {
  margin: 0;
  font-weight: 600;
}
.exercise-runner__feedback-answer {
  display: flex;
  align-items: baseline;
  gap: var(--spacing-xs);
  color: var(--color-text-secondary);
}
.exercise-runner__feedback-solution {
  margin-top: var(--spacing-xs);
}
</style>
