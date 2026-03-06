/**
 * API de metas (goals).
 * Endpoints podem ser implementados no backend; por ora retorna dados vazios ou de localStorage.
 */

import type { Goal, CreateGoalPayload, UpdateGoalPayload } from '@/types/goals.types'

const STORAGE_KEY = 'studytrack.goals'

function getStoredGoals(): Goal[] {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return []
    return JSON.parse(raw) as Goal[]
  } catch {
    return []
  }
}

function setStoredGoals(goals: Goal[]) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(goals))
  } catch {
    // ignore
  }
}

export const goalsApi = {
  async list(): Promise<{ data: Goal[] }> {
    // Se o backend tiver endpoint: return (await apiClient.get('/goals')).data
    const list = getStoredGoals()
    return { data: list }
  },

  async create(payload: CreateGoalPayload): Promise<{ data: Goal }> {
    const list = getStoredGoals()
    const id = `goal_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`
    const now = new Date().toISOString()
    const goal: Goal = {
      id,
      user_id: '',
      type: payload.type,
      target_value: payload.target_value,
      current_value: 0,
      status: 'active',
      start_date: payload.start_date,
      end_date: payload.end_date ?? null,
      created_at: now,
      updated_at: now,
      meta: payload.meta ?? {},
    }
    list.push(goal)
    setStoredGoals(list)
    return { data: goal }
  },

  async update(id: string, payload: UpdateGoalPayload): Promise<{ data: Goal }> {
    const list = getStoredGoals()
    const index = list.findIndex(g => g.id === id)
    if (index === -1) throw new Error('Meta não encontrada')
    const updated = { ...list[index], ...payload, updated_at: new Date().toISOString() }
    list[index] = updated
    setStoredGoals(list)
    return { data: updated }
  },

  async delete(id: string): Promise<{ success: boolean }> {
    const list = getStoredGoals().filter(g => g.id !== id)
    setStoredGoals(list)
    return { success: true }
  },
}
