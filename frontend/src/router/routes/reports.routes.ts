export const reportsRoutes = [
  {
    path: 'reports',
    name: 'reports',
    component: () => import('@/views/reports/ReportsView.vue'),
    meta: { title: 'Relatórios' },
  },
]
