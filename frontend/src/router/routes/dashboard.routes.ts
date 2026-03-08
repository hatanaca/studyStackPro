export const dashboardRoutes = [
  {
    path: '',
    name: 'dashboard',
    component: () => import('@/views/Dashboard/DashboardView.vue'),
    meta: { title: 'Dashboard' },
  },
]
