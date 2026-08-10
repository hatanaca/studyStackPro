import { flushPromises, mount } from '@vue/test-utils'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'
import { computed } from 'vue'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flashcardsApi } from '@/api/modules/flashcards.api'
import StudyQueue from '@/features/flashcards/components/StudyQueue.vue'
import type { Flashcard } from '@/features/flashcards/types/flashcards.types'

vi.mock('@/api/modules/flashcards.api', () => ({
  flashcardsApi: {
    listDecks: vi.fn(),
    createDeck: vi.fn(),
    updateDeck: vi.fn(),
    deleteDeck: vi.fn(),
    cards: vi.fn(),
    createCard: vi.fn(),
    deleteCard: vi.fn(),
    due: vi.fn(),
    review: vi.fn(),
  },
}))

vi.mock('@/composables/useQueryAuthEnabled', () => ({
  useQuerySessionEnabled: () => computed(() => true),
}))

const card1: Flashcard = {
  id: 'card-1',
  deck_id: 'deck-1',
  front_latex: 'Frente 1',
  back_latex: 'Verso 1',
  scheduling_state: null,
  fsrs_version: '3',
  due_at: '2026-08-08T00:00:00Z',
  created_at: '2026-08-08T00:00:00Z',
}

const card2: Flashcard = {
  id: 'card-2',
  deck_id: 'deck-1',
  front_latex: 'Frente 2',
  back_latex: 'Verso 2',
  scheduling_state: null,
  fsrs_version: '3',
  due_at: '2026-08-08T00:00:00Z',
  created_at: '2026-08-08T00:00:00Z',
}

function mountQueue() {
  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false } },
  })
  return mount(StudyQueue, {
    props: { deckId: 'deck-1' },
    global: {
      plugins: [[VueQueryPlugin, { queryClient }], PrimeVue, ToastService],
    },
  })
}

function findButton(wrapper: ReturnType<typeof mountQueue>, label: string) {
  const button = wrapper.findAll('button').find((b) => b.text().includes(label))
  expect(button, `botão "${label}" não encontrado`).toBeDefined()
  return button!
}

describe('StudyQueue', () => {
  beforeEach(() => {
    vi.mocked(flashcardsApi.due).mockReset()
    vi.mocked(flashcardsApi.review).mockReset()
  })

  it('mostra vazio quando não há cartões vencidos', async () => {
    vi.mocked(flashcardsApi.due).mockResolvedValue({ data: { success: true, data: [] } })
    const wrapper = mountQueue()
    await flushPromises()
    expect(wrapper.text()).toContain('Nada para revisar')
  })

  it('mostra a frente do primeiro cartão e vira ao clicar', async () => {
    vi.mocked(flashcardsApi.due).mockResolvedValue({
      data: { success: true, data: [card1, card2] },
    })
    const wrapper = mountQueue()
    await flushPromises()

    expect(wrapper.text()).toContain('Frente 1')
    expect(wrapper.text()).not.toContain('Verso 1')

    await wrapper.find('.study-queue__card').trigger('click')
    expect(wrapper.text()).toContain('Verso 1')
  })

  it('avalia com "Bom" e avança para o próximo cartão', async () => {
    vi.mocked(flashcardsApi.due).mockResolvedValue({
      data: { success: true, data: [card1, card2] },
    })
    vi.mocked(flashcardsApi.review).mockResolvedValue({
      data: {
        success: true,
        data: { id: 'rev-1', flashcard_id: 'card-1', rating: 3, state_after: {} as never, reviewed_at: '' },
      },
    })

    const wrapper = mountQueue()
    await flushPromises()

    await wrapper.find('.study-queue__card').trigger('click')
    await findButton(wrapper, 'Bom').trigger('click')
    await flushPromises()

    expect(flashcardsApi.review).toHaveBeenCalledWith('card-1', 3, expect.anything(), expect.anything())
    expect(wrapper.text()).toContain('Frente 2')
  })
})
