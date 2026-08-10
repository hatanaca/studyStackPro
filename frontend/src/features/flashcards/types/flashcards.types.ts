/** Estado do Card do ts-fsrs persistido no servidor (JSON). */
export interface FsrsState {
  due: string
  stability: number
  difficulty: number
  elapsed_days: number
  scheduled_days: number
  learning_steps: number
  reps: number
  lapses: number
  state: number
  last_review: string | null
}

export interface FlashcardDeck {
  id: string
  name: string
  cards_count?: number
  due_count?: number
  created_at: string
  updated_at: string
}

export interface Flashcard {
  id: string
  deck_id: string
  front_latex: string
  back_latex: string
  scheduling_state: FsrsState | null
  fsrs_version: string
  due_at: string
  created_at: string
}

export interface FlashcardReview {
  id: string
  flashcard_id: string
  rating: number
  state_after: FsrsState
  reviewed_at: string
}
