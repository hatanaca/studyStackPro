<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { StudyQuestion, StudyAttemptResult } from '../types/ita-study.types'
import { useGenerateQuestionMutation, useSubmitAnswerMutation } from '../composables/useItaStudyQuery'

const props = defineProps<{
  subTopicId: string
}>()

const emit = defineEmits<{
  (e: 'complete', result: StudyAttemptResult): void
}>()

const generateMutation = useGenerateQuestionMutation()
const submitMutation = useSubmitAnswerMutation()

const currentQuestion = ref<StudyQuestion | null>(null)
const userAnswer = ref('')
const lastResult = ref<StudyAttemptResult | null>(null)
const showSolution = ref(false)
const startTime = ref<number>(0)

const isLoading = computed(() => generateMutation.isPending.value)
const isSubmitting = computed(() => submitMutation.isPending.value)

async function loadQuestion() {
  showSolution.value = false
  lastResult.value = null
  userAnswer.value = ''

  try {
    const result = await generateMutation.mutateAsync({ subTopicId: props.subTopicId })
    currentQuestion.value = result
    startTime.value = Date.now()
  } catch (error) {
    console.error('Failed to generate question:', error)
  }
}

async function submitAnswer() {
  if (!currentQuestion.value || !userAnswer.value.trim()) return

  const timeSpent = Math.floor((Date.now() - startTime.value) / 1000)

  try {
    const result = await submitMutation.mutateAsync({
      variantId: currentQuestion.value.variant_id,
      answer: userAnswer.value.trim(),
      timeSpentSeconds: timeSpent,
    })

    lastResult.value = result
    showSolution.value = true
    emit('complete', result)
  } catch (error) {
    console.error('Failed to submit answer:', error)
  }
}

function handleChoiceSelect(choice: string) {
  userAnswer.value = choice
  submitAnswer()
}

onMounted(() => {
  loadQuestion()
})
</script>

<template>
  <div class="question-panel">
    <div v-if="isLoading" class="loading-state">
      <i class="pi pi-spin pi-spinner"></i>
      <span>Carregando questão...</span>
    </div>

    <div v-else-if="currentQuestion" class="question-content">
      <div class="question-header">
        <span class="question-kind">{{ currentQuestion.kind === 'multiple_choice' ? 'Múltipla Escolha' : 'Numérica' }}</span>
        <span class="question-difficulty">Nível {{ currentQuestion.difficulty }}</span>
      </div>

      <div class="question-prompt" v-html="currentQuestion.prompt"></div>

      <div v-if="currentQuestion.hint" class="question-hint">
        <i class="pi pi-lightbulb"></i>
        <span>{{ currentQuestion.hint }}</span>
      </div>

      <div v-if="!showSolution" class="answer-section">
        <div v-if="currentQuestion.kind === 'multiple_choice' && currentQuestion.choices" class="choices-grid">
          <button
            v-for="(choice, index) in currentQuestion.choices"
            :key="index"
            class="choice-btn"
            :class="{ selected: userAnswer === choice }"
            @click="handleChoiceSelect(choice)"
          >
            {{ choice }}
          </button>
        </div>

        <div v-else class="input-section">
          <InputText
            v-model="userAnswer"
            placeholder="Digite sua resposta..."
            class="answer-input"
            :disabled="isSubmitting"
            @keyup.enter="submitAnswer"
          />
          <Button
            label="Verificar"
            :loading="isSubmitting"
            :disabled="!userAnswer.trim()"
            @click="submitAnswer"
          />
        </div>
      </div>

      <div v-if="showSolution && lastResult" class="solution-section">
        <div class="result-banner" :class="lastResult.is_correct ? 'correct' : 'incorrect'">
          <i :class="lastResult.is_correct ? 'pi-check-circle' : 'pi-times-circle'"></i>
          <span>{{ lastResult.is_correct ? 'Correto!' : 'Incorreto' }}</span>
        </div>

        <div v-if="!lastResult.is_correct" class="expected-answer">
          <strong>Resposta esperada:</strong> {{ lastResult.expected }}
        </div>

        <div v-if="lastResult.solution_latex" class="solution-latex" v-html="lastResult.solution_latex"></div>

        <div v-if="lastResult.explanation" class="explanation">
          <strong>Explicação:</strong> {{ lastResult.explanation }}
        </div>

        <Button
          label="Próxima Questão"
          class="next-btn"
          @click="loadQuestion"
        />
      </div>
    </div>

    <div v-else class="empty-state">
      <i class="pi pi-inbox"></i>
      <span>Nenhuma questão disponível</span>
    </div>
  </div>
</template>

<style scoped>
.question-panel {
  padding: 1.5rem;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 3rem;
  color: var(--p-text-muted-color);
}

.question-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 1rem;
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}

.question-prompt {
  font-size: 1.125rem;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.question-hint {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: var(--p-yellow-50);
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: var(--p-yellow-700);
  margin-bottom: 1.5rem;
}

.answer-section {
  margin-top: 1.5rem;
}

.choices-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 0.75rem;
}

.choice-btn {
  padding: 0.75rem 1rem;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  background: var(--p-surface-0);
  cursor: pointer;
  transition: all 0.2s;
  text-align: left;
}

.choice-btn:hover {
  border-color: var(--p-primary-color);
}

.choice-btn.selected {
  border-color: var(--p-primary-color);
  background: var(--p-primary-50);
}

.input-section {
  display: flex;
  gap: 0.75rem;
}

.answer-input {
  flex: 1;
}

.solution-section {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--p-border-color);
}

.result-banner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  border-radius: 0.5rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.result-banner.correct {
  background: var(--p-green-50);
  color: var(--p-green-700);
}

.result-banner.incorrect {
  background: var(--p-red-50);
  color: var(--p-red-700);
}

.expected-answer {
  padding: 0.75rem;
  background: var(--p-surface-100);
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

.solution-latex {
  padding: 1rem;
  background: var(--p-surface-50);
  border-radius: 0.5rem;
  margin-bottom: 1rem;
  font-family: 'KaTeX_Math', serif;
}

.explanation {
  padding: 1rem;
  background: var(--p-blue-50);
  border-radius: 0.5rem;
  margin-bottom: 1rem;
  font-size: 0.875rem;
}

.next-btn {
  width: 100%;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 3rem;
  color: var(--p-text-muted-color);
}
</style>
