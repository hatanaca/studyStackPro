/**
 * Configuração do Vue Router.
 * Auth routes (login/register) fora do layout; rotas autenticadas dentro de AppLayout.
 * Guard de auth em beforeEach; título dinâmico em afterEach.
 */
import { createRouter, createWebHistory } from 'vue-router'
import { setupAuthGuard } from './guards'
import { authRoutes } from './routes/auth.routes'
import { dashboardRoutes } from './routes/dashboard.routes'
import { profileRoutes } from './routes/profile.routes'
import { sessionsRoutes } from './routes/sessions.routes'
import { technologiesRoutes } from './routes/technologies.routes'
import { goalsRoutes } from './routes/goals.routes'
import { exportRoutes } from './routes/export.routes'
import { settingsRoutes } from './routes/settings.routes'
import { reportsRoutes } from './routes/reports.routes'
import { helpRoutes } from './routes/help.routes'

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
        ...goalsRoutes,
        ...exportRoutes,
        ...settingsRoutes,
        ...reportsRoutes,
        ...helpRoutes,
        ...profileRoutes,
      ],
    },
  ],
})

/** Guard global: redireciona não autenticados para login e guests para dashboard */
router.beforeEach(setupAuthGuard)

const APP_TITLE = 'StudyTrack Pro'
/** Define título da página com base no meta.title da rota */
router.afterEach((to) => {
  const title = to.meta?.title as string | undefined
  document.title = title ? `${title} · ${APP_TITLE}` : APP_TITLE
})

export default router
