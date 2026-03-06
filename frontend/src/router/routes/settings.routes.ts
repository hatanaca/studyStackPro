export const settingsRoutes = [
  {
    path: 'settings',
    name: 'settings',
    component: () => import('@/views/settings/SettingsView.vue'),
    meta: { title: 'Configurações da aplicação' },
  },
]
