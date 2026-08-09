/**
 * Chaves de query para TanStack Query.
 * Centralizar aqui evita colisões e facilita invalidação.
 */
export const queryKeys = {
  analytics: {
    all: ['analytics'] as const,
    dashboard: () => [...queryKeys.analytics.all, 'dashboard'] as const,
    heatmap: (year?: number) => [...queryKeys.analytics.all, 'heatmap', year] as const,
    weekly: () => [...queryKeys.analytics.all, 'weekly'] as const,
    timeSeries: (days: number) => [...queryKeys.analytics.all, 'timeSeries', days] as const,
    techStats: () => [...queryKeys.analytics.all, 'techStats'] as const,
  },
  sessions: {
    all: ['sessions'] as const,
    list: (params?: Record<string, unknown>) =>
      [...queryKeys.sessions.all, 'list', params] as const,
    active: () => [...queryKeys.sessions.all, 'active'] as const,
    detail: (id: string) => [...queryKeys.sessions.all, 'detail', id] as const,
  },
  technologies: {
    all: ['technologies'] as const,
    list: () => [...queryKeys.technologies.all, 'list'] as const,
    detail: (id: string) => [...queryKeys.technologies.all, 'detail', id] as const,
  },
  linkedin: {
    all: ['linkedin'] as const,
    status: () => [...queryKeys.linkedin.all, 'status'] as const,
  },
  exercises: {
    all: ['exercises'] as const,
    templates: () => [...queryKeys.exercises.all, 'templates'] as const,
    attempts: () => [...queryKeys.exercises.all, 'attempts'] as const,
    stats: () => [...queryKeys.exercises.all, 'stats'] as const,
  },
  flashcards: {
    all: ['flashcards'] as const,
    decks: () => [...queryKeys.flashcards.all, 'decks'] as const,
    cards: (deckId: string) => [...queryKeys.flashcards.all, 'cards', deckId] as const,
    due: () => [...queryKeys.flashcards.all, 'due'] as const,
  },
  itaStudy: {
    all: ['ita-study'] as const,
    subjects: () => [...queryKeys.itaStudy.all, 'subjects'] as const,
    subject: (id: string) => [...queryKeys.itaStudy.subjects(), id] as const,
    topics: (subjectId: string) => [...queryKeys.itaStudy.subject(subjectId), 'topics'] as const,
    subTopics: (topicId: string) => [...queryKeys.itaStudy.all, 'subtopics', topicId] as const,
    question: (subTopicId: string) => [...queryKeys.itaStudy.all, 'question', subTopicId] as const,
    progress: () => [...queryKeys.itaStudy.all, 'progress'] as const,
    subjectProgress: (id: string) => [...queryKeys.itaStudy.progress(), id] as const,
    subTopicDetail: (id: string) => [...queryKeys.itaStudy.all, 'detail', id] as const,
    favorites: () => [...queryKeys.itaStudy.all, 'favorites'] as const,
    note: (subTopicId: string) => [...queryKeys.itaStudy.all, 'note', subTopicId] as const,
    readingProgress: (subTopicId: string) => [...queryKeys.itaStudy.all, 'reading', subTopicId] as const,
  },
} as const
