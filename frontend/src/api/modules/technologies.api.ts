import { apiClient } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { ApiResponse } from '@/types/api.types'
import type { Technology } from '@/types/domain.types'

export const technologiesApi = {
  list: (params?: { active_only?: boolean }) =>
    apiClient.get<ApiResponse<Technology[]>>(ENDPOINTS.technologies.list, { params }),
  search: (q: string, limit?: number) =>
    apiClient.get<ApiResponse<Technology[]>>(ENDPOINTS.technologies.search, {
      params: { q, limit }
    }),
  getOne: (id: string) =>
    apiClient.get<ApiResponse<Technology>>(ENDPOINTS.technologies.one(id)),
  create: (data: { name: string; color?: string; icon?: string; description?: string }) =>
    apiClient.post<ApiResponse<Technology>>(ENDPOINTS.technologies.list, data),
  update: (id: string, data: Partial<{ name: string; color: string; icon: string; description: string }>) =>
    apiClient.put<ApiResponse<Technology>>(ENDPOINTS.technologies.one(id), data),
  delete: (id: string) =>
    apiClient.delete<ApiResponse<null>>(ENDPOINTS.technologies.one(id)),
}
