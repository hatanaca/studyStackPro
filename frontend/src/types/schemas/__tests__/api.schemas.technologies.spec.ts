import { parseTechnologiesListResponse } from '../api.schemas'

describe('parseTechnologiesListResponse', () => {
  it('aceita icon e description null (formato Laravel)', () => {
    const raw = {
      success: true,
      data: [
        {
          id: '550e8400-e29b-41d4-a716-446655440000',
          name: 'Vue',
          slug: 'vue',
          color: '#42b883',
          icon: null,
          description: null,
          is_active: true,
          created_at: '2025-01-01T00:00:00+00:00',
          updated_at: '2025-01-02T00:00:00+00:00',
        },
      ],
    }
    const list = parseTechnologiesListResponse(raw)
    expect(list).toHaveLength(1)
    expect(list[0].name).toBe('Vue')
    expect(list[0].icon).toBeUndefined()
    expect(list[0].description).toBeUndefined()
  })

  it('trata data null como lista vazia', () => {
    const list = parseTechnologiesListResponse({ success: true, data: null })
    expect(list).toEqual([])
  })
})
