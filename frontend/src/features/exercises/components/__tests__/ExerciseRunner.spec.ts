import { flushPromises, mount } from '@vue/test-utils'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { exercisesApi } from '@/api/modules/exercises.api'
import ExerciseRunner from '@/features/exercises/components/ExerciseRunner.vue'
import type {
  ExerciseAttempt,
  ExerciseTemplate,
  ExerciseVariant,
} from '@/features/exercises/types/exercises.types'

vi.mock('@/api/modules/exercises.api', () => ({
  exercisesApi: {
    listTemplates: vi.fn(),
    createTemplate: vi.fn(),
    updateTemplate: vi.fn(),
    deleteTemplate: vi.fn(),
    generateVariant: vi.fn(),
    grade: vi.fn(),
    attempts: vi.fn(),
  },
}))

const template: ExerciseTemplate = {
  id: 'tpl-1',
  title: 'Equação do 1º grau',
  kind: 'numeric',
  prompt: 'Resolva {{a}}x + {{b}} = 0',
  parameters_spec: {},
  answer_expression: '-{{b}}/{{a}}',
  solution_latex: null,
  variables: null,
  difficulty: 2,
  is_global: true,
  created_at: '2026-08-08T00:00:00Z',
  updated_at: '2026-08-08T00:00:00Z',
}

const variant: ExerciseVariant = {
  id: 'var-1',
  template_id: 'tpl-1',
  parameters: { a: 2, b: 4 },
  prompt_latex: 'Resolva $2x + 4 = 0$',
  answer_expr: '-2',
  solution_latex: 'x = -2',
  created_at: '2026-08-08T00:00:00Z',
}

const attempt: ExerciseAttempt = {
  id: 'att-1',
  variant_id: 'var-1',
  template_title: 'Equação do 1º grau',
  answer: '-2',
  is_correct: true,
  feedback_latex: 'Resposta correta!',
  expected_latex: '-2',
  submitted_at: '2026-08-08T00:00:00Z',
  created_at: '2026-08-08T00:00:00Z',
}

function mountRunner() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return mount(ExerciseRunner, {
    props: { template },
    global: {
      plugins: [[VueQueryPlugin, { queryClient }], PrimeVue, ToastService],
    },
  })
}

function findButton(wrapper: ReturnType<typeof mountRunner>, label: string) {
  const button = wrapper.findAll('button').find((b) => b.text().includes(label))
  expect(button, `botão "${label}" não encontrado`).toBeDefined()
  return button!
}

describe('ExerciseRunner', () => {
  beforeEach(() => {
    vi.mocked(exercisesApi.generateVariant).mockReset()
    vi.mocked(exercisesApi.grade).mockReset()
  })

  it('mostra estado inicial sem variante', () => {
    const wrapper = mountRunner()
    expect(wrapper.text()).toContain('Pronto para praticar')
  })

  it('gera variante e exibe o enunciado', async () => {
    vi.mocked(exercisesApi.generateVariant).mockResolvedValue({
      data: { success: true, data: variant },
    })

    const wrapper = mountRunner()
    await findButton(wrapper, 'Gerar exercício').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Resolva')
    expect(wrapper.find('.formula-text').exists()).toBe(true)
  })

  it('corrige a resposta e exibe o feedback', async () => {
    vi.mocked(exercisesApi.generateVariant).mockResolvedValue({
      data: { success: true, data: variant },
    })
    vi.mocked(exercisesApi.grade).mockResolvedValue({
      data: { success: true, data: attempt },
    })

    const wrapper = mountRunner()
    await findButton(wrapper, 'Gerar exercício').trigger('click')
    await flushPromises()

    await wrapper.get('[data-testid="answer-input"]').setValue('-2')
    await wrapper.vm.$nextTick()
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(exercisesApi.grade).toHaveBeenCalledWith('var-1', '-2')
    expect(wrapper.text()).toContain('Resposta correta!')
  })

  it('trocar de template reseta o estado', async () => {
    vi.mocked(exercisesApi.generateVariant).mockResolvedValue({
      data: { success: true, data: variant },
    })

    const wrapper = mountRunner()
    await findButton(wrapper, 'Gerar exercício').trigger('click')
    await flushPromises()
    expect(wrapper.find('.formula-text').exists()).toBe(true)

    await wrapper.setProps({ template: { ...template, id: 'tpl-2' } })
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Pronto para praticar')
  })
})
