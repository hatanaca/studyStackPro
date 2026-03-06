export const sessionsRoutes = [
  {
    path: 'sessions',
    name: 'sessions',
    component: () => import('@/views/sessions/SessionsView.vue'),
  },
  {
    path: 'sessions/technology/:id',
    name: 'sessions-by-technology',
    component: () => import('@/views/sessions/TechnologySessionsView.vue'),
  },
  {
    path: 'sessions/:id',
    name: 'session-detail',
    component: () => import('@/views/sessions/SessionDetailView.vue'),
  },
]
