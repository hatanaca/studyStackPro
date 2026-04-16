import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Goal, CreateGoalPayload } from '@/types/goals.types'
import { goalsApi } from '@/api/modules/goals.api'

export const useGoalsStore = defineStore('goals', () => {
  const items = ref<Goal[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const activeGoals = computed(() => items.value.filter((g) => g.status === 'active'))
  const completedGoals = computed(() => items.value.filter((g) => g.status === 'completed'))

  async function fetchGoals() {
    loading.value = true
    error.value = null
    try {
      const { data } = await goalsApi.list()
      items.value = data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao carregar metas'
    } finally {
      loading.value = false
    }
  }

  async function createGoal(payload: CreateGoalPayload) {
    error.value = null
    try {
      const { data } = await goalsApi.create(payload)
      items.value = [data, ...items.value]
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao criar meta'
      throw e
    }
  }

  async function updateGoal(
    id: string,
    payload: { target_value?: number; status?: Goal['status']; end_date?: string | null }
  ) {
    error.value = null
    try {
      const { data } = await goalsApi.update(id, payload)
      const index = items.value.findIndex((g) => g.id === id)
      if (index !== -1) items.value[index] = data
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao atualizar meta'
      throw e
    }
  }

  async function deleteGoal(id: string) {
    error.value = null
    try {
      await goalsApi.delete(id)
      items.value = items.value.filter((g) => g.id !== id)
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao excluir meta'
      throw e
    }
  }

  function getProgress(goal: Goal, currentValueOverride?: number): number {
    const current = currentValueOverride ?? goal.current_value
    if (goal.target_value <= 0) return 0
    return Math.min(100, Math.round((current / goal.target_value) * 100))
  }

  /** Retorna a primeira meta ativa do tipo minutos por semana (para o widget do dashboard). */
  function getActiveWeeklyMinutesGoal(): Goal | null {
    return items.value.find((g) => g.status === 'active' && g.type === 'minutes_per_week') ?? null
  }

  return {
    items,
    loading,
    error,
    activeGoals,
    completedGoals,
    fetchGoals,
    createGoal,
    updateGoal,
    deleteGoal,
    getProgress,
    getActiveWeeklyMinutesGoal,
  }
})
