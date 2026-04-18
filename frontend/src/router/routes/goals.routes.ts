/** Metas passam a viver no perfil (tab «Metas»); `/goals` mantém compatibilidade. */
export const goalsRoutes = [
  {
    path: 'goals',
    redirect: { name: 'profile', query: { tab: 'goals' } },
  },
]
