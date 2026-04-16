import { apiClient } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { ApiResponse, SessionListFilters } from '@/types/api.types'
import type { StudySession } from '@/types/domain.types'

/** Sessão ativa com elapsed_seconds (timer em tempo real) */
export interface ActiveSessionResponse extends StudySession {
  elapsed_seconds: number
}

export interface SessionsListParams extends SessionListFilters {
  page?: number
  per_page?: number
  status?: string
}

/** Módulo de chamadas à API de sessões de estudo */
export const sessionsApi = {
  /** Lista sessões com filtros e paginação */
  list: (params?: SessionsListParams) =>
    apiClient.get<ApiResponse<StudySession[]>>(ENDPOINTS.sessions.list, { params }),
  getOne: (id: string) => apiClient.get<ApiResponse<StudySession>>(ENDPOINTS.sessions.one(id)),
  /** Sessão ativa (timer) com elapsed_seconds */
  getActive: () =>
    apiClient.get<ApiResponse<ActiveSessionResponse | null>>(ENDPOINTS.sessions.active),
  start: (technology_id?: string) =>
    apiClient.post<ApiResponse<StudySession>>(ENDPOINTS.sessions.start, {
      technology_id: technology_id || undefined,
    }),
  end: (id: string) => apiClient.patch<ApiResponse<StudySession>>(ENDPOINTS.sessions.end(id)),
  create: (data: Partial<StudySession> & { technology_id: string; started_at: string }) =>
    apiClient.post<ApiResponse<StudySession>>(ENDPOINTS.sessions.create, data),
  update: (id: string, data: Partial<StudySession>) =>
    apiClient.patch<ApiResponse<StudySession>>(ENDPOINTS.sessions.one(id), data),
  delete: (id: string) => apiClient.delete<ApiResponse<null>>(ENDPOINTS.sessions.one(id)),
}
