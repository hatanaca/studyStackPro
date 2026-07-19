import { apiClient } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { ApiResponse } from '@/types/api.types'
import type {
  LinkedInSharePayload,
  LinkedInShareResult,
  LinkedInStatus,
} from '@/types/domain.types'

export const linkedinApi = {
  status: () => apiClient.get<ApiResponse<LinkedInStatus>>(ENDPOINTS.linkedin.status),

  share: (data: LinkedInSharePayload) =>
    apiClient.post<ApiResponse<LinkedInShareResult>>(ENDPOINTS.linkedin.share, data),

  disconnect: () => apiClient.post<ApiResponse<null>>(ENDPOINTS.linkedin.disconnect),
}
