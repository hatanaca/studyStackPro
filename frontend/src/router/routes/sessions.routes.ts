export const sessionsRoutes = [
  {
    path: 'sessions',
    name: 'sessions',
    component: () => import('@/views/sessions/SessionsView.vue'),
    meta: { title: 'Sessões' },
  },
  {
    path: 'sessions/technology/:id',
    name: 'sessions-by-technology',
    component: () => import('@/views/sessions/TechnologySessionsView.vue'),
    meta: { title: 'Sessões por tecnologia' },
  },
  {
    path: 'session',
    name: 'session-focus',
    component: () => import('@/views/sessions/SessionFocusView.vue'),
    meta: { title: 'Sessão ativa' },
  },
  {
    path: 'sessions/:id',
    name: 'session-detail',
    component: () => import('@/views/sessions/SessionDetailView.vue'),
    meta: { title: 'Sessão de estudo' },
  },
]
