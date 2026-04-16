import { setActivePinia, createPinia } from 'pinia'
import { useNotificationsStore } from '../notifications.store'
import type { NotificationType } from '../notifications.store'

const fakeNotification = (type: NotificationType = 'info', title = 'Test') => ({
  type,
  title,
  message: `Message for ${title}`,
})

describe('notifications.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('initial state has empty notifications', () => {
    const store = useNotificationsStore()

    expect(store.items).toEqual([])
    expect(store.unreadCount).toBe(0)
  })

  it('add inserts notification at the beginning', () => {
    const store = useNotificationsStore()

    store.add(fakeNotification('success', 'First'))
    store.add(fakeNotification('info', 'Second'))

    expect(store.items).toHaveLength(2)
    expect(store.items[0].title).toBe('Second')
    expect(store.items[1].title).toBe('First')
  })

  it('add generates unique id, read=false and created_at', () => {
    const store = useNotificationsStore()
    store.add(fakeNotification())

    const item = store.items[0]
    expect(item.id).toMatch(/^notif_/)
    expect(item.read).toBe(false)
    expect(item.created_at).toBeTruthy()
  })

  it('add caps items at 50', () => {
    const store = useNotificationsStore()
    for (let i = 0; i < 55; i++) {
      store.add(fakeNotification('info', `N${i}`))
    }

    expect(store.items).toHaveLength(50)
  })

  it('remove deletes notification by id', () => {
    const store = useNotificationsStore()
    store.add(fakeNotification('warning', 'To Remove'))
    const id = store.items[0].id

    store.remove(id)

    expect(store.items).toHaveLength(0)
  })

  it('remove is a no-op for unknown id', () => {
    const store = useNotificationsStore()
    store.add(fakeNotification())

    store.remove('nonexistent')

    expect(store.items).toHaveLength(1)
  })

  it('markRead marks single notification as read', () => {
    const store = useNotificationsStore()
    store.add(fakeNotification())
    const id = store.items[0].id

    store.markRead(id)

    expect(store.items[0].read).toBe(true)
    expect(store.unreadCount).toBe(0)
  })

  it('markAllRead marks every notification as read', () => {
    const store = useNotificationsStore()
    store.add(fakeNotification('info', 'A'))
    store.add(fakeNotification('error', 'B'))

    expect(store.unreadCount).toBe(2)

    store.markAllRead()

    expect(store.unreadCount).toBe(0)
    expect(store.items.every((n) => n.read)).toBe(true)
  })

  it('unreadCount reflects only unread items', () => {
    const store = useNotificationsStore()
    store.add(fakeNotification('info', 'A'))
    store.add(fakeNotification('info', 'B'))
    store.add(fakeNotification('info', 'C'))

    store.markRead(store.items[0].id)

    expect(store.unreadCount).toBe(2)
  })
})
