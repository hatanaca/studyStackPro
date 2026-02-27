import { apiClient } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { ApiResponse } from '@/types/api.types'
import type { StudySession } from '@/types/domain.types'

export interface ActiveSessionResponse extends StudySession {
  elapsed_seconds: number
}

export const sessionsApi = {
  list: (params?: {
    page?: number
    per_page?: number
    technology_id?: string
    date_from?: string
    date_to?: string
    min_duration?: number
    mood?: number
    status?: string
  }) => apiClient.get<ApiResponse<StudySession[]>>(ENDPOINTS.sessions.list, { params }),
  getOne: (id: string) =>
    apiClient.get<ApiResponse<StudySession>>(ENDPOINTS.sessions.one(id)),
  getActive: () =>
    apiClient.get<ApiResponse<ActiveSessionResponse | null>>(ENDPOINTS.sessions.active),
  start: (technology_id?: string) =>
    apiClient.post<ApiResponse<StudySession>>(ENDPOINTS.sessions.start, {
      technology_id: technology_id || undefined
    }),
  end: (id: string) =>
    apiClient.patch<ApiResponse<StudySession>>(ENDPOINTS.sessions.end(id)),
  create: (data: Partial<StudySession> & { technology_id: string; started_at: string }) =>
    apiClient.post<ApiResponse<StudySession>>(ENDPOINTS.sessions.create, data),
  update: (id: string, data: Partial<StudySession>) =>
    apiClient.patch<ApiResponse<StudySession>>(ENDPOINTS.sessions.one(id), data),
  delete: (id: string) =>
    apiClient.delete<ApiResponse<null>>(ENDPOINTS.sessions.one(id)),
}
