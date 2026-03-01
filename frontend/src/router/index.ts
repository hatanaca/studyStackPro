import { createRouter, createWebHistory } from 'vue-router'
import { setupAuthGuard } from './guards'
import { authRoutes } from './routes/auth.routes'
import { dashboardRoutes } from './routes/dashboard.routes'
import { profileRoutes } from './routes/profile.routes'
import { sessionsRoutes } from './routes/sessions.routes'
import { technologiesRoutes } from './routes/technologies.routes'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    ...authRoutes,
    {
      path: '/',
      component: () => import('@/components/layout/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        ...dashboardRoutes,
        ...sessionsRoutes,
        ...technologiesRoutes,
        ...profileRoutes,
      ],
    },
  ],
})

router.beforeEach(setupAuthGuard)

export default router
