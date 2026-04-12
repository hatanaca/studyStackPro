/** Usuário autenticado (resposta de auth/me, registro, etc.) */
export interface User {
  id: string
  name: string
  email: string
  timezone: string
  avatar_url?: string | null
  created_at?: string
  updated_at?: string
}

/** Tecnologia (linguagem, ferramenta). Vinculada ao usuário. */
export interface Technology {
  id: string
  name: string
  slug: string
  color: string
  icon?: string
  description?: string
  is_active: boolean
  created_at?: string
  updated_at?: string
}

/** Sessão de estudo. ended_at null = sessão ativa. */
export interface StudySession {
  id: string
  user_id: string
  technology_id: string
  technology?: Technology
  started_at: string
  ended_at: string | null
  duration_min: number | null
  duration_formatted?: string | null
  notes: string | null
  mood: number | null
  focus_score?: number | null
  created_at: string
}

/** Métricas gerais do usuário (usado no dashboard) */
export interface UserMetrics {
  total_sessions: number
  total_minutes: number
  total_hours: number
  avg_session_min?: number
  longest_session_min?: number
  current_streak_days?: number
  max_streak_days?: number
  last_session_at?: string | null
}

/** Payload completo do dashboard (user_metrics, tech metrics, séries, top techs) */
export interface DashboardData {
  user_metrics: UserMetrics
  technology_metrics: TechnologyMetric[]
  time_series_30d: DailyMinute[]
  top_technologies: TechnologyMetric[]
}

/** Métricas por tecnologia (minutos, sessões, % do total) */
export interface TechnologyMetric {
  technology: Technology | null
  total_minutes: number
  total_hours?: number
  session_count: number
  percentage_total?: number
  last_studied_at?: string | null
}

/** Minutos e sessões por dia (time series, heatmap) */
export interface DailyMinute {
  date: string
  total_minutes: number
  session_count?: number
}
