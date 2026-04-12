export const sessionsRoutes = [
  {
    path: 'session',
    name: 'session-focus',
    component: () => import('@/views/sessions/SessionFocusView.vue'),
    meta: { title: 'Sessão ativa' },
  },
  /** Rota estática antes de `sessions/:id` para não capturar o segmento como id. */
  {
    path: 'sessions',
    name: 'sessions',
    component: () => import('@/views/sessions/SessionsView.vue'),
    meta: { title: 'Sessões' },
  },
  {
    path: 'sessions/:id',
    name: 'session-detail',
    component: () => import('@/views/sessions/SessionDetailView.vue'),
    meta: { title: 'Sessão de estudo' },
  },
]
