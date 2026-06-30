import type { RouteRecordRaw } from 'vue-router'

export const reportsRoutes: RouteRecordRaw[] = [
  {
    path: 'graficos',
    name: 'graficos',
    component: () => import('@/views/graficos/GraficosView.vue'),
    meta: { title: 'Gráficos & Analytics' },
  },
]
