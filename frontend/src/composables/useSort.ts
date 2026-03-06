import { ref, computed } from 'vue'

export type SortOrder = 'asc' | 'desc'

export interface UseSortOptions<T> {
  initialKey?: keyof T | ''
  initialOrder?: SortOrder
  compare?: (a: T, b: T, key: keyof T, order: SortOrder) => number
}

const defaultCompare = <T>(a: T, b: T, key: keyof T, order: SortOrder): number => {
  const aVal = a[key]
  const bVal = b[key]
  if (aVal == null && bVal == null) return 0
  if (aVal == null) return order === 'asc' ? 1 : -1
  if (bVal == null) return order === 'asc' ? -1 : 1
  const cmp = String(aVal).localeCompare(String(bVal), undefined, { numeric: true })
  return order === 'asc' ? cmp : -cmp
}

/**
 * Ordenação reativa para listas. Expõe key, order e uma função sort(list).
 */
export function useSort<T extends Record<string, unknown>>(options: UseSortOptions<T> = {}) {
  const sortKey = ref<keyof T | ''>(options.initialKey ?? '')
  const sortOrder = ref<SortOrder>(options.initialOrder ?? 'asc')
  const compareFn = options.compare ?? defaultCompare

  const sorted = computed(() => ({
    key: sortKey.value,
    order: sortOrder.value,
  }))

  function sort(items: T[]): T[] {
    if (!sortKey.value) return [...items]
    return [...items].sort((a, b) => compareFn(a, b, sortKey.value as keyof T, sortOrder.value))
  }

  function setSort(key: keyof T) {
    if (sortKey.value === key) {
      sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortKey.value = key
      sortOrder.value = 'asc'
    }
  }

  function reset() {
    sortKey.value = (options.initialKey ?? '') as keyof T | ''
    sortOrder.value = options.initialOrder ?? 'asc'
  }

  return { sortKey, sortOrder, sorted, sort, setSort, reset }
}
