import { sessionsRoutes } from '../routes/sessions.routes'

describe('sessionsRoutes', () => {
  it('define a lista estática /sessions antes de sessions/:id para não confundir com id', () => {
    const paths = sessionsRoutes.map((r) => r.path)
    const idxList = paths.indexOf('sessions')
    const idxDetail = paths.indexOf('sessions/:id')
    expect(idxList).toBeGreaterThanOrEqual(0)
    expect(idxDetail).toBeGreaterThanOrEqual(0)
    expect(idxList).toBeLessThan(idxDetail)
  })

  it('expõe nome sessions para navegação programática', () => {
    const list = sessionsRoutes.find((r) => r.path === 'sessions')
    expect(list).toBeDefined()
    expect(list?.name).toBe('sessions')
  })
})
