import { useClipboard } from '../useClipboard'

describe('useClipboard', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })
  afterEach(() => {
    vi.useRealTimers()
  })

  it('initializes with copied false and no error', () => {
    const { copied, error } = useClipboard()
    expect(copied.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('copy sets copied to true on success', async () => {
    vi.stubGlobal('navigator', {
      clipboard: { writeText: vi.fn().mockResolvedValue(undefined) },
    })

    const { copy, copied } = useClipboard()
    const result = await copy('hello')

    expect(result).toBe(true)
    expect(copied.value).toBe(true)
  })

  it('copy sets error on failure', async () => {
    vi.stubGlobal('navigator', {
      clipboard: { writeText: vi.fn().mockRejectedValue(new Error('Permission denied')) },
    })

    const { copy, error, copied } = useClipboard()
    const result = await copy('hello')

    expect(result).toBe(false)
    expect(error.value).toBe('Permission denied')
    expect(copied.value).toBe(false)
  })

  it('copied resets after 2 seconds', async () => {
    vi.stubGlobal('navigator', {
      clipboard: { writeText: vi.fn().mockResolvedValue(undefined) },
    })

    const { copy, copied } = useClipboard()
    await copy('hello')
    expect(copied.value).toBe(true)

    vi.advanceTimersByTime(2000)
    expect(copied.value).toBe(false)
  })

  it('copy resets error state', async () => {
    vi.stubGlobal('navigator', {
      clipboard: {
        writeText: vi.fn()
          .mockRejectedValueOnce(new Error('Fail'))
          .mockResolvedValueOnce(undefined),
      },
    })

    const { copy, error, copied } = useClipboard()

    await copy('hello')
    expect(error.value).toBe('Fail')

    await copy('hello again')
    expect(error.value).toBeNull()
    expect(copied.value).toBe(true)
  })
})
