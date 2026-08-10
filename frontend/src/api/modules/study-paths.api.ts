/**
 * API de study paths (mapas de estudo).
 * CRUD completo via backend Laravel.
 */

import { apiClient, unwrap } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'
import type { Node, Edge } from '@vue-flow/core'

export interface StudyPath {
  id: string
  user_id: string
  title: string
  technology_id: string | null
  nodes: Node[] | null
  edges: Edge[] | null
  created_at: string
  updated_at: string
}

export interface CreateStudyPathPayload {
  title?: string
  technology_id?: string | null
  nodes?: Node[]
  edges?: Edge[]
}

export const studyPathsApi = {
  async list(): Promise<StudyPath[]> {
    return unwrap(apiClient.get(ENDPOINTS.studyPaths.list))
  },

  async create(payload: CreateStudyPathPayload): Promise<StudyPath> {
    return unwrap(apiClient.post(ENDPOINTS.studyPaths.list, payload))
  },

  async get(id: string): Promise<StudyPath> {
    return unwrap(apiClient.get(ENDPOINTS.studyPaths.one(id)))
  },

  async update(id: string, payload: Partial<CreateStudyPathPayload>): Promise<StudyPath> {
    return unwrap(apiClient.put(ENDPOINTS.studyPaths.one(id), payload))
  },

  async delete(id: string): Promise<void> {
    await apiClient.delete(ENDPOINTS.studyPaths.one(id))
  },

  async getByTechnology(technologyId: string): Promise<StudyPath | null> {
    return unwrap(apiClient.get(ENDPOINTS.studyPaths.byTechnology(technologyId)))
  },
}
