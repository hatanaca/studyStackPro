<script setup lang="ts">
/**
 * Formulário de autoria de exercícios: cria/edita um template com prompt
 * parametrizado ({{param}}), spec de parâmetros e resposta esperada em SymPy.
 */
import { reactive, ref } from 'vue'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import { exercisesApi, type ExerciseTemplatePayload } from '@/api/modules/exercises.api'
import { useToast } from '@/composables/useToast'
import type { ExerciseTemplate } from '@/features/exercises/types/exercises.types'

const props = defineProps<{ template?: ExerciseTemplate | null }>()
const emit = defineEmits<{ (e: 'saved'): void }>()

const toast = useToast()
const saving = ref(false)

/** Renderiza o literal {{param}} sem interpolar (parser Vue não aceita chaves em string). */
const PARAM_PLACEHOLDER = '{{param}}'

interface ParamRow {
  name: string
  type: 'int' | 'float' | 'choice'
  min?: number
  max?: number
  choices: string
}

const form = reactive({
  title: props.template?.title ?? '',
  kind: (props.template?.kind ?? 'numeric') as 'numeric' | 'symbolic',
  prompt: props.template?.prompt ?? '',
  answer_expression: props.template?.answer_expression ?? '',
  solution_latex: props.template?.solution_latex ?? '',
  variables: props.template?.variables?.join(', ') ?? '',
  difficulty: props.template?.difficulty ?? 2,
})

const params = ref<ParamRow[]>(
  Object.entries(props.template?.parameters_spec ?? {}).map(([name, spec]) => ({
    name,
    type: spec.type,
    min: spec.min,
    max: spec.max,
    choices: spec.choices ? spec.choices.join(', ') : '',
  }))
)

function addParam() {
  params.value.push({ name: '', type: 'int', choices: '' })
}

function removeParam(index: number) {
  params.value.splice(index, 1)
}

function buildPayload(): ExerciseTemplatePayload {
  const parameters_spec: Record<string, unknown> = {}
  for (const p of params.value) {
    if (!p.name.trim()) continue
    if (p.type === 'choice') {
      const choices = p.choices
        .split(',')
        .map((c) => Number(c.trim()))
        .filter((n) => !Number.isNaN(n))
      if (choices.length) parameters_spec[p.name.trim()] = { type: 'choice', choices }
    } else {
      parameters_spec[p.name.trim()] = {
        type: p.type,
        min: p.min ?? undefined,
        max: p.max ?? undefined,
      }
    }
  }

  return {
    title: form.title.trim(),
    kind: form.kind,
    prompt: form.prompt.trim(),
    parameters_spec,
    answer_expression: form.answer_expression.trim(),
    solution_latex: form.solution_latex.trim() || null,
    variables: form.variables
      ? form.variables
          .split(',')
          .map((v) => v.trim())
          .filter(Boolean)
      : null,
    difficulty: form.difficulty,
  }
}

async function save() {
  saving.value = true
  try {
    const payload = buildPayload()
    if (props.template) {
      await exercisesApi.updateTemplate(props.template.id, payload)
      toast.success('Exercício atualizado.')
    } else {
      await exercisesApi.createTemplate(payload)
      toast.success('Exercício criado.')
    }
    emit('saved')
  } catch {
    toast.error('Falha ao salvar o exercício.')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form class="template-form" @submit.prevent="save">
    <div class="template-form__grid">
      <div class="template-form__field">
        <label class="template-form__label" for="tpl-title">Título</label>
        <InputText id="tpl-title" v-model="form.title" required class="template-form__input" />
      </div>

      <div class="template-form__field">
        <label class="template-form__label" for="tpl-kind">Tipo de resposta</label>
        <Select
          id="tpl-kind"
          v-model="form.kind"
          :options="[
            { value: 'numeric', label: 'Numérica' },
            { value: 'symbolic', label: 'Simbólica' },
          ]"
          option-label="label"
          option-value="value"
          class="template-form__input"
        />
      </div>
    </div>

    <div class="template-form__field">
      <label class="template-form__label" for="tpl-prompt">
        Enunciado (LaTeX com $...$; use placeholders {{ PARAM_PLACEHOLDER }})
      </label>
      <Textarea
        id="tpl-prompt"
        v-model="form.prompt"
        rows="3"
        auto-resize
        required
        :placeholder="'Ex.: Resolva a equação: {{a}}x + {{b}} = 0'"
        class="template-form__input"
      />
    </div>

    <div class="template-form__params">
      <div class="template-form__params-head">
        <span class="template-form__label">Parâmetros aleatórios</span>
        <Button
          type="button"
          label="Adicionar"
          icon="pi pi-plus"
          size="small"
          text
          @click="addParam"
        />
      </div>
      <div
        v-for="(p, i) in params"
        :key="i"
        class="template-form__param"
      >
        <InputText v-model="p.name" placeholder="nome" class="template-form__param-name" />
        <Select
          v-model="p.type"
          :options="[
            { value: 'int', label: 'int' },
            { value: 'float', label: 'float' },
            { value: 'choice', label: 'escolha' },
          ]"
          option-label="label"
          option-value="value"
          class="template-form__param-type"
        />
        <template v-if="p.type === 'choice'">
          <InputText
            v-model="p.choices"
            placeholder="opções separadas por vírgula (1, 2, 3)"
            class="template-form__param-choices"
          />
        </template>
        <template v-else>
          <InputNumber v-model="p.min" placeholder="min" class="template-form__param-bound" />
          <InputNumber v-model="p.max" placeholder="max" class="template-form__param-bound" />
        </template>
        <Button
          type="button"
          icon="pi pi-times"
          severity="danger"
          text
          size="small"
          aria-label="Remover parâmetro"
          @click="removeParam(i)"
        />
      </div>
      <p v-if="!params.length" class="template-form__hint">
        Sem parâmetros = exercício fixo. Adicione um para gerar variações.
      </p>
    </div>

    <div class="template-form__grid">
      <div class="template-form__field">
        <label class="template-form__label" for="tpl-answer">
          Resposta esperada (SymPy, com {{ PARAM_PLACEHOLDER }})
        </label>
        <InputText
          id="tpl-answer"
          v-model="form.answer_expression"
          required
          :placeholder="'Ex.: -{{b}}/{{a}}'"
          class="template-form__input"
        />
      </div>

      <div class="template-form__field">
        <label class="template-form__label" for="tpl-vars">Variáveis (simbólica, separadas por vírgula)</label>
        <InputText id="tpl-vars" v-model="form.variables" placeholder="Ex.: x" class="template-form__input" />
      </div>
    </div>

    <div class="template-form__grid">
      <div class="template-form__field">
        <label class="template-form__label" for="tpl-solution">Solução (LaTeX, opcional)</label>
        <InputText
          id="tpl-solution"
          v-model="form.solution_latex"
          :placeholder="'Ex.: x = -{{b}}/{{a}}'"
          class="template-form__input"
        />
      </div>

      <div class="template-form__field">
        <label class="template-form__label" for="tpl-difficulty">Dificuldade</label>
        <Select
          id="tpl-difficulty"
          v-model="form.difficulty"
          :options="[
            { value: 1, label: '1 - Fácil' },
            { value: 2, label: '2 - Médio' },
            { value: 3, label: '3 - Difícil' },
            { value: 4, label: '4 - Avançado' },
            { value: 5, label: '5 - Desafio' },
          ]"
          option-label="label"
          option-value="value"
          class="template-form__input"
        />
      </div>
    </div>

    <div class="template-form__actions">
      <Button
        type="submit"
        :label="props.template ? 'Salvar alterações' : 'Criar exercício'"
        icon="pi pi-check"
        :loading="saving"
      />
    </div>
  </form>
</template>

<style scoped>
.template-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
.template-form__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-md);
}
@media (max-width: 640px) {
  .template-form__grid {
    grid-template-columns: 1fr;
  }
}
.template-form__field {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xs);
}
.template-form__label {
  font-size: var(--text-sm);
  font-weight: 600;
}
.template-form__input {
  width: 100%;
}
.template-form__params {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  padding: var(--spacing-md);
  background: var(--color-bg-soft);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}
.template-form__params-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.template-form__param {
  display: flex;
  gap: var(--spacing-xs);
  align-items: center;
}
.template-form__param-name {
  width: 7rem;
}
.template-form__param-type {
  width: 7rem;
}
.template-form__param-choices {
  flex: 1;
}
.template-form__param-bound {
  width: 6rem;
}
.template-form__hint {
  margin: 0;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
.template-form__actions {
  display: flex;
  justify-content: flex-end;
}
</style>
