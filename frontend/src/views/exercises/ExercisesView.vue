<script setup lang="ts">
/**
 * View de Exercícios de Matemática: catálogo de templates (globais + próprios),
 * autoria de novos exercícios, runner de prática e histórico de tentativas.
 */
import { computed, ref } from 'vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import { useConfirm } from 'primevue/useconfirm'
import FormulaText from '@/components/math/FormulaText.vue'
import GraphPlot from '@/components/math/GraphPlot.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import StackSkeleton from '@/components/ui/StackSkeleton.vue'
import ExerciseRunner from '@/features/exercises/components/ExerciseRunner.vue'
import TemplateForm from '@/features/exercises/components/TemplateForm.vue'
import {
  useAttemptsQuery,
  useStatsQuery,
  useTemplatesQuery,
} from '@/features/exercises/composables/useExercisesQuery'
import type { ExerciseTemplate } from '@/features/exercises/types/exercises.types'
import { exercisesApi } from '@/api/modules/exercises.api'
import { useToast } from '@/composables/useToast'

const toast = useToast()
const confirm = useConfirm()
const { data: templates, isLoading, isError, error, refetch } = useTemplatesQuery()
const { data: attempts } = useAttemptsQuery()
const { data: stats } = useStatsQuery()
const selected = ref<ExerciseTemplate | null>(null)
const showGlobal = ref(true)
const showForm = ref(false)
const editing = ref<ExerciseTemplate | null>(null)
const graphFn = ref('x^2, sin(x)')
const graphError = ref('')

const graphFns = computed(() =>
  graphFn.value
    .split(',')
    .map((fn) => fn.trim())
    .filter(Boolean)
)

const visibleTemplates = computed(() =>
  (templates.value ?? []).filter((t) => showGlobal.value || !t.is_global)
)

function select(template: ExerciseTemplate) {
  selected.value = template
}

function openCreate() {
  editing.value = null
  showForm.value = true
}

function openEdit(template: ExerciseTemplate) {
  editing.value = template
  showForm.value = true
}

async function handleSaved() {
  showForm.value = false
  await refetch()
}

function removeTemplate(template: ExerciseTemplate) {
  confirm.require({
    message: `Excluir o exercício "${template.title}"?`,
    header: 'Confirmar exclusão',
    acceptLabel: 'Excluir',
    rejectLabel: 'Cancelar',
    accept: async () => {
      try {
        await exercisesApi.deleteTemplate(template.id)
        toast.success('Exercício excluído.')
        if (selected.value?.id === template.id) selected.value = null
        await refetch()
      } catch {
        toast.error('Falha ao excluir o exercício.')
      }
    },
  })
}

function difficultyLabel(difficulty: number): string {
  return ['', 'Fácil', 'Médio', 'Difícil', 'Avançado', 'Desafio'][difficulty] ?? ''
}
</script>

<template>
  <section class="exercises">
    <header class="exercises__header">
      <h1 class="exercises__title">Exercícios de Matemática</h1>
      <p class="exercises__subtitle">
        Pratique com exercícios gerados aleatoriamente e corrigidos automaticamente pelo SymPy.
      </p>
    </header>

    <ErrorCard v-if="isError" :error="error" />

    <div v-else class="exercises__layout">
      <div class="exercises__catalog">
        <div class="exercises__catalog-head">
          <h2 class="exercises__section-title">Exercícios</h2>
          <div class="exercises__catalog-actions">
            <label class="exercises__filter-label">
              <input v-model="showGlobal" type="checkbox" />
              Incluir globais
            </label>
            <Button label="Novo" icon="pi pi-plus" size="small" @click="openCreate" />
          </div>
        </div>

        <StackSkeleton v-if="isLoading" :count="3" />

        <EmptyState v-else-if="!visibleTemplates.length" title="Nenhum exercício disponível." />

        <ul v-else class="exercises__list">
          <li
            v-for="template in visibleTemplates"
            :key="template.id"
            class="exercises__item"
          >
            <button
              type="button"
              class="exercises__card"
              :class="{ 'exercises__card--active': selected?.id === template.id }"
              @click="select(template)"
            >
              <span class="exercises__card-main">
                <span class="exercises__card-title">{{ template.title }}</span>
                <span class="exercises__card-meta">
                  <Tag
                    :value="template.kind === 'symbolic' ? 'Simbólica' : 'Numérica'"
                    :severity="template.kind === 'symbolic' ? 'info' : 'contrast'"
                    rounded
                  />
                  <Tag
                    :value="difficultyLabel(template.difficulty)"
                    :severity="template.difficulty >= 4 ? 'warn' : 'secondary'"
                    rounded
                  />
                </span>
              </span>
              <span class="exercises__card-cta">
                <Button
                  :label="selected?.id === template.id ? 'Praticando' : 'Praticar'"
                  :icon="selected?.id === template.id ? 'pi pi-stop' : 'pi pi-play'"
                  size="small"
                  :severity="selected?.id === template.id ? 'secondary' : 'primary'"
                  @click.stop="select(template)"
                />
              </span>
            </button>
            <div v-if="!template.is_global" class="exercises__item-actions">
              <Button
                icon="pi pi-pencil"
                text
                size="small"
                aria-label="Editar exercício"
                @click="openEdit(template)"
              />
              <Button
                icon="pi pi-trash"
                text
                severity="danger"
                size="small"
                aria-label="Excluir exercício"
                @click="removeTemplate(template)"
              />
            </div>
          </li>
        </ul>
      </div>

      <div class="exercises__runner">
        <ExerciseRunner v-if="selected" :template="selected" />
        <EmptyState v-else title="Selecione um exercício para começar a praticar." />
      </div>
    </div>

    <section class="exercises__explorer">
      <div class="exercises__explorer-head">
        <h2 class="exercises__section-title">Explorador de funções</h2>
        <InputText
          v-model="graphFn"
          placeholder="f(x) — ex.: x^2, sin(x), 1/x"
          class="exercises__explorer-input"
          @update:model-value="graphError = ''"
        />
      </div>
      <p v-if="graphError" class="exercises__explorer-error">
        Expressão inválida: {{ graphError }}
      </p>
      <GraphPlot :fns="graphFns" @error="graphError = $event" />
    </section>

    <section class="exercises__history">
      <div class="exercises__history-head">
        <h2 class="exercises__section-title">Tentativas recentes</h2>
        <Tag
          v-if="stats && stats.total_attempts > 0"
          :value="`Acerto: ${Math.round(stats.accuracy * 100)}% (${stats.correct_attempts}/${stats.total_attempts})`"
          :severity="stats.accuracy >= 0.7 ? 'success' : stats.accuracy >= 0.4 ? 'warn' : 'danger'"
          rounded
        />
      </div>
      <StackSkeleton v-if="!attempts" :count="2" />
      <EmptyState
        v-else-if="!attempts.length"
        title="Você ainda não resolveu exercícios."
      />
      <ul v-else class="exercises__attempts">
        <li v-for="attempt in attempts.slice(0, 10)" :key="attempt.id" class="exercises__attempt">
          <Tag
            :value="attempt.is_correct ? 'Correta' : 'Errada'"
            :severity="attempt.is_correct ? 'success' : 'danger'"
            rounded
          />
          <span class="exercises__attempt-title">{{ attempt.template_title }}</span>
          <FormulaText v-if="attempt.expected_latex" :latex="attempt.expected_latex" />
          <time class="exercises__attempt-date">
            {{ new Date(attempt.submitted_at).toLocaleString() }}
          </time>
        </li>
      </ul>
    </section>

    <Dialog
      v-model:visible="showForm"
      :header="editing ? 'Editar exercício' : 'Novo exercício'"
      modal
      class="exercises__dialog"
    >
      <TemplateForm :template="editing" @saved="handleSaved" />
    </Dialog>
  </section>
</template>

<style scoped>
.exercises {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
}
.exercises__header {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.exercises__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--text-2xl);
}
.exercises__subtitle {
  margin: 0;
  color: var(--color-text-secondary);
}
.exercises__layout {
  display: grid;
  grid-template-columns: minmax(16rem, 2fr) minmax(18rem, 3fr);
  gap: var(--spacing-lg);
  align-items: start;
}
@media (max-width: 860px) {
  .exercises__layout {
    grid-template-columns: 1fr;
  }
}
.exercises__catalog,
.exercises__runner {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  padding: var(--spacing-lg);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
}
.exercises__catalog-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.exercises__catalog-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}
.exercises__section-title {
  margin: 0;
  font-size: var(--text-lg);
}
.exercises__filter-label {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
}
.exercises__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.exercises__item {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}
.exercises__card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-sm);
  flex: 1;
  min-width: 0;
  padding: var(--spacing-md);
  text-align: left;
  background: var(--color-bg-soft);
  border: 1px solid transparent;
  border-radius: var(--radius-md);
  color: inherit;
  cursor: pointer;
  transition:
    border-color var(--duration-fast) ease,
    background var(--duration-fast) ease;
}
.exercises__card:hover {
  border-color: var(--color-border);
}
.exercises__card--active {
  border-color: color-mix(in srgb, var(--color-primary) 40%, var(--color-border));
  background: var(--color-primary-soft);
}
.exercises__card-main {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  min-width: 0;
}
.exercises__card-title {
  font-weight: 600;
}
.exercises__card-meta {
  display: flex;
  gap: var(--spacing-xs);
}
.exercises__item-actions {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xs);
  flex-shrink: 0;
}
.exercises__history {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
.exercises__history-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.exercises__attempts {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.exercises__attempt {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}
.exercises__attempt-title {
  font-weight: 500;
}
.exercises__attempt-date {
  margin-left: auto;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
.exercises__explorer {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  padding: var(--spacing-lg);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
}
.exercises__explorer-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-md);
  flex-wrap: wrap;
}
.exercises__explorer-input {
  min-width: 16rem;
}
.exercises__explorer-error {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-danger);
}
</style>
