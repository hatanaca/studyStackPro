export const technologiesRoutes = [
  {
    path: 'technologies',
    name: 'technologies',
    component: () => import('@/views/technologies/TechnologiesView.vue'),
    meta: { title: 'Tecnologias' },
  },
  {
    path: 'technologies/:id',
    name: 'technology-detail',
    component: () => import('@/views/technologies/TechnologyDetailView.vue'),
    meta: { title: 'Detalhes da tecnologia' },
  },
]
