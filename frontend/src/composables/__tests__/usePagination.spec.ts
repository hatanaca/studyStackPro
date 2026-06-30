import { usePagination } from '../usePagination'

describe('usePagination', () => {
  it('initializes with default values', () => {
    const p = usePagination()
    expect(p.currentPage.value).toBe(1)
    expect(p.perPage.value).toBe(15)
    expect(p.total.value).toBe(0)
    expect(p.lastPage.value).toBe(1)
    expect(p.hasNextPage.value).toBe(false)
    expect(p.hasPrevPage.value).toBe(false)
  })

  it('initializes with custom perPage', () => {
    const p = usePagination(25)
    expect(p.perPage.value).toBe(25)
  })

  it('setMeta updates all values', () => {
    const p = usePagination()
    p.setMeta({ current_page: 3, last_page: 10, per_page: 20, total: 200 })

    expect(p.currentPage.value).toBe(3)
    expect(p.lastPage.value).toBe(10)
    expect(p.perPage.value).toBe(20)
    expect(p.total.value).toBe(200)
  })

  it('meta computed returns correct structure', () => {
    const p = usePagination()
    p.setMeta({ current_page: 2, last_page: 5, per_page: 10, total: 50 })

    expect(p.meta.value).toEqual({
      current_page: 2,
      last_page: 5,
      per_page: 10,
      total: 50,
    })
  })

  it('nextPage advances when has next', () => {
    const p = usePagination()
    p.setMeta({ current_page: 1, last_page: 5, per_page: 15, total: 75 })

    p.nextPage()
    expect(p.currentPage.value).toBe(2)
    expect(p.hasNextPage.value).toBe(true)
  })

  it('nextPage does not go beyond last page', () => {
    const p = usePagination()
    p.setMeta({ current_page: 5, last_page: 5, per_page: 15, total: 75 })

    p.nextPage()
    expect(p.currentPage.value).toBe(5)
    expect(p.hasNextPage.value).toBe(false)
  })

  it('prevPage goes back when has prev', () => {
    const p = usePagination()
    p.setMeta({ current_page: 3, last_page: 5, per_page: 15, total: 75 })

    p.prevPage()
    expect(p.currentPage.value).toBe(2)
    expect(p.hasPrevPage.value).toBe(true)
  })

  it('prevPage does not go below 1', () => {
    const p = usePagination()
    p.setMeta({ current_page: 1, last_page: 5, per_page: 15, total: 75 })

    p.prevPage()
    expect(p.currentPage.value).toBe(1)
    expect(p.hasPrevPage.value).toBe(false)
  })

  it('goToPage clamps between 1 and lastPage', () => {
    const p = usePagination()
    p.setMeta({ current_page: 1, last_page: 5, per_page: 15, total: 75 })

    p.goToPage(10)
    expect(p.currentPage.value).toBe(5)

    p.goToPage(-3)
    expect(p.currentPage.value).toBe(1)
  })

  it('reset resets to initial state', () => {
    const p = usePagination()
    p.setMeta({ current_page: 3, last_page: 5, per_page: 15, total: 75 })

    p.reset()
    expect(p.currentPage.value).toBe(1)
    expect(p.total.value).toBe(0)
    expect(p.lastPage.value).toBe(1)
  })

  it('hasNextPage and hasPrevPage compute correctly', () => {
    const p = usePagination()
    p.setMeta({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

    expect(p.hasNextPage.value).toBe(false)
    expect(p.hasPrevPage.value).toBe(false)

    p.setMeta({ current_page: 2, last_page: 5, per_page: 15, total: 75 })
    expect(p.hasNextPage.value).toBe(true)
    expect(p.hasPrevPage.value).toBe(true)
  })
})
