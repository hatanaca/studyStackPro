import { apiClient } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { ApiResponse } from '@/types/api.types'
import type {
  Flashcard,
  FlashcardDeck,
  FlashcardReview,
  FsrsState,
} from '@/features/flashcards/types/flashcards.types'

export const flashcardsApi = {
  listDecks: () =>
    apiClient.get<ApiResponse<FlashcardDeck[]>>(ENDPOINTS.flashcards.decks),

  createDeck: (name: string) =>
    apiClient.post<ApiResponse<FlashcardDeck>>(ENDPOINTS.flashcards.decks, { name }),

  updateDeck: (id: string, name: string) =>
    apiClient.put<ApiResponse<FlashcardDeck>>(ENDPOINTS.flashcards.deck(id), { name }),

  deleteDeck: (id: string) =>
    apiClient.delete<ApiResponse<null>>(ENDPOINTS.flashcards.deck(id)),

  cards: (deckId: string) =>
    apiClient.get<ApiResponse<Flashcard[]>>(ENDPOINTS.flashcards.cards(deckId)),

  createCard: (deckId: string, frontLatex: string, backLatex: string) =>
    apiClient.post<ApiResponse<Flashcard>>(ENDPOINTS.flashcards.cards(deckId), {
      front_latex: frontLatex,
      back_latex: backLatex,
    }),

  deleteCard: (id: string) =>
    apiClient.delete<ApiResponse<null>>(ENDPOINTS.flashcards.card(id)),

  due: (deckId?: string) =>
    apiClient.get<ApiResponse<Flashcard[]>>(ENDPOINTS.flashcards.due, {
      params: deckId ? { deck_id: deckId } : undefined,
    }),

  review: (id: string, rating: number, stateAfter: FsrsState, dueAt: string) =>
    apiClient.post<ApiResponse<FlashcardReview>>(ENDPOINTS.flashcards.review(id), {
      rating,
      state_after: stateAfter,
      due_at: dueAt,
    }),
}
