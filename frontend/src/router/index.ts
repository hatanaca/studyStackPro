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
import { settingsRoutes, legacySettingsRedirects } from './routes/settings.routes'
import { studyPathRoutes } from './routes/study-path.routes'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  /** Garante scroll à âncora quando a rota define `hash` (ex.: links internos). */
  scrollBehavior(to, _from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0 }
  },
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
        ...studyPathRoutes,
        ...settingsRoutes,
        ...legacySettingsRedirects,
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
