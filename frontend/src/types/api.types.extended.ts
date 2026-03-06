/**
 * Tipos estendidos para respostas e erros da API
 */

export interface ApiErrorDetail {
  message: string
  field?: string
  code?: string
}

export interface ApiErrorResponse {
  success: false
  error?: {
    message: string
    details?: Record<string, string[]>
    code?: string
  }
}

export interface ApiSuccessResponse<T> {
  success: true
  data: T
  message?: string
}

export type ApiResponse<T> = ApiSuccessResponse<T> | ApiErrorResponse

export function isApiErrorResponse(r: unknown): r is ApiErrorResponse {
  return typeof r === 'object' && r !== null && 'success' in r && (r as ApiErrorResponse).success === false
}

export function getApiErrorMessage(r: ApiErrorResponse): string {
  return r.error?.message ?? 'Erro desconhecido'
}

export function getApiFieldErrors(r: ApiErrorResponse): Record<string, string> {
  const details = r.error?.details
  if (!details || typeof details !== 'object') return {}
  return Object.fromEntries(
    Object.entries(details).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)])
  )
}
