/**
 * API de notificações.
 * CRUD + mark read via backend Laravel.
 */

import { apiClient, unwrap } from '@/api/client'
import { ENDPOINTS } from '@/api/endpoints'

export interface Notification {
  id: string
  type: 'info' | 'success' | 'warning' | 'error'
  title: string
  message: string | null
  read: boolean
  action_url: string | null
  action_label: string | null
  created_at: string
}

export interface CreateNotificationPayload {
  type: 'info' | 'success' | 'warning' | 'error'
  title: string
  message?: string
  action_url?: string
  action_label?: string
}

export const notificationsApi = {
  async list(unreadOnly = false): Promise<Notification[]> {
    const params = unreadOnly ? { unread: 'true' } : {}
    return unwrap(apiClient.get(ENDPOINTS.notifications.list, { params }))
  },

  async create(payload: CreateNotificationPayload): Promise<Notification> {
    return unwrap(apiClient.post(ENDPOINTS.notifications.list, payload))
  },

  async markRead(id: string): Promise<void> {
    await apiClient.post(ENDPOINTS.notifications.markRead(id))
  },

  async markAllRead(): Promise<void> {
    await apiClient.post(ENDPOINTS.notifications.markAllRead)
  },

  async delete(id: string): Promise<void> {
    await apiClient.delete(ENDPOINTS.notifications.one(id))
  },

  async unreadCount(): Promise<number> {
    const res = await unwrap<{ count: number }>(apiClient.get(ENDPOINTS.notifications.unreadCount))
    return res.count
  },
}
