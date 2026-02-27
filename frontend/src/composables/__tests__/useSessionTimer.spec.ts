import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useSessionTimer } from '@/features/sessions/composables/useSessionTimer'
import { sessionsApi } from '@/api/modules/sessions.api'

vi.mock('@/api/modules/sessions.api', () => ({
  sessionsApi: {
    getActive: vi.fn()
  }
}))

vi.useFakeTimers()

describe('useSessionTimer', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('retorna activeSession, elapsedSeconds, formattedTime e refresh', () => {
    const { activeSession, elapsedSeconds, formattedTime, refresh } = useSessionTimer()

    expect(activeSession).toBeDefined()
    expect(elapsedSeconds).toBeDefined()
    expect(formattedTime).toBeDefined()
    expect(typeof refresh).toBe('function')
  })

  it('refresh com elapsed_seconds do servidor preenche o store', async () => {
    vi.mocked(sessionsApi.getActive).mockResolvedValue({
      data: {
        success: true,
        data: {
          id: '1',
          elapsed_seconds: 120,
          technology: {}
        }
      }
    } as never)

    const { refresh } = useSessionTimer()
    await refresh()

    const { useSessionsStore } = await import('@/stores/sessions.store')
    const store = useSessionsStore()
    expect(store.elapsedSeconds).toBe(120)
    expect(store.activeSession).toBeTruthy()
  })
})
