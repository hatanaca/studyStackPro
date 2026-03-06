export const exportRoutes = [
  {
    path: 'export',
    name: 'export',
    component: () => import('@/views/export/ExportView.vue'),
    meta: { title: 'Exportar dados' },
  },
]
