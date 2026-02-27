import { ref, computed } from 'vue'

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export function usePagination(initialPerPage = 15) {
  const currentPage = ref(1)
  const perPage = ref(initialPerPage)
  const total = ref(0)
  const lastPage = ref(1)

  const meta = computed<PaginationMeta>(() => ({
    current_page: currentPage.value,
    last_page: lastPage.value,
    per_page: perPage.value,
    total: total.value,
  }))

  const hasNextPage = computed(() => currentPage.value < lastPage.value)
  const hasPrevPage = computed(() => currentPage.value > 1)

  function setMeta(m: PaginationMeta) {
    currentPage.value = m.current_page
    lastPage.value = m.last_page
    perPage.value = m.per_page
    total.value = m.total
  }

  function nextPage() {
    if (hasNextPage.value) currentPage.value++
  }

  function prevPage() {
    if (hasPrevPage.value) currentPage.value--
  }

  function goToPage(page: number) {
    currentPage.value = Math.max(1, Math.min(page, lastPage.value))
  }

  function reset() {
    currentPage.value = 1
    total.value = 0
    lastPage.value = 1
  }

  return {
    currentPage,
    perPage,
    total,
    lastPage,
    meta,
    hasNextPage,
    hasPrevPage,
    setMeta,
    nextPage,
    prevPage,
    goToPage,
    reset,
  }
}
