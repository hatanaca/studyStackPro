/**
 * Tipos para filtros reutilizáveis em listagens e relatórios
 */

export interface DateFilterValue {
  start: string
  end: string
}

export interface SelectFilterOption<T = string> {
  value: T
  label: string
  disabled?: boolean
}

export interface MultiSelectFilterState<T = string> {
  selected: T[]
  options: SelectFilterOption<T>[]
}

export interface SortConfig {
  key: string
  order: 'asc' | 'desc'
}

export interface PaginationState {
  page: number
  pageSize: number
  totalItems: number
  totalPages: number
}

export interface ListRequestParams {
  page?: number
  per_page?: number
  sort_by?: string
  sort_order?: 'asc' | 'desc'
  search?: string
  [key: string]: string | number | boolean | undefined
}

export type FilterChangeHandler = (key: string, value: unknown) => void
