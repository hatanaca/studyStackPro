import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

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

  function add(notification: Omit<AppNotification, 'id' | 'read' | 'created_at'>) {
    const id = `notif_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`
    items.value = [
      {
        ...notification,
        id,
        read: false,
        created_at: new Date().toISOString(),
      },
      ...items.value,
    ].slice(0, 50)
  }

  function markRead(id: string) {
    const item = items.value.find(n => n.id === id)
    if (item) item.read = true
  }

  function markAllRead() {
    items.value.forEach(n => { n.read = true })
  }

  function remove(id: string) {
    items.value = items.value.filter(n => n.id !== id)
  }

  const unreadCount = computed(() => items.value.filter(n => !n.read).length)

  return {
    items,
    add,
    markRead,
    markAllRead,
    remove,
    unreadCount,
  }
})
