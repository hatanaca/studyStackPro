import type { DashboardData } from './domain.types'

export interface MetricsUpdatedEvent {
  dashboard: DashboardData
}

export interface MetricsRecalculatingEvent {
  status: 'recalculating'
}

/** Payload do evento session.started (Reverb). technology.slug enviado pelo backend. */
export interface SessionStartedEvent {
  session: {
    id: string
    technology?: { id: string; name: string; slug: string; color: string }
    started_at: string
    elapsed_seconds?: number
  }
}

export interface SessionEndedEvent {
  session: {
    id: string
    duration_min: number | null
    duration_formatted: string | null
  }
}
