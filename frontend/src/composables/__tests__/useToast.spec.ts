import { describe, it, expect, vi, beforeEach } from 'vitest'

vi.mock('primevue/usetoast', () => ({
  useToast: () => ({
    add: vi.fn(),
  }),
}))

import { useToast } from '../useToast'

describe('useToast', () => {
  it('returns expected API', () => {
    const toast = useToast()
    expect(typeof toast.show).toBe('function')
    expect(typeof toast.success).toBe('function')
    expect(typeof toast.error).toBe('function')
    expect(typeof toast.info).toBe('function')
    expect(toast.toasts).toBeDefined()
    expect(typeof toast.dismiss).toBe('function')
  })

  it('success does not throw', () => {
    const toast = useToast()
    expect(() => toast.success('Operation completed')).not.toThrow()
  })

  it('error does not throw', () => {
    const toast = useToast()
    expect(() => toast.error('Something went wrong')).not.toThrow()
  })

  it('info does not throw', () => {
    const toast = useToast()
    expect(() => toast.info('For your information')).not.toThrow()
  })

  it('show does not throw', () => {
    const toast = useToast()
    expect(() => toast.show('Hello')).not.toThrow()
  })

  it('dismiss is callable', () => {
    const toast = useToast()
    expect(() => toast.dismiss()).not.toThrow()
  })
})
