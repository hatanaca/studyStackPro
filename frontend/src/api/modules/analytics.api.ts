import { apiClient } from '@/api/client'
import type { ApiResponse } from '@/types/api.types'
import type { DashboardData } from '@/types/domain.types'

export const analyticsApi = {
  getDashboard: () => apiClient.get<ApiResponse<DashboardData>>('/analytics/dashboard'),
  getUserMetrics: () => apiClient.get<ApiResponse<Record<string, unknown>>>('/analytics/user-metrics')
}
