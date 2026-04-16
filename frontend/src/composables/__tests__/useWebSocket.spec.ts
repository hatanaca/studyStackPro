import { setActivePinia, createPinia } from 'pinia'
import { useWebSocket } from '../useWebSocket'

vi.mock('laravel-echo', () => ({
  default: vi.fn().mockImplementation(() => ({
    connector: {
      pusher: {
        connection: {
          bind: vi.fn(),
        },
      },
    },
    private: vi.fn().mockReturnValue({
      listen: vi.fn().mockReturnThis(),
    }),
    disconnect: vi.fn(),
  })),
}))

vi.mock('pusher-js', () => ({
  default: vi.fn(),
}))

vi.stubGlobal('window', {
  Pusher: vi.fn(),
})

describe('useWebSocket', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('retorna connect, disconnect e isConnected', () => {
    const { connect, disconnect, isConnected } = useWebSocket()

    expect(typeof connect).toBe('function')
    expect(typeof disconnect).toBe('function')
    expect(isConnected).toBeDefined()
    expect(isConnected.value).toBe(false)
  })

  it('connect com userId não lança erro', () => {
    const { connect } = useWebSocket()
    expect(() => connect('user-123')).not.toThrow()
  })

  it('disconnect não lança erro', () => {
    const { disconnect } = useWebSocket()
    expect(() => disconnect()).not.toThrow()
  })
})
