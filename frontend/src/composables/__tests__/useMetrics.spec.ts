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

  it('userMetrics reflects analytics store', () => {
    const analyticsStore = useAnalyticsStore()
    const metrics = useMetrics()

    expect(metrics.userMetrics.value).toBeNull()

    analyticsStore.userMetrics = { total_sessions: 10, total_minutes: 600 }
    expect(metrics.userMetrics.value).toEqual({ total_sessions: 10, total_minutes: 600 })
  })

  it('technologyMetrics reflects analytics store', () => {
    const analyticsStore = useAnalyticsStore()
    const metrics = useMetrics()

    analyticsStore.technologyMetrics = [{ name: 'Vue.js', minutes: 300 }]
    expect(metrics.technologyMetrics.value).toHaveLength(1)
  })

  it('isLoading reflects analytics store loading state', () => {
    const analyticsStore = useAnalyticsStore()
    const metrics = useMetrics()

    expect(metrics.isLoading.value).toBe(false)

    analyticsStore.isLoading = true
    expect(metrics.isLoading.value).toBe(true)
  })

  it('refreshDashboard calls fetchDashboard on analytics store', async () => {
    const analyticsStore = useAnalyticsStore()
    analyticsStore.fetchDashboard = vi.fn().mockResolvedValue(undefined)
    const metrics = useMetrics()

    await metrics.refreshDashboard()

    expect(analyticsStore.fetchDashboard).toHaveBeenCalledOnce()
  })
})
