/**
 * Chaves de query para TanStack Query.
 * Centralizar aqui evita colisões e facilita invalidação.
 */
export const queryKeys = {
  analytics: {
    all: ['analytics'] as const,
    dashboard: () => [...queryKeys.analytics.all, 'dashboard'] as const,
    heatmap: (year?: number) => [...queryKeys.analytics.all, 'heatmap', year] as const,
    weekly: () => [...queryKeys.analytics.all, 'weekly'] as const,
    timeSeries: (days: number) => [...queryKeys.analytics.all, 'timeSeries', days] as const,
    techStats: () => [...queryKeys.analytics.all, 'techStats'] as const,
  },
  sessions: {
    all: ['sessions'] as const,
    list: (params?: Record<string, unknown>) => [...queryKeys.sessions.all, 'list', params] as const,
    active: () => [...queryKeys.sessions.all, 'active'] as const,
    detail: (id: string) => [...queryKeys.sessions.all, 'detail', id] as const,
  },
  technologies: {
    all: ['technologies'] as const,
    list: () => [...queryKeys.technologies.all, 'list'] as const,
    detail: (id: string) => [...queryKeys.technologies.all, 'detail', id] as const,
  },
} as const
