import type { DashboardData } from './domain.types'

export interface MetricsUpdatedEvent {
  dashboard: DashboardData
}

export interface MetricsRecalculatingEvent {
  status: 'recalculating'
}

export interface SessionStartedEvent {
  session: {
    id: string
    technology: { name: string; color: string }
    started_at: string
  }
}

export interface SessionEndedEvent {
  session: {
    id: string
    duration_min: number | null
    duration_formatted: string | null
  }
}
