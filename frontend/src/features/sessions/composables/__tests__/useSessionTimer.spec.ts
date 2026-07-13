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

  it('refresh fetches active session from API', async () => {
    const mockSession = { id: 's1', started_at: '2026-01-01T00:00:00Z' }
    vi.mocked((await import('@/api/modules/sessions.api')).sessionsApi.getActive).mockResolvedValue({
      data: { success: true, data: mockSession },
    })

    const timer = useSessionTimer()
    await timer.refresh()

    expect(timer.activeSession.value).toEqual(mockSession)
  })

  it('refresh calls API to fetch active session', async () => {
    const { sessionsApi } = await import('@/api/modules/sessions.api')
    vi.mocked(sessionsApi.getActive).mockResolvedValue({
      data: { success: true, data: null },
    })

    const timer = useSessionTimer()
    await timer.refresh()

    expect(sessionsApi.getActive).toHaveBeenCalledOnce()
  })
})
