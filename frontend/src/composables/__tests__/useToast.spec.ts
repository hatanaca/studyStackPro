import { useToast } from '../useToast'

describe('useToast', () => {
  it('returns expected API', () => {
    const toast = useToast()
    expect(typeof toast.success).toBe('function')
    expect(typeof toast.error).toBe('function')
    expect(typeof toast.info).toBe('function')
    expect(typeof toast.warning).toBe('function')
    expect(toast.toasts).toBeDefined()
  })

  it('success adds toast to list', () => {
    const toast = useToast()
    toast.success('Operation completed')
    expect(toast.toasts.value.length).toBeGreaterThan(0)
  })

  it('error adds toast to list', () => {
    const toast = useToast()
    toast.error('Something went wrong')
    expect(toast.toasts.value.length).toBeGreaterThan(0)
  })

  it('info adds toast to list', () => {
    const toast = useToast()
    toast.info('For your information')
    expect(toast.toasts.value.length).toBeGreaterThan(0)
  })

  it('warning adds toast to list', () => {
    const toast = useToast()
    toast.warning('Be careful')
    expect(toast.toasts.value.length).toBeGreaterThan(0)
  })

  it('remove removes toast by id', () => {
    const toast = useToast()
    toast.success('Test')
    const id = toast.toasts.value[0]?.id
    if (id) {
      toast.remove(id)
      expect(toast.toasts.value.find((t) => t.id === id)).toBeUndefined()
    }
  })
})
