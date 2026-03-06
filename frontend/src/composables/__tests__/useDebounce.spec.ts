import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { ref, nextTick } from 'vue'
import { useDebounce, useDebounceFn } from '../useDebounce'

describe('useDebounce', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })
  afterEach(() => {
    vi.useRealTimers()
  })

  it('atualiza valor após delay', async () => {
    const source = ref('a')
    const debounced = useDebounce(source, 300)
    expect(debounced.value).toBe('a')
    source.value = 'b'
    await nextTick()
    expect(debounced.value).toBe('a')
    vi.advanceTimersByTime(300)
    expect(debounced.value).toBe('b')
  })

  it('cancela anterior se source mudar de novo antes do delay', async () => {
    const source = ref('a')
    const debounced = useDebounce(source, 300)
    source.value = 'b'
    await nextTick()
    vi.advanceTimersByTime(100)
    source.value = 'c'
    await nextTick()
    vi.advanceTimersByTime(300)
    expect(debounced.value).toBe('c')
  })
})

describe('useDebounceFn', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })
  afterEach(() => {
    vi.useRealTimers()
  })

  it('chama fn após delay', () => {
    const fn = vi.fn()
    const debounced = useDebounceFn(fn, 300)
    debounced()
    expect(fn).not.toHaveBeenCalled()
    vi.advanceTimersByTime(300)
    expect(fn).toHaveBeenCalledTimes(1)
  })

  it('só chama uma vez se invocado múltiplas vezes dentro do delay', () => {
    const fn = vi.fn()
    const debounced = useDebounceFn(fn, 300)
    debounced()
    debounced()
    debounced()
    vi.advanceTimersByTime(300)
    expect(fn).toHaveBeenCalledTimes(1)
  })
})
