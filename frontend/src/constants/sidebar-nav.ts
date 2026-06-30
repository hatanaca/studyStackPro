import {
  prefetchDashboardView,
  prefetchProfileView,
  prefetchSessionsView,
  prefetchSettingsView,
  prefetchTechnologiesView,
} from '@/router/prefetch'

/**
 * Iten de navegação da sidebar.
 */
export interface SidebarNavItem {
  /** Rota de destino (string ou objeto route) */
  to: string | { name: string }
  /** Texto exibido no menu */
  label: string
  /** Nome do ícone */
  icon: string
  /** Função de pré-carregamento chamada no hover */
  prefetch?: () => void
  /** Função para determinar se o item está ativo */
  isActive?: (path: string, routeName?: string, routeQuery?: Record<string, unknown>) => boolean
  /** Classe CSS para ativo exato */
  exactActiveClass?: string
}

/**
 * Itens de navegação da sidebar principal.
 * Cada item inclui rota, label, ícone e função de prefetch opcional.
 */
export const sidebarNavItems: SidebarNavItem[] = [
  {
    to: '/',
    label: 'Dashboard',
    icon: 'dashboard',
    prefetch: prefetchDashboardView,
    exactActiveClass: 'active',
  },
  {
    to: { name: 'sessions' },
    label: 'Sessões',
    icon: 'sessions',
    prefetch: prefetchSessionsView,
  },
  {
    to: '/technologies',
    label: 'Tecnologias',
    icon: 'technologies',
    prefetch: prefetchTechnologiesView,
  },
  {
    to: '/canvas',
    label: 'Canvas',
    icon: 'canvas',
    isActive: (path) => path.startsWith('/canvas'),
  },
  {
    to: '/graficos',
    label: 'Gráficos',
    icon: 'reports',
    isActive: (path) => path.startsWith('/graficos'),
  },
  {
    to: '/settings',
    label: 'Configurações',
    icon: 'settings',
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
