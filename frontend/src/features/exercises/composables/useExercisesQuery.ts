import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { unwrap } from '@/api/client'
import { exercisesApi, type ExerciseStats } from '@/api/modules/exercises.api'
import { queryKeys } from '@/api/queryKeys'
import { useQuerySessionEnabled } from '@/composables/useQueryAuthEnabled'
import type {
  ExerciseAttempt,
  ExerciseTemplate,
  ExerciseVariant,
} from '@/features/exercises/types/exercises.types'

export function useTemplatesQuery() {
  const enabled = useQuerySessionEnabled()

  return useQuery({
    queryKey: queryKeys.exercises.templates(),
    queryFn: async (): Promise<ExerciseTemplate[]> => unwrap(exercisesApi.listTemplates()),
    staleTime: 5 * 60 * 1000,
    enabled,
  })
}

export function useAttemptsQuery() {
  const enabled = useQuerySessionEnabled()

  return useQuery({
    queryKey: queryKeys.exercises.attempts(),
    queryFn: async (): Promise<ExerciseAttempt[]> => unwrap(exercisesApi.attempts()),
    staleTime: 30 * 1000,
    enabled,
  })
}

export function useStatsQuery() {
  const enabled = useQuerySessionEnabled()

  return useQuery({
    queryKey: queryKeys.exercises.stats(),
    queryFn: async (): Promise<ExerciseStats> => unwrap(exercisesApi.stats()),
    staleTime: 30 * 1000,
    enabled,
  })
}

export function useGenerateVariantMutation() {
  return useMutation({
    mutationFn: async ({
      templateId,
      seed,
    }: {
      templateId: string
      seed?: number
    }): Promise<ExerciseVariant> => unwrap(exercisesApi.generateVariant(templateId, seed)),
  })
}

export function useGradeMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async ({
      variantId,
      answer,
    }: {
      variantId: string
      answer: string
    }): Promise<ExerciseAttempt> => unwrap(exercisesApi.grade(variantId, answer)),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.exercises.attempts() })
    },
  })
}
