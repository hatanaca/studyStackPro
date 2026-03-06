export const goalsRoutes = [
  {
    path: 'goals',
    name: 'goals',
    component: () => import('@/views/goals/GoalsView.vue'),
    meta: { title: 'Metas' },
  },
]
