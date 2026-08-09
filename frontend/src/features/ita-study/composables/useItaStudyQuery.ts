import { computed, type Ref } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { itaStudyApi } from '@/api/modules/ita-study.api'
import { queryKeys } from '@/api/queryKeys'
import { useQuerySessionEnabled } from '@/composables/useQueryAuthEnabled'

export function useSubjectsQuery() {
  const enabled = useQuerySessionEnabled()

  return useQuery({
    queryKey: queryKeys.itaStudy.subjects(),
    queryFn: () => itaStudyApi.listSubjects(),
    select: (response) => response.data.data,
    enabled,
  })
}

export function useTopicsQuery(subjectId: Ref<string>) {
  const enabled = useQuerySessionEnabled(computed(() => !!subjectId.value))

  return useQuery({
    queryKey: queryKeys.itaStudy.topics(subjectId.value),
    queryFn: () => itaStudyApi.listTopics(subjectId.value),
    select: (response) => response.data.data,
    enabled,
  })
}

export function useSubTopicsQuery(topicId: Ref<string>) {
  const enabled = useQuerySessionEnabled(computed(() => !!topicId.value))

  return useQuery({
    queryKey: queryKeys.itaStudy.subTopics(topicId.value),
    queryFn: () => itaStudyApi.listSubTopics(topicId.value),
    select: (response) => response.data.data,
    enabled,
  })
}

export function useGenerateQuestionMutation() {
  return useMutation({
    mutationFn: ({ subTopicId, difficulty }: { subTopicId: string; difficulty?: number }) =>
      itaStudyApi.generateQuestion(subTopicId, difficulty).then((response) => response.data.data),
  })
}

export function useSubmitAnswerMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({
      variantId,
      answer,
      timeSpentSeconds,
    }: {
      variantId: string
      answer: string
      timeSpentSeconds?: number
    }) =>
      itaStudyApi
        .submitAnswer(variantId, answer, timeSpentSeconds)
        .then((response) => response.data.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.itaStudy.progress() })
    },
  })
}

export function useProgressQuery() {
  const enabled = useQuerySessionEnabled()

  return useQuery({
    queryKey: queryKeys.itaStudy.progress(),
    queryFn: () => itaStudyApi.getProgress(),
    select: (response) => response.data.data,
    enabled,
  })
}

export function useSubjectProgressQuery(subjectId: Ref<string>) {
  const enabled = useQuerySessionEnabled(computed(() => !!subjectId.value))

  return useQuery({
    queryKey: queryKeys.itaStudy.subjectProgress(subjectId.value),
    queryFn: () => itaStudyApi.getSubjectProgress(subjectId.value),
    select: (response) => response.data.data,
    enabled,
  })
}
