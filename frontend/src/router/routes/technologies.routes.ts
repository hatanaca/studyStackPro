export const technologiesRoutes = [
  {
    path: 'technologies',
    name: 'technologies',
    component: () => import('@/views/technologies/TechnologiesView.vue'),
  },
  {
    path: 'technologies/:id',
    name: 'technology-detail',
    component: () => import('@/views/technologies/TechnologyDetailView.vue'),
  },
]
