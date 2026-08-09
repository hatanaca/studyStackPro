import { FSRS, Rating, createEmptyCard, generatorParameters } from 'ts-fsrs'
import type { Card, CardInput, Grade } from 'ts-fsrs'
import type { FsrsState } from '@/features/flashcards/types/flashcards.types'

/**
 * Wrapper sobre ts-fsrs (algoritmo de repetição espaçada do Anki).
 * O cliente calcula o próximo agendamento; o servidor persiste o snapshot.
 * `now` é injetável para testes determinísticos.
 */
const fsrs = new FSRS(generatorParameters())

export const RATING_LABELS = [
  { value: Rating.Again, label: 'De novo' },
  { value: Rating.Hard, label: 'Difícil' },
  { value: Rating.Good, label: 'Bom' },
  { value: Rating.Easy, label: 'Fácil' },
] as const

/** Estado inicial de um cartão novo (due imediato). */
export function createInitialState(now: Date = new Date()): { state: FsrsState; dueAt: string } {
  const card = createEmptyCard(now)
  return { state: serializeCard(card), dueAt: card.due.toISOString() }
}

/**
 * Calcula o próximo estado do cartão após a nota (1=Again..4=Easy).
 * Aceita o estado persistido (ou null para cartão novo).
 */
export function scheduleNext(
  state: FsrsState | null,
  rating: number,
  now: Date = new Date()
): { state: FsrsState; dueAt: string } {
  const cardInput: CardInput = state
    ? {
        ...state,
        due: state.due,
        last_review: state.last_review ?? undefined,
      }
    : createEmptyCard(now)

  const record = fsrs.repeat(cardInput, now)[rating as Grade]
  return { state: serializeCard(record.card), dueAt: record.card.due.toISOString() }
}

function serializeCard(card: Card): FsrsState {
  return {
    due: card.due.toISOString(),
    stability: card.stability,
    difficulty: card.difficulty,
    elapsed_days: card.elapsed_days,
    scheduled_days: card.scheduled_days,
    learning_steps: card.learning_steps,
    reps: card.reps,
    lapses: card.lapses,
    state: card.state,
    last_review: card.last_review ? card.last_review.toISOString() : null,
  }
}
