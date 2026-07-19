import { apiClient } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { ApiResponse } from '@/types/api.types'
import type {
  CodeExecutionResult,
  LanguageConfig,
} from '@/features/code-terminal/types/code-terminal.types'

export const codeExecutionApi = {
  execute: (data: { code: string; language: string }) =>
    apiClient.post<ApiResponse<CodeExecutionResult & { executor: string }>>(
      ENDPOINTS.code.execute,
      data
    ),

  languages: () => apiClient.get<ApiResponse<LanguageConfig[]>>(ENDPOINTS.code.languages),
}
