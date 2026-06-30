import { useLocalStorage } from '../useLocalStorage'

describe('useLocalStorage', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('returns default value when nothing stored', () => {
    const value = useLocalStorage('test-key', 'default')
    expect(value.value).toBe('default')
  })

  it('reflects updates via ref assignment', () => {
    const value = useLocalStorage('test-key', 'initial')
    value.value = 'updated'
    expect(value.value).toBe('updated')
  })

  it('returns default for different key', () => {
    localStorage.setItem('other-key', JSON.stringify(42))
    const value = useLocalStorage('test-key', 0)
    expect(value.value).toBe(0)
  })

  it('handles primitive types', () => {
    const num = useLocalStorage('num', 0)
    num.value = 42
    expect(num.value).toBe(42)

    const bool = useLocalStorage('bool', false)
    bool.value = true
    expect(bool.value).toBe(true)
  })

  it('handles null default', () => {
    const value = useLocalStorage<string | null>('nullable', null)
    expect(value.value).toBeNull()
  })
})
