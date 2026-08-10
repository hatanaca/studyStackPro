import { describe, expect, it } from 'vitest'
import { createInitialState, scheduleNext } from '@/features/flashcards/composables/useFsrs'

const NOW = new Date('2026-08-08T12:00:00.000Z')

describe('useFsrs', () => {
  it('cria estado inicial com due imediato (cartão novo)', () => {
    const { state, dueAt } = createInitialState(NOW)
    expect(state.reps).toBe(0)
    expect(state.state).toBe(0)
    expect(dueAt).toBe(NOW.toISOString())
  })

  it('primeiro Good agenda para o futuro e incrementa reps', () => {
    const initial = createInitialState(NOW)
    const next = scheduleNext(initial.state, 3, NOW)
    expect(next.state.reps).toBe(1)
    expect(next.state.state).toBeGreaterThan(0)
    expect(new Date(next.dueAt).getTime()).toBeGreaterThan(NOW.getTime())
  })

  it('Again mantém o cartão mais próximo que Good', () => {
    const initial = createInitialState(NOW)
    const again = scheduleNext(initial.state, 1, NOW)
    const good = scheduleNext(initial.state, 3, NOW)
    expect(new Date(again.dueAt).getTime()).toBeLessThan(new Date(good.dueAt).getTime())
  })

  it('é determinístico para o mesmo input', () => {
    const initial = createInitialState(NOW)
    expect(scheduleNext(initial.state, 3, NOW)).toEqual(scheduleNext(initial.state, 3, NOW))
  })

  it('aceita estado persistido (com due em string ISO)', () => {
    const initial = createInitialState(NOW)
    const first = scheduleNext(initial.state, 3, NOW)
    const second = scheduleNext(first.state, 3, new Date('2026-08-09T12:00:00.000Z'))
    expect(second.state.reps).toBe(2)
    expect(second.state.due).toBeTruthy()
  })
})
