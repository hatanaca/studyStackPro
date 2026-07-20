/**
 * API de metas (goals).
 * CRUD completo via backend Laravel.
 */

import { apiClient, unwrap } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { Goal, CreateGoalPayload, UpdateGoalPayload } from '@/types/goals.types'

export const goalsApi = {
  async list(): Promise<Goal[]> {
    return unwrap(apiClient.get(ENDPOINTS.goals.list))
  },

  async create(payload: CreateGoalPayload): Promise<Goal> {
    return unwrap(apiClient.post(ENDPOINTS.goals.list, payload))
  },

  async update(id: string, payload: UpdateGoalPayload): Promise<Goal> {
    return unwrap(apiClient.put(ENDPOINTS.goals.one(id), payload))
  },

  async delete(id: string): Promise<void> {
    await apiClient.delete(ENDPOINTS.goals.one(id))
  },
}
