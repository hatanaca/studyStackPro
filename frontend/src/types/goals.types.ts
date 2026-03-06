/**
 * Tipos para a feature de metas (goals)
 */

export type GoalType = 'minutes_per_week' | 'sessions_per_week' | 'streak_days'

export type GoalStatus = 'active' | 'completed' | 'cancelled'

export interface Goal {
  id: string
  user_id: string
  type: GoalType
  target_value: number
  current_value: number
  status: GoalStatus
  start_date: string
  end_date: string | null
  created_at: string
  updated_at: string
  /** Metadados opcionais (ex.: tecnologia associada) */
  meta?: Record<string, unknown>
}

export interface CreateGoalPayload {
  type: GoalType
  target_value: number
  start_date: string
  end_date?: string | null
  meta?: Record<string, unknown>
}

export interface UpdateGoalPayload {
  target_value?: number
  status?: GoalStatus
  end_date?: string | null
}

export const GOAL_TYPE_LABELS: Record<GoalType, string> = {
  minutes_per_week: 'Minutos por semana',
  sessions_per_week: 'Sessões por semana',
  streak_days: 'Dias de sequência (streak)',
}

export const GOAL_STATUS_LABELS: Record<GoalStatus, string> = {
  active: 'Ativa',
  completed: 'Concluída',
  cancelled: 'Cancelada',
}
