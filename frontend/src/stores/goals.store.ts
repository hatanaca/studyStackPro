import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Goal, CreateGoalPayload } from '@/types/goals.types'
import { goalsApi } from '@/api/modules/goals.api'

export const useGoalsStore = defineStore('goals', () => {
  const items = ref<Goal[]>([])
  const error = ref<string | null>(null)

  const activeGoals = computed(() => items.value.filter((g) => g.status === 'active'))
  const completedGoals = computed(() => items.value.filter((g) => g.status === 'completed'))

  async function fetchGoals() {
    error.value = null
    try {
      items.value = await goalsApi.list()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao carregar metas'
    }
  }

  async function createGoal(payload: CreateGoalPayload): Promise<Goal | null> {
    error.value = null
    try {
      const goal = await goalsApi.create(payload)
      items.value = [goal, ...items.value]
      return goal
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao criar meta'
      return null
    }
  }

  async function updateGoal(
    id: string,
    payload: { target_value?: number; status?: Goal['status']; end_date?: string | null }
  ): Promise<Goal | null> {
    error.value = null
    try {
      const updated = await goalsApi.update(id, payload)
      const index = items.value.findIndex((g) => g.id === id)
      if (index !== -1) {
        items.value = [...items.value.slice(0, index), updated, ...items.value.slice(index + 1)]
      }
      return updated
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao atualizar meta'
      return null
    }
  }

  async function deleteGoal(id: string): Promise<boolean> {
    error.value = null
    try {
      await goalsApi.delete(id)
      items.value = items.value.filter((g) => g.id !== id)
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao excluir meta'
      return false
    }
  }

  function getProgress(goal: Goal, currentValueOverride?: number): number {
    const current = currentValueOverride ?? goal.current_value
    if (goal.target_value <= 0) return 0
    return Math.min(100, Math.round((current / goal.target_value) * 100))
  }

  function getActiveWeeklyMinutesGoal(): Goal | null {
    return items.value.find((g) => g.status === 'active' && g.type === 'minutes_per_week') ?? null
  }

  return { items, error, activeGoals, completedGoals, fetchGoals, createGoal, updateGoal, deleteGoal, getProgress, getActiveWeeklyMinutesGoal }
})
