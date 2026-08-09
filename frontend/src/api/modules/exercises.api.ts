import { apiClient } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { ApiResponse } from '@/types/api.types'
import type {
  ExerciseAttempt,
  ExerciseTemplate,
  ExerciseVariant,
} from '@/features/exercises/types/exercises.types'

export type ExerciseTemplatePayload = {
  title: string
  kind: 'numeric' | 'symbolic'
  prompt: string
  parameters_spec: Record<string, unknown>
  answer_expression: string
  solution_latex?: string | null
  variables?: string[] | null
  difficulty?: number
}

export const exercisesApi = {
  listTemplates: () =>
    apiClient.get<ApiResponse<ExerciseTemplate[]>>(ENDPOINTS.exercises.templates),

  createTemplate: (data: ExerciseTemplatePayload) =>
    apiClient.post<ApiResponse<ExerciseTemplate>>(ENDPOINTS.exercises.templates, data),

  updateTemplate: (id: string, data: Partial<ExerciseTemplatePayload>) =>
    apiClient.put<ApiResponse<ExerciseTemplate>>(ENDPOINTS.exercises.template(id), data),

  deleteTemplate: (id: string) =>
    apiClient.delete<ApiResponse<null>>(ENDPOINTS.exercises.template(id)),

  generateVariant: (templateId: string, seed?: number) =>
    apiClient.post<ApiResponse<ExerciseVariant>>(ENDPOINTS.exercises.generateVariant(templateId), {
      seed,
    }),

  grade: (variantId: string, answer: string) =>
    apiClient.post<ApiResponse<ExerciseAttempt>>(ENDPOINTS.exercises.grade, {
      variant_id: variantId,
      answer,
    }),

  attempts: () =>
    apiClient.get<ApiResponse<ExerciseAttempt[]>>(ENDPOINTS.exercises.attempts),

  stats: () => apiClient.get<ApiResponse<ExerciseStats>>(ENDPOINTS.exercises.stats),
}

export interface ExerciseStats {
  total_attempts: number
  correct_attempts: number
  accuracy: number
  by_template: Array<{ template_title: string; attempts: number; correct: number }>
}
