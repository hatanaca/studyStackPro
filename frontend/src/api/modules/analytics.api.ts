import { apiClient } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { ApiResponse } from '@/types/api.types'
import type { DashboardData, TechnologyMetric } from '@/types/domain.types'

export const analyticsApi = {
  getDashboard: () => apiClient.get<ApiResponse<DashboardData>>(ENDPOINTS.analytics.dashboard),
  getUserMetrics: () => apiClient.get<ApiResponse<Record<string, unknown>>>(ENDPOINTS.analytics.userMetrics),
  getTechStats: () => apiClient.get<ApiResponse<TechnologyMetric[]>>(ENDPOINTS.analytics.techStats),
  getTimeSeries: (days = 30) =>
    apiClient.get<ApiResponse<{ date: string; total_minutes: number }[]>>(
      ENDPOINTS.analytics.timeSeries,
      { params: { days } }
    ),
  getWeekly: () =>
    apiClient.get<ApiResponse<{ week_start: string; total_minutes: number; session_count: number }[]>>(
      ENDPOINTS.analytics.weekly
    ),
  getHeatmap: (year?: number) =>
    apiClient.get<ApiResponse<{ date: string; total_minutes: number }[]>>(
      ENDPOINTS.analytics.heatmap,
      { params: year ? { year } : {} }
    ),
  recalculate: () =>
    apiClient.post<ApiResponse<{ job_id: string }>>(ENDPOINTS.analytics.recalculate),

  getExport: (params: { start: string; end: string }) =>
    apiClient.get<
      ApiResponse<{
        exported_at: string
        period: { start: string; end: string }
        data: Array<{ date: string; total_minutes: number; session_count: number }>
      }>
    >(ENDPOINTS.analytics.export, { params }),
}
