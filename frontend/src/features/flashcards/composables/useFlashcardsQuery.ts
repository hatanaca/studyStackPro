import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { unwrap } from '@/api/client'
import { flashcardsApi } from '@/api/modules/flashcards.api'
import { queryKeys } from '@/api/queryKeys'
import { useQuerySessionEnabled } from '@/composables/useQueryAuthEnabled'
import type {
  Flashcard,
  FlashcardDeck,
  FsrsState,
} from '@/features/flashcards/types/flashcards.types'

export function useDecksQuery() {
  const enabled = useQuerySessionEnabled()

  return useQuery({
    queryKey: queryKeys.flashcards.decks(),
    queryFn: async (): Promise<FlashcardDeck[]> => unwrap(flashcardsApi.listDecks()),
    staleTime: 30 * 1000,
    enabled,
  })
}

export function useDeckCardsQuery(deckId: MaybeRefOrGetter<string | null>) {
  const enabled = useQuerySessionEnabled()

  return useQuery({
    queryKey: computed(() => queryKeys.flashcards.cards(toValue(deckId) ?? 'none')),
    queryFn: async (): Promise<Flashcard[]> =>
      unwrap(flashcardsApi.cards(toValue(deckId) as string)),
    enabled: computed(() => enabled.value && !!toValue(deckId)),
  })
}

export function useDueQuery(deckId?: MaybeRefOrGetter<string | null>) {
  const enabled = useQuerySessionEnabled()

  return useQuery({
    queryKey: queryKeys.flashcards.due(),
    queryFn: async (): Promise<Flashcard[]> => unwrap(flashcardsApi.due(toValue(deckId) ?? undefined)),
    staleTime: 15 * 1000,
    enabled,
  })
}

export function useCreateDeckMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (name: string) => unwrap(flashcardsApi.createDeck(name)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.flashcards.decks() })
    },
  })
}

export function useDeleteDeckMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: string) => unwrap(flashcardsApi.deleteDeck(id)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.flashcards.decks() })
    },
  })
}

export function useCreateCardMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ deckId, front, back }: { deckId: string; front: string; back: string }) =>
      unwrap(flashcardsApi.createCard(deckId, front, back)),
    onSuccess: (_data, vars) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.flashcards.cards(vars.deckId) })
      queryClient.invalidateQueries({ queryKey: queryKeys.flashcards.due() })
    },
  })
}

export function useDeleteCardMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: string) => unwrap(flashcardsApi.deleteCard(id)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.flashcards.all })
    },
  })
}

export function useReviewMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({
      id,
      rating,
      stateAfter,
      dueAt,
    }: {
      id: string
      rating: number
      stateAfter: FsrsState
      dueAt: string
    }) => unwrap(flashcardsApi.review(id, rating, stateAfter, dueAt)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.flashcards.all })
    },
  })
}
