export const terminalRoutes = [
  {
    path: 'terminal',
    name: 'terminal',
    component: () => import('@/views/terminal/TerminalView.vue'),
    meta: { title: 'Code Terminal' },
  },
]
