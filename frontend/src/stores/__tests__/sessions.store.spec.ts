import { setActivePinia, createPinia } from 'pinia'
import { useSessionsStore } from '../sessions.store'
import { sessionsApi } from '@/api/modules/sessions.api'

vi.mock('@/api/modules/sessions.api', () => ({
  sessionsApi: {
    list: vi.fn(),
    getActive: vi.fn(),
  },
}))

const mockSessions = [
  { id: 's1', technology_id: 't1', started_at: '2026-01-01T10:00:00Z' },
  { id: 's2', technology_id: 't2', started_at: '2026-01-02T10:00:00Z' },
]

const mockActiveSession = {
  id: 's-active',
  technology_id: 't1',
  started_at: '2026-04-06T08:00:00Z',
  elapsed_seconds: 3661,
}

describe('sessions.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchSessions stores sessions from API', async () => {
    vi.mocked(sessionsApi.list).mockResolvedValue({
      data: { success: true, data: mockSessions, meta: { total: 2 } },
    } as never)

    const store = useSessionsStore()
    await store.fetchSessions()

    expect(store.sessions).toEqual(mockSessions)
    expect(sessionsApi.list).toHaveBeenCalledOnce()
  })

  it('fetchSessions sets total from meta', async () => {
    vi.mocked(sessionsApi.list).mockResolvedValue({
      data: { success: true, data: mockSessions, meta: { total: 42 } },
    } as never)

    const store = useSessionsStore()
    await store.fetchSessions()

    expect(store.total).toBe(42)
  })

  it('fetchSessions sets isLoading to false after completion', async () => {
    vi.mocked(sessionsApi.list).mockResolvedValue({
      data: { success: true, data: [], meta: { total: 0 } },
    } as never)

    const store = useSessionsStore()
    await store.fetchSessions()

    expect(store.isLoading).toBe(false)
  })

  it('fetchSessions sets isLoading to false even on error', async () => {
    vi.mocked(sessionsApi.list).mockRejectedValue(new Error('Network error'))

    const store = useSessionsStore()
    await expect(store.fetchSessions()).rejects.toThrow('Network error')

    expect(store.isLoading).toBe(false)
  })

  it('fetchActiveSession stores active session and elapsed seconds', async () => {
    vi.mocked(sessionsApi.getActive).mockResolvedValue({
      data: { success: true, data: mockActiveSession },
    } as never)

    const store = useSessionsStore()
    const result = await store.fetchActiveSession()

    expect(store.activeSession).toEqual(mockActiveSession)
    expect(store.elapsedSeconds).toBe(3661)
    expect(result).toEqual(mockActiveSession)
  })

  it('fetchActiveSession clears when no active session', async () => {
    vi.mocked(sessionsApi.getActive).mockResolvedValue({
      data: { success: true, data: null },
    } as never)

    const store = useSessionsStore()
    store.activeSession = mockActiveSession as never
    store.elapsedSeconds = 999

    const result = await store.fetchActiveSession()

    expect(store.activeSession).toBeNull()
    expect(store.elapsedSeconds).toBe(0)
    expect(result).toBeNull()
  })

  it('setActiveSession updates activeSession', () => {
    const store = useSessionsStore()
    store.setActiveSession(mockActiveSession as never)

    expect(store.activeSession).toEqual(mockActiveSession)
  })

  it('clearActiveSession resets state', () => {
    const store = useSessionsStore()
    store.activeSession = mockActiveSession as never
    store.elapsedSeconds = 500

    store.clearActiveSession()

    expect(store.activeSession).toBeNull()
    expect(store.elapsedSeconds).toBe(0)
  })

  it('hasActiveSession returns true when session exists', () => {
    const store = useSessionsStore()
    store.setActiveSession(mockActiveSession as never)

    expect(store.hasActiveSession).toBe(true)
  })

  it('hasActiveSession returns false when no session', () => {
    const store = useSessionsStore()

    expect(store.hasActiveSession).toBe(false)
  })

  it('formattedTimer formats seconds correctly', () => {
    const store = useSessionsStore()
    store.setElapsedSeconds(3661)

    expect(store.formattedTimer).toBe('01:01:01')
  })

  it('formattedTimer formats zero correctly', () => {
    const store = useSessionsStore()

    expect(store.formattedTimer).toBe('00:00:00')
  })

  it('setElapsedSeconds updates value', () => {
    const store = useSessionsStore()
    store.setElapsedSeconds(120)

    expect(store.elapsedSeconds).toBe(120)
  })
})
