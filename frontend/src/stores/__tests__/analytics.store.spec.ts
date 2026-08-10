import { setActivePinia, createPinia } from 'pinia'
import { useAnalyticsStore } from '../analytics.store'

describe('analytics.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('setDashboard preenche dashboard e lastFetchAt', () => {
    const mockData = {
      user_metrics: { total_sessions: 10, total_minutes: 120 },
      technology_metrics: [],
      time_series_30d: [],
      top_technologies: [],
    }

    const store = useAnalyticsStore()
    store.setDashboard(mockData)

    expect(store.dashboard).toEqual(mockData)
    expect(store.lastFetchAt).toBeInstanceOf(Date)
  })

  it('setDashboard atualiza time series de 30d', () => {
    const mockData = {
      user_metrics: {},
      technology_metrics: [],
      time_series_30d: [{ date: '2025-03-15', total_minutes: 30, session_count: 1 }],
      top_technologies: [],
    }

    const store = useAnalyticsStore()
    store.setDashboard(mockData)

    expect(store.timeSeriesData['30d']).toEqual(mockData.time_series_30d)
  })

  it('updateFromWebSocket substitui dashboard', () => {
    const store = useAnalyticsStore()
    const newData = {
      user_metrics: { total_sessions: 5 },
      technology_metrics: [],
      time_series_30d: [],
      top_technologies: [],
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

  it('reconcilePending clears pending sessions when API total matches expected', async () => {
    const store = useAnalyticsStore()

    store.setDashboard({
      user_metrics: { total_sessions: 5, total_minutes: 100, total_hours: 1.67 },
      technology_metrics: [],
      time_series_30d: [],
      top_technologies: [],
    })

    store.addLocalTodaySession('2025-03-15', 30, {
      id: 't1',
      name: 'Vue',
      color: '#42b883',
    })

    expect(store.pendingSessions).toHaveLength(1)
    expect(store.sessionCountAtPendingStart).toBe(5)

    // API confirma sessão: total vai de 5 para 6 → pending deve ser limpo.
    store.setDashboard({
      user_metrics: { total_sessions: 6, total_minutes: 130, total_hours: 2.17 },
      technology_metrics: [],
      time_series_30d: [],
      top_technologies: [],
    })

    expect(store.pendingSessions).toHaveLength(0)
    expect(store.sessionCountAtPendingStart).toBeNull()
  })

  it('addLocalTodaySession adds to pending and records session count', () => {
    const store = useAnalyticsStore()

    store.setDashboard({
      user_metrics: { total_sessions: 10, total_minutes: 200, total_hours: 3.33 },
      technology_metrics: [],
      time_series_30d: [],
      top_technologies: [],
    })

    store.addLocalTodaySession('2025-03-15', 25, {
      id: 't1',
      name: 'TypeScript',
      color: '#3178c6',
    })

    expect(store.pendingSessions).toHaveLength(1)
    expect(store.pendingSessions[0].minutes).toBe(25)
    expect(store.pendingSessions[0].technology?.name).toBe('TypeScript')
    expect(store.sessionCountAtPendingStart).toBe(10)

    store.addLocalTodaySession('2025-03-15', 15, undefined)

    expect(store.pendingSessions).toHaveLength(2)
    expect(store.sessionCountAtPendingStart).toBe(10)
  })

  it('optional chaining handles undefined user_metrics safely', () => {
    const store = useAnalyticsStore()

    store.setDashboard({
      user_metrics: undefined as never,
      technology_metrics: [],
      time_series_30d: [],
      top_technologies: [],
    })

    expect(store.userMetrics).toBeNull()
    expect(() => store.addLocalTodaySession('2025-03-15', 10)).not.toThrow()
    expect(store.pendingSessions).toHaveLength(1)
  })
})
