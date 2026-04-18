export const technologiesRoutes = [
  {
    path: 'technologies',
    name: 'technologies',
    component: () => import('@/views/technologies/TechnologiesView.vue'),
    meta: { title: 'Tecnologias' },
  },
  {
    path: 'technologies/:id',
    component: () => import('@/views/technologies/TechnologyDetailLayout.vue'),
    meta: { title: 'Detalhes da tecnologia' },
    children: [
      {
        path: '',
        name: 'technology-detail',
        component: () => import('@/views/technologies/TechnologyDetailOverviewView.vue'),
        meta: { title: 'Detalhes da tecnologia' },
      },
      {
        path: 'lembretes',
        name: 'technology-detail-lembretes',
        component: () => import('@/views/technologies/TechnologyDetailRemindersView.vue'),
        meta: { title: 'Lembretes' },
      },
      {
        path: 'mural',
        name: 'technology-detail-mural',
        component: () => import('@/views/technologies/TechnologyDetailMuralView.vue'),
        meta: { title: 'Mural' },
      },
      {
        path: 'mapa',
        name: 'technology-detail-mapa',
        component: () => import('@/views/technologies/TechnologyDetailMapView.vue'),
        meta: { title: 'Mapa de estudos' },
      },
      {
        path: 'sessoes',
        name: 'technology-detail-sessoes',
        component: () => import('@/views/technologies/TechnologyDetailSessionsView.vue'),
        meta: { title: 'Sessões' },
      },
    ],
  },
]
