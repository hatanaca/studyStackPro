import {
  prefetchDashboardView,
  prefetchProfileView,
  prefetchSessionsView,
  prefetchTechnologiesView,
  prefetchSettingsView,
  prefetchCanvasView,
  prefetchGraficosView,
} from '@/router/prefetch'

/**
 * Iten de navegação da sidebar.
 */
export interface SidebarNavItem {
  /** Rota de destino (string ou objeto route) */
  to: string | { name: string }
  /** Texto exibido no menu */
  label: string
  /** Nome do ícone (ver SidebarIcon.vue) */
  icon: string
  /** Rota exibida sob o label */
  routeHint: string
  /** Função de pré-carregamento chamada no hover */
  prefetch?: () => void
  /** Função para determinar se o item está ativo */
  isActive?: (path: string, routeName?: string, routeQuery?: Record<string, unknown>) => boolean
  /** Classe CSS para ativo exato */
  exactActiveClass?: string
  /** Classe CSS para ativo via RouterLink (active-class) */
  activeClass?: string
}

/**
 * Itens de navegação da sidebar principal.
 * Fonte única da nav — consumida pelo AppSidebar.vue.
 */
export const sidebarNavItems: SidebarNavItem[] = [
  {
    to: '/',
    label: 'Dashboard',
    icon: 'dashboard',
    routeHint: '/',
    prefetch: prefetchDashboardView,
    exactActiveClass: 'active',
  },
  {
    to: { name: 'sessions' },
    label: 'Sessões',
    icon: 'sessions',
    routeHint: '/sessions',
    prefetch: prefetchSessionsView,
    activeClass: 'active',
  },
  {
    to: '/technologies',
    label: 'Tecnologias',
    icon: 'technologies',
    routeHint: '/technologies',
    prefetch: prefetchTechnologiesView,
    activeClass: 'active',
  },
  {
    to: '/canvas',
    label: 'Canvas',
    icon: 'canvas',
    routeHint: '/canvas',
    prefetch: prefetchCanvasView,
    isActive: (path) => path.startsWith('/canvas'),
  },
  {
    to: '/graficos',
    label: 'Gráficos',
    icon: 'reports',
    routeHint: '/graficos',
    prefetch: prefetchGraficosView,
    isActive: (path) => path.startsWith('/graficos'),
  },
  {
    to: '/terminal',
    label: 'Terminal',
    icon: 'terminal',
    routeHint: '/terminal',
    isActive: (path) => path.startsWith('/terminal'),
  },
  {
    to: '/exercises',
    label: 'Exercícios',
    icon: 'exercises',
    routeHint: '/exercises',
    isActive: (path) => path.startsWith('/exercises'),
  },
  {
    to: '/flashcards',
    label: 'Flashcards',
    icon: 'flashcards',
    routeHint: '/flashcards',
    isActive: (path) => path.startsWith('/flashcards'),
  },
  {
    to: '/ita-study',
    label: 'Estudo ITA',
    icon: 'ita-study',
    routeHint: '/ita-study',
    isActive: (path) => path.startsWith('/ita-study'),
  },
  {
    to: '/settings',
    label: 'Configurações',
    icon: 'settings',
    routeHint: '/settings',
    prefetch: prefetchSettingsView,
    isActive: (path) => path.startsWith('/settings'),
  },
]

export const sidebarStakentPills = [
  {
    to: '/',
    label: 'Estudo',
    prefetch: prefetchDashboardView,
    isActive: (path: string) => path === '/',
  },
  {
    to: { name: 'profile', query: { tab: 'goals' } },
    label: 'Metas',
    prefetch: prefetchProfileView,
    isActive: (_path: string, routeName?: string, routeQuery?: Record<string, unknown>) =>
      routeName === 'profile' && routeQuery?.tab === 'goals',
  },
]
