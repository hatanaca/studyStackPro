export const profileRoutes = [
  {
    path: 'profile',
    name: 'profile',
    component: () => import('@/views/profile/ProfileView.vue'),
    meta: { title: 'Perfil' },
  },
]
