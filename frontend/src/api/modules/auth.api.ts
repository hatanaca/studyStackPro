import { apiClient } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { ApiResponse } from '@/types/api.types'
import type { User } from '@/types/domain.types'

/** Informações de um token de acesso (listagem de dispositivos) */
export interface TokenInfo {
  id: string
  name: string
  created_at: string
  last_used_at: string | null
}

/** Módulo de chamadas à API de autenticação */
export const authApi = {
  login: (email: string, password: string) =>
    apiClient.post<ApiResponse<{ user: User; token: string }>>(ENDPOINTS.auth.login, { email, password }),
  /** Registro com dados validados. API retorna user (token no header) */
  register: (data: { name: string; email: string; password: string; password_confirmation: string; timezone?: string }) =>
    apiClient.post<ApiResponse<User>>(ENDPOINTS.auth.register, data),
  logout: () => apiClient.post(ENDPOINTS.auth.logout),
  me: () => apiClient.get<ApiResponse<User>>(ENDPOINTS.auth.me),
  updateProfile: (data: { name?: string; timezone?: string }) =>
    apiClient.put<ApiResponse<User>>(ENDPOINTS.auth.updateProfile, data),
  changePassword: (data: { current_password: string; password: string; password_confirmation: string }) =>
    apiClient.post<ApiResponse<void>>(ENDPOINTS.auth.changePassword, data),
  getTokens: () => apiClient.get<ApiResponse<TokenInfo[]>>(ENDPOINTS.auth.tokens),
  revokeAllTokens: () => apiClient.delete<ApiResponse<{ revoked_count: number }>>(ENDPOINTS.auth.revokeTokens),
}
