import { describe, it, expect, beforeEach } from 'vitest'
import { usePagination } from '../usePagination'

describe('usePagination', () => {
  beforeEach(() => {
    // Cada teste usa uma nova instância via usePagination()
  })

  it('retorna valores iniciais corretos', () => {
    const { currentPage, lastPage, total, perPage, hasNextPage, hasPrevPage } = usePagination(10)

    expect(currentPage.value).toBe(1)
    expect(lastPage.value).toBe(1)
    expect(total.value).toBe(0)
    expect(perPage.value).toBe(10)
    expect(hasNextPage.value).toBe(false)
    expect(hasPrevPage.value).toBe(false)
  })

  it('setMeta atualiza o estado', () => {
    const { setMeta, currentPage, lastPage, total, perPage } = usePagination()

    setMeta({
      current_page: 2,
      last_page: 5,
      per_page: 15,
      total: 72
    })

    expect(currentPage.value).toBe(2)
    expect(lastPage.value).toBe(5)
    expect(perPage.value).toBe(15)
    expect(total.value).toBe(72)
  })

  it('nextPage incrementa currentPage quando há próxima página', () => {
    const { setMeta, nextPage, currentPage } = usePagination()
    setMeta({ current_page: 1, last_page: 3, per_page: 15, total: 45 })

    nextPage()
    expect(currentPage.value).toBe(2)
    nextPage()
    expect(currentPage.value).toBe(3)
    nextPage()
    expect(currentPage.value).toBe(3)
  })

  it('prevPage decrementa currentPage quando há página anterior', () => {
    const { setMeta, prevPage, currentPage } = usePagination()
    setMeta({ current_page: 3, last_page: 3, per_page: 15, total: 45 })

    prevPage()
    expect(currentPage.value).toBe(2)
    prevPage()
    expect(currentPage.value).toBe(1)
    prevPage()
    expect(currentPage.value).toBe(1)
  })

  it('goToPage limita ao range válido', () => {
    const { setMeta, goToPage, currentPage } = usePagination()
    setMeta({ current_page: 1, last_page: 5, per_page: 10, total: 50 })

    goToPage(3)
    expect(currentPage.value).toBe(3)
    goToPage(0)
    expect(currentPage.value).toBe(1)
    goToPage(10)
    expect(currentPage.value).toBe(5)
  })

  it('reset volta ao estado inicial', () => {
    const { setMeta, reset, currentPage, lastPage, total } = usePagination()
    setMeta({ current_page: 3, last_page: 5, per_page: 10, total: 50 })

    reset()
    expect(currentPage.value).toBe(1)
    expect(lastPage.value).toBe(1)
    expect(total.value).toBe(0)
  })
})
