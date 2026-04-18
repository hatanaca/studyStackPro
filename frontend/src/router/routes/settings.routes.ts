export const settingsRoutes = [
  {
    path: 'settings',
    component: () => import('@/views/settings/SettingsLayout.vue'),
    meta: { title: 'Configurações' },
    children: [
      { path: '', name: 'settings-index', redirect: { name: 'settings-appearance' } },
      {
        path: 'appearance',
        name: 'settings-appearance',
        component: () => import('@/views/settings/SettingsAppearanceView.vue'),
        meta: { title: 'Aparência' },
      },
      {
        path: 'data',
        redirect: { name: 'settings-appearance' },
      },
      {
        path: 'export',
        name: 'settings-export',
        component: () => import('@/views/export/ExportView.vue'),
        meta: { title: 'Exportar dados' },
      },
      {
        path: 'reports',
        name: 'settings-reports',
        component: () => import('@/views/reports/ReportsView.vue'),
        meta: { title: 'Relatórios' },
      },
      {
        path: 'help',
        name: 'settings-help',
        component: () => import('@/views/help/HelpView.vue'),
        meta: { title: 'Ajuda' },
      },
    ],
  },
]

/** Redireciona URLs antigas para as novas rotas sob /settings. */
export const legacySettingsRedirects = [
  { path: 'export', redirect: { name: 'settings-export' } },
  { path: 'reports', redirect: { name: 'settings-reports' } },
  { path: 'help', redirect: { name: 'settings-help' } },
  { path: 'data', redirect: { name: 'settings-appearance' } },
]
