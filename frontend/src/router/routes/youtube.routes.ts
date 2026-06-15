export const youtubeRoutes = [
  {
    path: 'videos',
    name: 'youtube-search',
    component: () => import('@/views/videos/YouTubeSearchView.vue'),
    meta: { title: 'Vídeos de Estudo' },
  },
]
