import { computed } from 'vue'
import type { Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { userStudyApi } from '@/api/modules/user-study.api'
import { queryKeys } from '@/api/queryKeys'
import { useQuerySessionEnabled } from '@/composables/useQueryAuthEnabled'

export function useSubTopicDetailQuery(subTopicId: Ref<string>) {
  const enabled = useQuerySessionEnabled(computed(() => !!subTopicId.value))

  return useQuery({
    queryKey: queryKeys.itaStudy.subTopicDetail(subTopicId.value),
    queryFn: () => userStudyApi.getSubTopicDetail(subTopicId.value),
    select: (response) => response.data.data,
    enabled,
  })
}

export function useFavoritesQuery() {
  const enabled = useQuerySessionEnabled()

  return useQuery({
    queryKey: queryKeys.itaStudy.favorites(),
    queryFn: () => userStudyApi.listFavorites(),
    select: (response) => response.data.data,
    enabled,
  })
}

export function useAddFavoriteMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (subTopicId: string) => userStudyApi.addFavorite(subTopicId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.itaStudy.favorites() })
    },
  })
}

export function useRemoveFavoriteMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (subTopicId: string) => userStudyApi.removeFavorite(subTopicId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.itaStudy.favorites() })
    },
  })
}

export function useNoteQuery(subTopicId: Ref<string>) {
  const enabled = useQuerySessionEnabled(computed(() => !!subTopicId.value))

  return useQuery({
    queryKey: queryKeys.itaStudy.note(subTopicId.value),
    queryFn: () => userStudyApi.getNote(subTopicId.value),
    select: (response) => response.data.data,
    enabled,
  })
}

export function useSaveNoteMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ subTopicId, content }: { subTopicId: string; content: string }) =>
      userStudyApi.saveNote(subTopicId, content),
    onSuccess: (_data, variables) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.itaStudy.note(variables.subTopicId) })
    },
  })
}

export function useDeleteNoteMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (subTopicId: string) => userStudyApi.deleteNote(subTopicId),
    onSuccess: (_data, subTopicId) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.itaStudy.note(subTopicId) })
    },
  })
}

export function useReadingProgressQuery(subTopicId: Ref<string>) {
  const enabled = useQuerySessionEnabled(computed(() => !!subTopicId.value))

  return useQuery({
    queryKey: queryKeys.itaStudy.readingProgress(subTopicId.value),
    queryFn: () => userStudyApi.getReadingProgress(subTopicId.value),
    select: (response) => response.data.data,
    enabled,
  })
}

export function useUpdateReadingProgressMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ subTopicId, progress }: { subTopicId: string; progress: number }) =>
      userStudyApi.updateReadingProgress(subTopicId, progress),
    onSuccess: (_data, variables) => {
      queryClient.invalidateQueries({
        queryKey: queryKeys.itaStudy.readingProgress(variables.subTopicId),
      })
    },
  })
}
