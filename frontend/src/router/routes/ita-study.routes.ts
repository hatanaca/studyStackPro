export const itaStudyRoutes = [
  {
    path: 'ita-study',
    name: 'ita-study',
    component: () => import('@/views/ita-study/ItaStudyHomeView.vue'),
    meta: { title: 'Estudo ITA' },
  },
  {
    path: 'ita-study/:subjectId',
    name: 'ita-study-subject',
    component: () => import('@/views/ita-study/ItaStudySubjectView.vue'),
    meta: { title: 'Estudo ITA' },
  },
  {
    path: 'ita-study/:subjectId/:topicId',
    name: 'ita-study-topic',
    component: () => import('@/views/ita-study/ItaStudyTopicView.vue'),
    meta: { title: 'Estudo ITA' },
  },
  {
    path: 'ita-study/:subjectId/:topicId/:subTopicId',
    name: 'ita-study-content',
    component: () => import('@/views/ita-study/ItaStudyContentView.vue'),
    meta: { title: 'Estudo ITA' },
  },
  {
    path: 'ita-study/session/:subTopicId',
    name: 'ita-study-session',
    component: () => import('@/views/ita-study/ItaStudySessionView.vue'),
    meta: { title: 'Sessão de Estudo' },
  },
]
