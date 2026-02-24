import { apiClient } from '@/api/client'
import type { ApiResponse } from '@/types/api.types'
import type { User } from '@/types/domain.types'

export const authApi = {
  login: (email: string, password: string) =>
    apiClient.post<ApiResponse<{ user: User; token: string }>>('/auth/login', { email, password }),
  register: (data: { name: string; email: string; password: string; password_confirmation: string; timezone?: string }) =>
    apiClient.post<ApiResponse<User>>('/auth/register', data),
  logout: () => apiClient.post('/auth/logout'),
  me: () => apiClient.get<ApiResponse<User>>('/auth/me')
}
