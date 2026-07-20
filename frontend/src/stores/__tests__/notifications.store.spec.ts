import { setActivePinia, createPinia } from 'pinia'
import { useNotificationsStore } from '../notifications.store'
import type { NotificationType } from '../notifications.store'
import { notificationsApi } from '@/api/modules/notifications.api'

vi.mock('@/api/modules/notifications.api', () => ({
  notificationsApi: {
    list: vi.fn(),
    create: vi.fn(),
    markRead: vi.fn(),
    markAllRead: vi.fn(),
    delete: vi.fn(),
  },
}))

const fakeNotification = (type: NotificationType = 'info', title = 'Test') => ({
  type,
  title,
  message: `Message for ${title}`,
})

describe('notifications.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('initial state has empty notifications', () => {
    const store = useNotificationsStore()

    expect(store.items).toEqual([])
    expect(store.unreadCount).toBe(0)
  })

  it('add inserts notification at the beginning', async () => {
    vi.mocked(notificationsApi.create).mockResolvedValue({
      id: 'notif_1',
      type: 'success',
      title: 'First',
      message: 'Message for First',
      read: false,
      created_at: '2025-01-01T00:00:00Z',
      action_url: null,
      action_label: null,
    })

    const store = useNotificationsStore()
    await store.add(fakeNotification('success', 'First'))

    vi.mocked(notificationsApi.create).mockResolvedValue({
      id: 'notif_2',
      type: 'info',
      title: 'Second',
      message: 'Message for Second',
      read: false,
      created_at: '2025-01-02T00:00:00Z',
      action_url: null,
      action_label: null,
    })

    await store.add(fakeNotification('info', 'Second'))

    expect(store.items).toHaveLength(2)
    expect(store.items[0].title).toBe('Second')
    expect(store.items[1].title).toBe('First')
  })

  it('add generates unique id, read=false and created_at', async () => {
    vi.mocked(notificationsApi.create).mockResolvedValue({
      id: 'notif_1',
      type: 'info',
      title: 'Test',
      message: 'Message for Test',
      read: false,
      created_at: '2025-01-01T00:00:00Z',
      action_url: null,
      action_label: null,
    })

    const store = useNotificationsStore()
    await store.add(fakeNotification())

    const item = store.items[0]
    expect(item.id).toBe('notif_1')
    expect(item.read).toBe(false)
    expect(item.created_at).toBeTruthy()
  })

  it('add caps items at 50', async () => {
    vi.mocked(notificationsApi.create).mockImplementation(async (_, payload) => ({
      id: `notif_${Date.now()}_${Math.random()}`,
      type: payload.type,
      title: payload.title,
      message: payload.message ?? null,
      read: false,
      created_at: new Date().toISOString(),
      action_url: null,
      action_label: null,
    }))

    const store = useNotificationsStore()
    for (let i = 0; i < 55; i++) {
      await store.add(fakeNotification('info', `N${i}`))
    }

    expect(store.items).toHaveLength(50)
  })

  it('remove deletes notification by id', async () => {
    vi.mocked(notificationsApi.create).mockResolvedValue({
      id: 'notif_1',
      type: 'warning',
      title: 'To Remove',
      message: 'Message for To Remove',
      read: false,
      created_at: '2025-01-01T00:00:00Z',
      action_url: null,
      action_label: null,
    })
    vi.mocked(notificationsApi.delete).mockResolvedValue(undefined)

    const store = useNotificationsStore()
    await store.add(fakeNotification('warning', 'To Remove'))
    const id = store.items[0].id

    await store.remove(id)

    expect(store.items).toHaveLength(0)
  })

  it('remove is a no-op for unknown id', async () => {
    vi.mocked(notificationsApi.create).mockResolvedValue({
      id: 'notif_1',
      type: 'info',
      title: 'Test',
      message: 'Message for Test',
      read: false,
      created_at: '2025-01-01T00:00:00Z',
      action_url: null,
      action_label: null,
    })

    const store = useNotificationsStore()
    await store.add(fakeNotification())

    await store.remove('nonexistent')

    expect(store.items).toHaveLength(1)
  })

  it('markRead marks single notification as read', async () => {
    vi.mocked(notificationsApi.create).mockResolvedValue({
      id: 'notif_1',
      type: 'info',
      title: 'Test',
      message: 'Message for Test',
      read: false,
      created_at: '2025-01-01T00:00:00Z',
      action_url: null,
      action_label: null,
    })
    vi.mocked(notificationsApi.markRead).mockResolvedValue(undefined)

    const store = useNotificationsStore()
    await store.add(fakeNotification())
    const id = store.items[0].id

    await store.markRead(id)

    expect(store.items[0].read).toBe(true)
    expect(store.unreadCount).toBe(0)
  })

  it('markAllRead marks every notification as read', async () => {
    vi.mocked(notificationsApi.create)
      .mockResolvedValueOnce({
        id: 'notif_1',
        type: 'info',
        title: 'A',
        message: 'Message for A',
        read: false,
        created_at: '2025-01-01T00:00:00Z',
        action_url: null,
        action_label: null,
      })
      .mockResolvedValueOnce({
        id: 'notif_2',
        type: 'error',
        title: 'B',
        message: 'Message for B',
        read: false,
        created_at: '2025-01-02T00:00:00Z',
        action_url: null,
        action_label: null,
      })
    vi.mocked(notificationsApi.markAllRead).mockResolvedValue(undefined)

    const store = useNotificationsStore()
    await store.add(fakeNotification('info', 'A'))
    await store.add(fakeNotification('error', 'B'))

    expect(store.unreadCount).toBe(2)

    await store.markAllRead()

    expect(store.unreadCount).toBe(0)
    expect(store.items.every((n) => n.read)).toBe(true)
  })

  it('unreadCount reflects only unread items', async () => {
    vi.mocked(notificationsApi.create)
      .mockResolvedValueOnce({
        id: 'notif_1',
        type: 'info',
        title: 'A',
        message: 'Message for A',
        read: false,
        created_at: '2025-01-01T00:00:00Z',
        action_url: null,
        action_label: null,
      })
      .mockResolvedValueOnce({
        id: 'notif_2',
        type: 'info',
        title: 'B',
        message: 'Message for B',
        read: false,
        created_at: '2025-01-02T00:00:00Z',
        action_url: null,
        action_label: null,
      })
      .mockResolvedValueOnce({
        id: 'notif_3',
        type: 'info',
        title: 'C',
        message: 'Message for C',
        read: false,
        created_at: '2025-01-03T00:00:00Z',
        action_url: null,
        action_label: null,
      })
    vi.mocked(notificationsApi.markRead).mockResolvedValue(undefined)

    const store = useNotificationsStore()
    await store.add(fakeNotification('info', 'A'))
    await store.add(fakeNotification('info', 'B'))
    await store.add(fakeNotification('info', 'C'))

    await store.markRead(store.items[0].id)

    expect(store.unreadCount).toBe(2)
  })
})
