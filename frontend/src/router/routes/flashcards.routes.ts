export const flashcardsRoutes = [
  {
    path: 'flashcards',
    name: 'flashcards',
    component: () => import('@/views/flashcards/FlashcardsView.vue'),
    meta: { title: 'Flashcards' },
  },
]
