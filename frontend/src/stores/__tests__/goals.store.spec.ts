import { setActivePinia, createPinia } from 'pinia'
import { useGoalsStore } from '../goals.store'

describe('goals.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('localStorage', {
      getItem: vi.fn(() => null),
      setItem: vi.fn(),
      removeItem: vi.fn(),
    })
  })

  it('inicializa com lista vazia', () => {
    const store = useGoalsStore()
    expect(store.items).toEqual([])
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('activeGoals filtra apenas status active', async () => {
    const store = useGoalsStore()
    await store.createGoal({
      type: 'minutes_per_week',
      target_value: 300,
      start_date: '2025-01-01',
      end_date: null,
    })
    expect(store.activeGoals.length).toBe(1)
    expect(store.completedGoals.length).toBe(0)
  })

  it('createGoal adiciona item à lista', async () => {
    const store = useGoalsStore()
    const created = await store.createGoal({
      type: 'sessions_per_week',
      target_value: 5,
      start_date: '2025-01-01',
    })
    expect(created).toBeDefined()
    expect(created.type).toBe('sessions_per_week')
    expect(created.target_value).toBe(5)
    expect(store.items.length).toBe(1)
  })

  it('getProgress retorna 0 quando target é 0', () => {
    const store = useGoalsStore()
    const progress = store.getProgress({
      id: '1',
      user_id: 'u1',
      type: 'minutes_per_week',
      target_value: 0,
      current_value: 0,
      status: 'active',
      start_date: '2025-01-01',
      end_date: null,
      created_at: '',
      updated_at: '',
    })
    expect(progress).toBe(0)
  })

  it('getProgress retorna 100 quando current >= target', () => {
    const store = useGoalsStore()
    const progress = store.getProgress({
      id: '1',
      user_id: 'u1',
      type: 'minutes_per_week',
      target_value: 100,
      current_value: 150,
      status: 'active',
      start_date: '2025-01-01',
      end_date: null,
      created_at: '',
      updated_at: '',
    })
    expect(progress).toBe(100)
  })

  it('getProgress calcula percentual corretamente', () => {
    const store = useGoalsStore()
    const progress = store.getProgress({
      id: '1',
      user_id: 'u1',
      type: 'minutes_per_week',
      target_value: 200,
      current_value: 100,
      status: 'active',
      start_date: '2025-01-01',
      end_date: null,
      created_at: '',
      updated_at: '',
    })
    expect(progress).toBe(50)
  })
})
