import { describe, it, expect } from 'vitest'
import { ref } from 'vue'
import { useIntersectionObserver } from '../useIntersectionObserver'

describe('useIntersectionObserver', () => {
  it('returns expected API', () => {
    const target = ref<HTMLElement | null>(null)
    const observer = useIntersectionObserver(target)
    expect(observer.isIntersecting).toBeDefined()
    expect(typeof observer.isIntersecting.value).toBe('boolean')
  })

  it('isIntersecting is false initially', () => {
    const target = ref<HTMLElement | null>(null)
    const observer = useIntersectionObserver(target)
    expect(observer.isIntersecting.value).toBe(false)
  })
})
