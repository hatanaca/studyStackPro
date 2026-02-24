import { apiClient } from '@/api/client'
import type { ApiResponse } from '@/types/api.types'
import type { Technology } from '@/types/domain.types'

export const technologiesApi = {
  list: (params?: { active_only?: boolean }) =>
    apiClient.get<ApiResponse<Technology[]>>('/technologies', { params }),
  search: (q: string, limit?: number) =>
    apiClient.get<ApiResponse<Technology[]>>('/technologies/search', {
      params: { q, limit }
    }),
  getOne: (id: string) =>
    apiClient.get<ApiResponse<Technology>>(`/technologies/${id}`),
  create: (data: { name: string; color?: string; icon?: string; description?: string }) =>
    apiClient.post<ApiResponse<Technology>>('/technologies', data),
  update: (id: string, data: Partial<{ name: string; color: string; icon: string; description: string }>) =>
    apiClient.put<ApiResponse<Technology>>(`/technologies/${id}`, data),
  delete: (id: string) =>
    apiClient.delete<ApiResponse<null>>(`/technologies/${id}`)
}
