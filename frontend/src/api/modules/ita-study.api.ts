import { apiClient } from '@/api/client'
import type { ApiResponse } from '@/types/api.types'
import type {
  OverallProgress,
  ProgressData,
  StudyAttemptResult,
  StudyQuestion,
  StudySubject,
  StudySubTopic,
  StudyTopic,
} from '@/features/ita-study/types/ita-study.types'

export const itaStudyApi = {
  listSubjects: () =>
    apiClient.get<ApiResponse<StudySubject[]>>('/ita-study/subjects'),

  listTopics: (subjectId: string) =>
    apiClient.get<ApiResponse<StudyTopic[]>>(`/ita-study/subjects/${subjectId}/topics`),

  listSubTopics: (topicId: string) =>
    apiClient.get<ApiResponse<StudySubTopic[]>>(`/ita-study/topics/${topicId}/subtopics`),

  generateQuestion: (subTopicId: string, difficulty?: number) =>
    apiClient.post<ApiResponse<StudyQuestion>>('/ita-study/questions/generate', {
      sub_topic_id: subTopicId,
      difficulty,
    }),

  submitAnswer: (variantId: string, answer: string, timeSpentSeconds?: number) =>
    apiClient.post<ApiResponse<StudyAttemptResult>>('/ita-study/questions/answer', {
      variant_id: variantId,
      answer,
      time_spent_seconds: timeSpentSeconds,
    }),

  generateBatch: (subTopicId: string, count: number, difficulty?: number) =>
    apiClient.post<ApiResponse<StudyQuestion[]>>('/ita-study/questions/generate-batch', {
      sub_topic_id: subTopicId,
      count,
      difficulty,
    }),

  getProgress: () =>
    apiClient.get<ApiResponse<OverallProgress>>('/ita-study/progress'),

  getSubjectProgress: (subjectId: string) =>
    apiClient.get<ApiResponse<{ topics: StudyTopic[]; progress: ProgressData }>>(
      `/ita-study/progress/subject/${subjectId}`
    ),

  getTopicProgress: (topicId: string) =>
    apiClient.get<ApiResponse<{ attempted: number; mastered: number; total: number; percentage: number }>>(
      `/ita-study/progress/topic/${topicId}`
    ),
}
