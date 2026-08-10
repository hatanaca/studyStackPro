/**
 * API de canvas (artworks).
 * CRUD completo via backend Laravel.
 */

import { apiClient, unwrap } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'

export interface CanvasArtwork {
  id: string
  user_id: string
  title: string
  canvas_data: Record<string, unknown> | null
  mural_items: Array<{ id: string; type: string; url: string }> | null
  width: number
  height: number
  bg_color: string
  created_at: string
  updated_at: string
}

export interface CreateCanvasPayload {
  title?: string
  canvas_data?: Record<string, unknown>
  mural_items?: Array<{ id: string; type: string; url: string }>
  width?: number
  height?: number
  bg_color?: string
}

export const canvasApi = {
  async list(): Promise<CanvasArtwork[]> {
    return unwrap(apiClient.get(ENDPOINTS.canvas.list))
  },

  async create(payload: CreateCanvasPayload): Promise<CanvasArtwork> {
    return unwrap(apiClient.post(ENDPOINTS.canvas.list, payload))
  },

  async get(id: string): Promise<CanvasArtwork> {
    return unwrap(apiClient.get(ENDPOINTS.canvas.one(id)))
  },

  async update(id: string, payload: Partial<CreateCanvasPayload>): Promise<CanvasArtwork> {
    return unwrap(apiClient.put(ENDPOINTS.canvas.one(id), payload))
  },

  async delete(id: string): Promise<void> {
    await apiClient.delete(ENDPOINTS.canvas.one(id))
  },
}
