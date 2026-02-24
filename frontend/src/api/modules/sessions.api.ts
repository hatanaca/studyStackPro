import { apiClient } from '@/api/client'
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
  }) => apiClient.get<ApiResponse<StudySession[]>>('/study-sessions', { params }),
  getOne: (id: string) =>
    apiClient.get<ApiResponse<StudySession>>(`/study-sessions/${id}`),
  getActive: () =>
    apiClient.get<ApiResponse<ActiveSessionResponse | null>>('/study-sessions/active'),
  start: (technology_id?: string) =>
    apiClient.post<ApiResponse<StudySession>>('/study-sessions/start', {
      technology_id: technology_id || undefined
    }),
  end: (id: string) =>
    apiClient.patch<ApiResponse<StudySession>>(`/study-sessions/${id}/end`),
  create: (data: Partial<StudySession> & { technology_id: string; started_at: string }) =>
    apiClient.post<ApiResponse<StudySession>>('/study-sessions', data),
  update: (id: string, data: Partial<StudySession>) =>
    apiClient.put<ApiResponse<StudySession>>(`/study-sessions/${id}`, data),
  delete: (id: string) =>
    apiClient.delete<ApiResponse<null>>(`/study-sessions/${id}`)
}
