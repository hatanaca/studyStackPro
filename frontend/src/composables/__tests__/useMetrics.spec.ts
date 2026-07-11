import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useMetrics } from '../useMetrics'
import { useAnalyticsStore } from '@/stores/analytics.store'

vi.mock('@/api/modules/analytics.api', () => ({
  analyticsApi: {
    getDashboard: vi.fn(),
    getUserMetrics: vi.fn(),
    getTechStats: vi.fn(),
    getTimeSeries: vi.fn(),
    getHeatmap: vi.fn(),
  },
}))

describe('useMetrics', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('returns expected API', () => {
    const metrics = useMetrics()
    expect(metrics.userMetrics).toBeDefined()
    expect(metrics.technologyMetrics).toBeDefined()
    expect(metrics.timeSeries30d).toBeDefined()
    expect(metrics.isLoading).toBeDefined()
    expect(typeof metrics.refreshDashboard).toBe('function')
  })

  it('userMetrics is null initially', () => {
    const metrics = useMetrics()
    expect(metrics.userMetrics.value).toBeNull()
  })

  it('userMetrics reflects analytics store data', () => {
    const analyticsStore = useAnalyticsStore()
    analyticsStore.dashboard = {
      user_metrics: { total_sessions: 10, total_minutes: 600, total_hours: 10, ...{} as any },
      technology_metrics: [],
      time_series_30d: [],
      weekly_comparison: [],
      heatmap: [],
      top_technologies: [],
      today_minutes: 0,
      today_sessions: 0,
      today_technologies: [],
    } as any
    const metrics = useMetrics()
    expect(metrics.userMetrics.value).toEqual(
      expect.objectContaining({ total_sessions: 10, total_minutes: 600 })
    )
  })

  it('technologyMetrics reflects analytics store data', () => {
    const analyticsStore = useAnalyticsStore()
    analyticsStore.dashboard = {
      user_metrics: null,
      technology_metrics: [{ name: 'Vue.js', minutes: 300 } as any],
      time_series_30d: [],
      weekly_comparison: [],
      heatmap: [],
      top_technologies: [],
      today_minutes: 0,
      today_sessions: 0,
      today_technologies: [],
    } as any
    const metrics = useMetrics()
    expect(metrics.technologyMetrics.value).toHaveLength(1)
  })

  it('isLoading reflects analytics store loading state', () => {
    const metrics = useMetrics()
    expect(metrics.isLoading.value).toBe(false)
  })

  it('refreshDashboard calls fetchDashboard on analytics store', async () => {
    const analyticsStore = useAnalyticsStore()
    analyticsStore.fetchDashboard = vi.fn().mockResolvedValue(undefined)
    const metrics = useMetrics()

    await metrics.refreshDashboard()

    expect(analyticsStore.fetchDashboard).toHaveBeenCalledOnce()
  })
})
