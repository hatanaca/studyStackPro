export const exercisesRoutes = [
  {
    path: 'exercises',
    name: 'exercises',
    component: () => import('@/views/exercises/ExercisesView.vue'),
    meta: { title: 'Exercícios' },
  },
]
