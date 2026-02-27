/**
 * Constantes de endpoints da API
 * Base URL: /api/v1 (definida no client.ts)
 */

export const ENDPOINTS = {
  auth: {
    login: '/auth/login',
    register: '/auth/register',
    logout: '/auth/logout',
    me: '/auth/me',
  },
  sessions: {
    list: '/study-sessions',
    active: '/study-sessions/active',
    start: '/study-sessions/start',
    one: (id: string) => `/study-sessions/${id}`,
    end: (id: string) => `/study-sessions/${id}/end`,
    create: '/study-sessions',
  },
  technologies: {
    list: '/technologies',
    search: '/technologies/search',
    one: (id: string) => `/technologies/${id}`,
  },
  analytics: {
    dashboard: '/analytics/dashboard',
    userMetrics: '/analytics/user-metrics',
    techStats: '/analytics/tech-stats',
    timeSeries: '/analytics/time-series',
    weekly: '/analytics/weekly',
    heatmap: '/analytics/heatmap',
    recalculate: '/analytics/recalculate',
  },
} as const
