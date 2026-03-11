/** Resposta padrão da API: success, data, message opcional, meta (paginação) */
export interface ApiResponse<T> {
  success: boolean
  data: T
  message?: string
  meta?: PaginationMeta
}

/** Metadados de paginação */
export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

/** Resposta de erro da API */
export interface ApiErrorResponse {
  success: false
  error: {
    code: string
    message: string
    details?: Record<string, string[]>
  }
}

/** Filtros da lista de sessões (query params + v-model do SessionFilters). */
export interface SessionListFilters {
  technology_id?: string
  date_from?: string
  date_to?: string
  min_duration?: number
  mood?: number
}
