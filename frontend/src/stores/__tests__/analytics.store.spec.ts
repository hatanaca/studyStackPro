import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAnalyticsStore } from '../analytics.store'
import { apiClient } from '@/api/client'

vi.mock('@/api/client', () => ({
  apiClient: {
    get: vi.fn()
  }
}))

describe('analytics.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchDashboard preenche dashboard e lastFetchAt', async () => {
    const mockData = {
      success: true,
      data: {
        user_metrics: { total_sessions: 10, total_minutes: 120 },
        technology_metrics: [],
        time_series_30d: [],
        top_technologies: []
      }
    }
    vi.mocked(apiClient.get).mockResolvedValue({ data: mockData } as never)

    const store = useAnalyticsStore()
    await store.fetchDashboard(true)

    expect(store.dashboard).toEqual(mockData.data)
    expect(store.lastFetchAt).toBeInstanceOf(Date)
  })

  it('isFresh retorna true dentro do TTL e false fora', async () => {
    const mockData = {
      success: true,
      data: {
        user_metrics: {},
        technology_metrics: [],
        time_series_30d: [],
        top_technologies: []
      }
    }
    vi.mocked(apiClient.get).mockResolvedValue({ data: mockData } as never)

    const store = useAnalyticsStore()
    expect(store.isFresh).toBe(false)

    await store.fetchDashboard(true)
    expect(store.isFresh).toBe(true)
  })

  it('updateFromWebSocket substitui dashboard', () => {
    const store = useAnalyticsStore()
    const newData = {
      user_metrics: { total_sessions: 5 },
      technology_metrics: [],
      time_series_30d: [],
      top_technologies: []
    }

    store.updateFromWebSocket(newData as never)

    expect(store.dashboard).toEqual(newData)
    expect(store.lastFetchAt).toBeInstanceOf(Date)
    expect(store.isRecalculating).toBe(false)
  })

  it('setRecalculating altera isRecalculating', () => {
    const store = useAnalyticsStore()

    expect(store.isRecalculating).toBe(false)
    store.setRecalculating(true)
    expect(store.isRecalculating).toBe(true)
    store.setRecalculating(false)
    expect(store.isRecalculating).toBe(false)
  })
})
