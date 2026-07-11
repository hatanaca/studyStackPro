import { describe, it, expect } from 'vitest'
import { useConfirm } from '../useConfirm'

describe('useConfirm', () => {
  it('returns expected API', () => {
    const confirm = useConfirm()
    expect(typeof confirm.confirm).toBe('function')
    expect(typeof confirm.cancel).toBe('function')
    expect(typeof confirm.open).toBe('function')
    expect(confirm.isOpen).toBeDefined()
    expect(confirm.options).toBeDefined()
  })

  it('isOpen is false initially', () => {
    const confirm = useConfirm()
    expect(confirm.isOpen.value).toBe(false)
  })

  it('open sets isOpen to true', async () => {
    const confirm = useConfirm()
    const promise = confirm.open({ message: 'Test?' })
    expect(confirm.isOpen.value).toBe(true)
    confirm.cancel()
    const result = await promise
    expect(result).toBe(false)
  })

  it('confirm resolves to true', async () => {
    const confirm = useConfirm()
    const promise = confirm.open({ message: 'Test?' })
    confirm.confirm()
    const result = await promise
    expect(result).toBe(true)
  })
})
