import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useTechnologiesStore } from '../technologies.store'
import { technologiesApi } from '@/api/modules/technologies.api'

vi.mock('@/api/modules/technologies.api', () => ({
  technologiesApi: {
    list: vi.fn(),
    search: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('technologies.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchTechnologies populates the technologies array', async () => {
    const mockTechs = [
      {
        id: '1',
        name: 'JavaScript',
        slug: 'javascript',
        color: '#f7df1e',
        is_active: true,
      },
      {
        id: '2',
        name: 'TypeScript',
        slug: 'typescript',
        color: '#3178c6',
        is_active: true,
      },
    ]
    vi.mocked(technologiesApi.list).mockResolvedValue({
      data: { success: true, data: mockTechs },
    } as never)

    const store = useTechnologiesStore()
    await store.fetchTechnologies()

    expect(store.technologies).toEqual(mockTechs)
    expect(technologiesApi.list).toHaveBeenCalled()
  })

  it('searchLocal filters by name (case insensitive)', () => {
    const store = useTechnologiesStore()
    store.setTechnologies([
      { id: '1', name: 'JavaScript', slug: 'javascript', color: '#f7df1e', is_active: true },
      { id: '2', name: 'TypeScript', slug: 'typescript', color: '#3178c6', is_active: true },
      { id: '3', name: 'Python', slug: 'python', color: '#3776ab', is_active: true },
    ])

    expect(store.searchLocal('SCRIPT')).toHaveLength(2)
    expect(store.searchLocal('script')).toEqual([
      { id: '1', name: 'JavaScript', slug: 'javascript', color: '#f7df1e', is_active: true },
      { id: '2', name: 'TypeScript', slug: 'typescript', color: '#3178c6', is_active: true },
    ])
  })

  it('searchLocal handles undefined slug without crashing', () => {
    const store = useTechnologiesStore()
    const techWithUndefinedSlug = {
      id: '1',
      name: 'Vue',
      slug: undefined as unknown as string,
      color: '#42b883',
      is_active: true,
    }
    store.setTechnologies([techWithUndefinedSlug])

    const result = store.searchLocal('vue')
    expect(result).toHaveLength(1)
    expect(result[0].name).toBe('Vue')
  })

  it('createTechnology adds to the list', async () => {
    const newTech = {
      id: 'new-1',
      name: 'Rust',
      slug: 'rust',
      color: '#000000',
      is_active: true,
    }
    vi.mocked(technologiesApi.create).mockResolvedValue({
      data: { success: true, data: newTech },
    } as never)

    const store = useTechnologiesStore()
    store.setTechnologies([
      { id: '1', name: 'JavaScript', slug: 'javascript', color: '#f7df1e', is_active: true },
    ])

    const created = await store.createTechnology({ name: 'Rust', color: '#000000' })

    expect(created).toEqual(newTech)
    expect(store.technologies).toHaveLength(2)
    expect(store.technologies.find((t) => t.id === 'new-1')).toEqual(newTech)
  })

  it('deleteTechnology removes from the list', async () => {
    vi.mocked(technologiesApi.delete).mockResolvedValue({} as never)

    const store = useTechnologiesStore()
    store.setTechnologies([
      { id: '1', name: 'JavaScript', slug: 'javascript', color: '#f7df1e', is_active: true },
      { id: '2', name: 'TypeScript', slug: 'typescript', color: '#3178c6', is_active: true },
    ])

    await store.deleteTechnology('1')

    expect(store.technologies).toHaveLength(1)
    expect(store.technologies[0].id).toBe('2')
    expect(technologiesApi.delete).toHaveBeenCalledWith('1')
  })

  it('isFresh returns true within cache TTL', async () => {
    const mockTechs = [{ id: '1', name: 'JS', slug: 'js', color: '#000', is_active: true }]
    vi.mocked(technologiesApi.list).mockResolvedValue({
      data: { success: true, data: mockTechs },
    } as never)

    const store = useTechnologiesStore()
    expect(store.isFresh).toBe(false)

    await store.fetchTechnologies()
    expect(store.isFresh).toBe(true)
  })
})
