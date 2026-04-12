import { useSort } from '../useSort'

describe('useSort', () => {
  const items = [
    { id: '1', name: 'Charlie', score: 30 },
    { id: '2', name: 'Alice', score: 10 },
    { id: '3', name: 'Bob', score: 20 },
  ]

  it('retorna lista inalterada quando sortKey está vazio', () => {
    const { sort, sortKey } = useSort<{ id: string; name: string }>()
    expect(sortKey.value).toBe('')
    const result = sort(items)
    expect(result).toEqual([...items])
  })

  it('ordena por key ascendente', () => {
    const { setSort, sort } = useSort<{ id: string; name: string; score: number }>()
    setSort('name')
    const result = sort(items)
    expect(result.map(r => r.name)).toEqual(['Alice', 'Bob', 'Charlie'])
  })

  it('ordena por key descendente ao setSort duas vezes', () => {
    const { setSort, sort, sortOrder } = useSort<{ id: string; name: string }>()
    setSort('name')
    expect(sortOrder.value).toBe('asc')
    setSort('name')
    expect(sortOrder.value).toBe('desc')
    const result = sort(items)
    expect(result.map(r => r.name)).toEqual(['Charlie', 'Bob', 'Alice'])
  })

  it('reset restaura estado inicial', () => {
    const { setSort, reset, sortKey, sortOrder } = useSort<{ id: string }>({ initialKey: 'id', initialOrder: 'asc' })
    setSort('id')
    reset()
    expect(sortKey.value).toBe('id')
    expect(sortOrder.value).toBe('asc')
  })
})
