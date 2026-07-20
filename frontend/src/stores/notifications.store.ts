import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notificationsApi, type Notification } from '@/api/modules/notifications.api'

export type NotificationType = 'info' | 'success' | 'warning' | 'error'

export interface AppNotification {
  id: string
  type: NotificationType
  title: string
  message?: string
  read: boolean
  created_at: string
  actionUrl?: string
  actionLabel?: string
}

export const useNotificationsStore = defineStore('notifications', () => {
  const items = ref<AppNotification[]>([])
  const loading = ref(false)

  async function fetchNotifications() {
    loading.value = true
    try {
      const notifications = await notificationsApi.list()
      items.value = notifications.map(mapNotification)
    } catch {
      // keep existing items on error
    } finally {
      loading.value = false
    }
  }

  async function add(notification: Omit<AppNotification, 'id' | 'read' | 'created_at'>) {
    try {
      const created = await notificationsApi.create({
        type: notification.type,
        title: notification.title,
        message: notification.message,
        action_url: notification.actionUrl,
        action_label: notification.actionLabel,
      })
      items.value = [mapNotification(created), ...items.value].slice(0, 50)
    } catch {
      // fallback to local
      const id = `notif_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`
      items.value = [
        { ...notification, id, read: false, created_at: new Date().toISOString() },
        ...items.value,
      ].slice(0, 50)
    }
  }

  async function markRead(id: string) {
    items.value = items.value.map((n) => (n.id === id ? { ...n, read: true } : n))
    try {
      await notificationsApi.markRead(id)
    } catch {
      // optimistic update already applied
    }
  }

  async function markAllRead() {
    items.value = items.value.map((n) => ({ ...n, read: true }))
    try {
      await notificationsApi.markAllRead()
    } catch {
      // optimistic update already applied
    }
  }

  async function remove(id: string) {
    items.value = items.value.filter((n) => n.id !== id)
    try {
      await notificationsApi.delete(id)
    } catch {
      // optimistic update already applied
    }
  }

  const unreadCount = computed(() => items.value.filter((n) => !n.read).length)

  function mapNotification(n: Notification): AppNotification {
    return {
      id: n.id,
      type: n.type,
      title: n.title,
      message: n.message ?? undefined,
      read: n.read,
      created_at: n.created_at,
      actionUrl: n.action_url ?? undefined,
      actionLabel: n.action_label ?? undefined,
    }
  }

  return { items, loading, add, markRead, markAllRead, remove, unreadCount, fetchNotifications }
})
