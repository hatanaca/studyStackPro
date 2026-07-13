import { setActivePinia, createPinia } from 'pinia'
import { useSessionTimer } from '@/features/sessions/composables/useSessionTimer'
import { useSessionsStore } from '@/stores/sessions.store'

vi.mock('@/api/modules/sessions.api', () => ({
  sessionsApi: {
    list: vi.fn(),
    getActive: vi.fn().mockResolvedValue({ data: { success: true, data: null } }),
  },
}))

describe('useSessionTimer', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('returns expected API', () => {
    const timer = useSessionTimer()
    expect(timer.activeSession).toBeDefined()
    expect(timer.elapsedSeconds).toBeDefined()
    expect(timer.formattedTime).toBeDefined()
    expect(typeof timer.fetchActive).toBe('function')
    expect(typeof timer.refresh).toBe('function')
  })

  it('formattedTime returns 00:00:00 initially', () => {
    const timer = useSessionTimer()
    expect(timer.formattedTime.value).toBe('00:00:00')
  })

  it('elapsedSeconds is 0 initially', () => {
    const timer = useSessionTimer()
    expect(timer.elapsedSeconds.value).toBe(0)
  })

  it('activeSession is null initially', () => {
    const timer = useSessionTimer()
    expect(timer.activeSession.value).toBeNull()
  })

  it('elapsedSeconds reflects store state', () => {
    const store = useSessionsStore()
    const timer = useSessionTimer()

    store.setElapsedSeconds(120)
    expect(timer.elapsedSeconds.value).toBe(120)
  })

  it('formattedTime reflects store formatted timer', () => {
    const store = useSessionsStore()
    const timer = useSessionTimer()

    store.setElapsedSeconds(3661)
    expect(timer.formattedTime.value).toBe('01:01:01')
  })

  it('activeSession reflects store active session', () => {
    const store = useSessionsStore()
    const timer = useSessionTimer()

    const mockSession = { id: 's1', started_at: '2026-01-01T00:00:00Z' }
    store.setActiveSession(mockSession as never)
    expect(timer.activeSession.value).toEqual(mockSession)
  })

  it('refresh is an alias for fetchActive', () => {
    const timer = useSessionTimer()
    expect(timer.refresh).toBe(timer.fetchActive)
  })
})
