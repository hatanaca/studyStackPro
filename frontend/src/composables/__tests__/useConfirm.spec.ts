import { useConfirm } from '../useConfirm'

describe('useConfirm', () => {
  it('returns expected API', () => {
    const confirm = useConfirm()
    expect(typeof confirm.confirm).toBe('function')
    expect(confirm.isVisible).toBeDefined()
    expect(confirm.message).toBeDefined()
    expect(confirm.title).toBeDefined()
  })

  it('confirm resolves to false when cancelled', async () => {
    const confirm = useConfirm()
    const result = await confirm.confirm({ message: 'Test?' })
    expect(result).toBe(false)
  })

  it('isVisible is false initially', () => {
    const confirm = useConfirm()
    expect(confirm.isVisible.value).toBe(false)
  })
})
