/**
 * Constantes de endpoints da API.
 * Base URL: /api/v1 (definida no client.ts).
 * Estrutura organizada por domínio (auth, sessions, technologies, analytics).
 */
export const ENDPOINTS = {
  auth: {
    login: '/auth/login',
    register: '/auth/register',
    logout: '/auth/logout',
    oauthComplete: '/auth/oauth-complete',
    me: '/auth/me',
    updateProfile: '/auth/me',
    changePassword: '/auth/change-password',
    tokens: '/auth/tokens',
    revokeTokens: '/auth/tokens',
    oauthRedirect: (provider: string) => `/auth/${provider}`,
    oauthCallback: (provider: string) => `/auth/${provider}/callback`,
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
    export: '/analytics/export',
  },
  youtube: {
    search: '/youtube/search',
    videos: '/youtube/videos',
    playlists: '/youtube/playlists',
  },
  linkedin: {
    status: '/linkedin/status',
    share: '/linkedin/share',
    disconnect: '/linkedin/disconnect',
  },
  code: {
    execute: '/code/execute',
    languages: '/code/languages',
  },
  exercises: {
    templates: '/exercises/templates',
    template: (id: string) => `/exercises/templates/${id}`,
    generateVariant: (id: string) => `/exercises/templates/${id}/generate`,
    grade: '/exercises/grade',
    solve: '/exercises/solve',
    attempts: '/exercises/attempts',
    stats: '/exercises/stats',
  },
  flashcards: {
    decks: '/flashcard-decks',
    deck: (id: string) => `/flashcard-decks/${id}`,
    cards: (deckId: string) => `/flashcard-decks/${deckId}/cards`,
    card: (id: string) => `/flashcards/${id}`,
    due: '/flashcards/due',
    review: (id: string) => `/flashcards/${id}/review`,
  },
  goals: {
    list: '/goals',
    one: (id: string) => `/goals/${id}`,
  },
  canvas: {
    list: '/canvas',
    one: (id: string) => `/canvas/${id}`,
  },
  studyPaths: {
    list: '/study-paths',
    one: (id: string) => `/study-paths/${id}`,
    byTechnology: (technologyId: string) => `/study-paths/technology/${technologyId}`,
  },
  notifications: {
    list: '/notifications',
    one: (id: string) => `/notifications/${id}`,
    markRead: (id: string) => `/notifications/${id}/read`,
    markAllRead: '/notifications/read-all',
    unreadCount: '/notifications/unread-count',
  },
} as const
