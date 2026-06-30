import type { RouteRecordRaw } from 'vue-router'

export const canvasRoutes: RouteRecordRaw[] = [
  {
    path: 'canvas',
    name: 'canvas',
    component: () => import('@/views/canvas/CanvasView.vue'),
    meta: { title: 'Canvas' },
  },
]
