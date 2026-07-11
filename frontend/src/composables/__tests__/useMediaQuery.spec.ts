import { useMediaQuery } from '../useMediaQuery'

describe('useMediaQuery', () => {
  it('returns a ref', () => {
    const matches = useMediaQuery('(min-width: 768px)')
    expect(matches).toBeDefined()
    expect(typeof matches.value).toBe('boolean')
  })

  it('returns false for non-matching query in test env', () => {
    // happy-dom doesn't implement matchMedia, so it should return false
    const matches = useMediaQuery('(min-width: 9999px)')
    expect(matches.value).toBe(false)
  })
})
