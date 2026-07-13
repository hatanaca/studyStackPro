import { setActivePinia, createPinia } from 'pinia'
import { useGoalsStore } from '../goals.store'
import { goalsApi } from '@/api/modules/goals.api'

vi.mock('@/api/modules/goals.api', () => ({
  goalsApi: {
    list: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
  },
}))

const mockGoal = {
  id: 'goal-1',
  user_id: 'user-1',
  type: 'minutes_per_week' as const,
  target_value: 600,
  current_value: 300,
  status: 'active' as const,
  start_date: '2025-01-01',
  end_date: null,
  created_at: '2025-01-01T00:00:00Z',
  updated_at: '2025-01-01T00:00:00Z',
}

const mockCompletedGoal = {
  ...mockGoal,
  id: 'goal-2',
  status: 'completed' as const,
  current_value: 600,
}

describe('goals.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('initializes with empty items and default state', () => {
    const store = useGoalsStore()
    expect(store.items).toEqual([])
    expect(store.error).toBeNull()
  })

  it('fetchGoals loads goals from API', async () => {
    vi.mocked(goalsApi.list).mockReturnValue([mockGoal, mockCompletedGoal])

    const store = useGoalsStore()
    store.fetchGoals()

    expect(store.items).toHaveLength(2)
  })

  it('fetchGoals sets error on failure', async () => {
    vi.mocked(goalsApi.list).mockImplementation(() => {
      throw new Error('Network error')
    })

    const store = useGoalsStore()
    store.fetchGoals()

    expect(store.error).toBe('Network error')
  })

  it('createGoal adds goal to beginning of list', async () => {
    vi.mocked(goalsApi.create).mockReturnValue(mockGoal)

    const store = useGoalsStore()
    const result = store.createGoal({
      type: 'minutes_per_week',
      target_value: 600,
      start_date: '2025-01-01',
    })

    expect(store.items[0]).toEqual(mockGoal)
    expect(result).toEqual(mockGoal)
  })

  it('createGoal sets error and returns null on failure', async () => {
    vi.mocked(goalsApi.create).mockImplementation(() => {
      throw new Error('Validation error')
    })

    const store = useGoalsStore()
    const result = await store.createGoal({
      type: 'minutes_per_week',
      target_value: 600,
      start_date: '2025-01-01',
    })

    expect(result).toBeNull()
    expect(store.error).toBe('Validation error')
  })

  it('updateGoal updates goal in list', async () => {
    vi.mocked(goalsApi.update).mockReturnValue({ ...mockGoal, current_value: 450 })

    const store = useGoalsStore()
    store.items = [mockGoal]
    store.updateGoal('goal-1', { target_value: 600 })

    expect(store.items[0].current_value).toBe(450)
  })

  it('updateGoal does nothing if goal not found', async () => {
    vi.mocked(goalsApi.update).mockReturnValue(mockGoal)

    const store = useGoalsStore()
    store.items = []
    store.updateGoal('goal-1', { target_value: 600 })

    expect(store.items).toHaveLength(0)
  })

  it('deleteGoal removes goal from list', async () => {
    vi.mocked(goalsApi.delete).mockReturnValue(undefined)

    const store = useGoalsStore()
    store.items = [mockGoal, mockCompletedGoal]
    store.deleteGoal('goal-1')

    expect(store.items).toHaveLength(1)
    expect(store.items[0].id).toBe('goal-2')
  })

  it('deleteGoal sets error on failure', async () => {
    vi.mocked(goalsApi.delete).mockImplementation(() => {
      throw new Error('Delete failed')
    })

    const store = useGoalsStore()
    store.items = [mockGoal]

    const result = await store.deleteGoal('goal-1')
    expect(result).toBe(false)
    expect(store.error).toBe('Delete failed')
  })

  it('activeGoals filters only active goals', () => {
    const store = useGoalsStore()
    store.items = [mockGoal, mockCompletedGoal]

    expect(store.activeGoals).toHaveLength(1)
    expect(store.activeGoals[0].status).toBe('active')
  })

  it('completedGoals filters only completed goals', () => {
    const store = useGoalsStore()
    store.items = [mockGoal, mockCompletedGoal]

    expect(store.completedGoals).toHaveLength(1)
    expect(store.completedGoals[0].status).toBe('completed')
  })

  it('getProgress calculates correct percentage', () => {
    const store = useGoalsStore()
    expect(store.getProgress(mockGoal)).toBe(50)
  })

  it('getProgress caps at 100', () => {
    const store = useGoalsStore()
    const overGoal = { ...mockGoal, current_value: 1000, target_value: 600 }
    expect(store.getProgress(overGoal)).toBe(100)
  })

  it('getProgress returns 0 when target is 0', () => {
    const store = useGoalsStore()
    const zeroGoal = { ...mockGoal, target_value: 0 }
    expect(store.getProgress(zeroGoal)).toBe(0)
  })

  it('getProgress uses override value when provided', () => {
    const store = useGoalsStore()
    expect(store.getProgress(mockGoal, 120)).toBe(20)
  })

  it('getActiveWeeklyMinutesGoal returns first active minutes_per_week goal', () => {
    const store = useGoalsStore()
    store.items = [mockCompletedGoal, mockGoal]

    const result = store.getActiveWeeklyMinutesGoal()
    expect(result?.id).toBe('goal-1')
  })

  it('getActiveWeeklyMinutesGoal returns null when none found', () => {
    const store = useGoalsStore()
    store.items = [mockCompletedGoal]

    expect(store.getActiveWeeklyMinutesGoal()).toBeNull()
  })
})
