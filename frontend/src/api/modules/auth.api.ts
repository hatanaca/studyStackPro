import { apiClient } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { ApiResponse } from '@/types/api.types'
import type { User } from '@/types/domain.types'

export const authApi = {
  login: (email: string, password: string) =>
    apiClient.post<ApiResponse<{ user: User; token: string }>>(ENDPOINTS.auth.login, { email, password }),
  register: (data: { name: string; email: string; password: string; password_confirmation: string; timezone?: string }) =>
    apiClient.post<ApiResponse<User>>(ENDPOINTS.auth.register, data),
  logout: () => apiClient.post(ENDPOINTS.auth.logout),
  me: () => apiClient.get<ApiResponse<User>>(ENDPOINTS.auth.me),
}
