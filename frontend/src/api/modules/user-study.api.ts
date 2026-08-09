import { apiClient } from '@/api/client'
import type { ApiResponse } from '@/types/api.types'
import type {
  ReadingProgress,
  StudyFavorite,
  StudyNote,
  StudySubTopicDetail,
} from '@/features/ita-study/types/study-content.types'

export const userStudyApi = {
  getSubTopicDetail: (subTopicId: string) =>
    apiClient.get<ApiResponse<StudySubTopicDetail>>(`/ita-study/subtopics/${subTopicId}`),

  listFavorites: () =>
    apiClient.get<ApiResponse<StudyFavorite[]>>('/ita-study/favorites'),

  addFavorite: (subTopicId: string) =>
    apiClient.post<ApiResponse<StudyFavorite>>('/ita-study/favorites', { sub_topic_id: subTopicId }),

  removeFavorite: (subTopicId: string) =>
    apiClient.delete<ApiResponse<null>>(`/ita-study/favorites/${subTopicId}`),

  getNote: (subTopicId: string) =>
    apiClient.get<ApiResponse<StudyNote | null>>(`/ita-study/subtopics/${subTopicId}/note`),

  saveNote: (subTopicId: string, content: string) =>
    apiClient.put<ApiResponse<StudyNote>>(`/ita-study/subtopics/${subTopicId}/note`, { content }),

  deleteNote: (subTopicId: string) =>
    apiClient.delete<ApiResponse<null>>(`/ita-study/subtopics/${subTopicId}/note`),

  getReadingProgress: (subTopicId: string) =>
    apiClient.get<ApiResponse<ReadingProgress>>(`/ita-study/subtopics/${subTopicId}/reading-progress`),

  updateReadingProgress: (subTopicId: string, progress: number) =>
    apiClient.put<ApiResponse<ReadingProgress>>(`/ita-study/subtopics/${subTopicId}/reading-progress`, {
      progress,
    }),
}
