/**
 * Tipos para exportação de dados
 */

export type ExportFormat = 'csv' | 'json' | 'xlsx'

export interface ExportRequest {
  format: ExportFormat
  date_from: string
  date_to: string
  include_sessions?: boolean
  include_metrics?: boolean
  include_technologies?: boolean
}

export interface ExportJob {
  id: string
  status: 'pending' | 'processing' | 'completed' | 'failed'
  format: ExportFormat
  created_at: string
  completed_at?: string | null
  download_url?: string | null
  error_message?: string | null
}

export interface ExportPreset {
  id: string
  name: string
  format: ExportFormat
  date_range: 'last_7_days' | 'last_30_days' | 'last_90_days' | 'custom'
  custom_from?: string
  custom_to?: string
}
