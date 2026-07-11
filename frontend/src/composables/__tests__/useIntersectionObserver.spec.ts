import { useIntersectionObserver } from '../useIntersectionObserver'

describe('useIntersectionObserver', () => {
  it('returns expected API', () => {
    const observer = useIntersectionObserver()
    expect(typeof observer.observe).toBe('function')
    expect(typeof observer.unobserve).toBe('function')
    expect(typeof observer.disconnect).toBe('function')
  })

  it('observe and unobserve are callable without error', () => {
    const observer = useIntersectionObserver()
    const mockEl = document.createElement('div')

    expect(() => observer.observe(mockEl)).not.toThrow()
    expect(() => observer.unobserve(mockEl)).not.toThrow()
  })

  it('disconnect is callable without error', () => {
    const observer = useIntersectionObserver()
    expect(() => observer.disconnect()).not.toThrow()
  })
})
