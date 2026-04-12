import { defineStore } from 'pinia'
import { ref, shallowRef, computed } from 'vue'
import type { Technology } from '@/types/domain.types'
import { technologiesApi } from '@/api/modules/technologies.api'

/** TTL para considerar lista de tecnologias "fresca" (1 min) */
const CACHE_FRESH_MS = 60_000

/** Store de tecnologias: CRUD, busca local e na API */
export const useTechnologiesStore = defineStore('technologies', () => {
  const technologies = shallowRef<Technology[]>([])
  const lastFetchedAt = ref<number | null>(null)
  const loading = ref(false)

  /** True se fetch foi feito há menos de CACHE_FRESH_MS */
  const isFresh = computed(() => {
    if (!lastFetchedAt.value) return false
    return Date.now() - lastFetchedAt.value < CACHE_FRESH_MS
  })

  /** Atualiza lista a partir de dados externos (ex.: TanStack Query) */
  function setTechnologies(list: Technology[]) {
    technologies.value = list
    lastFetchedAt.value = Date.now()
  }

  /** Busca tecnologias na API. Usa cache se isFresh e !force */
  async function fetchTechnologies(force = false) {
    if (isFresh.value && !force) return technologies.value
    loading.value = true
    try {
      const { data } = await technologiesApi.list()
      if (data.success && Array.isArray(data.data)) {
        technologies.value = data.data
        lastFetchedAt.value = Date.now()
      }
      return technologies.value
    } finally {
      loading.value = false
    }
  }

  /** Busca local na lista em memória (name ou slug) */
  function searchLocal(query: string): Technology[] {
    const q = query.trim().toLowerCase()
    if (!q) return technologies.value
    return technologies.value.filter(
      (t) =>
        t.name.toLowerCase().includes(q) || t.slug?.toLowerCase().includes(q)
    )
  }

  async function searchFromApi(query: string, limit = 10): Promise<Technology[]> {
    const q = query.trim()
    if (q.length < 2) return []
    const { data } = await technologiesApi.search(q, limit)
    if (data.success && Array.isArray(data.data)) return data.data
    return []
  }

  /** Cria tecnologia e atualiza store */
  async function createTechnology(data: {
    name: string
    color?: string
    icon?: string
    description?: string
  }) {
    const { data: res } = await technologiesApi.create(data)
    if (res.success && res.data) {
      technologies.value = [...technologies.value, res.data]
      lastFetchedAt.value = Date.now()
      return res.data
    }
    throw new Error('Falha ao criar tecnologia')
  }

  async function updateTechnology(
    id: string,
    data: Partial<{ name: string; color: string; icon: string; description: string }>
  ) {
    const { data: res } = await technologiesApi.update(id, data)
    if (res.success && res.data) {
      const idx = technologies.value.findIndex((t) => t.id === id)
      if (idx >= 0) {
        technologies.value = [
          ...technologies.value.slice(0, idx),
          res.data,
          ...technologies.value.slice(idx + 1)
        ]
      } else {
        technologies.value = [...technologies.value, res.data]
      }
      lastFetchedAt.value = Date.now()
      return res.data
    }
    throw new Error('Falha ao atualizar tecnologia')
  }

  /** Remove tecnologia (soft delete na API) e filtra da lista local */
  async function deleteTechnology(id: string) {
    await technologiesApi.delete(id)
    technologies.value = technologies.value.filter((t) => t.id !== id)
    lastFetchedAt.value = Date.now()
  }

  /** Invalida cache (próximo fetch não usará cache) */
  function invalidate() {
    lastFetchedAt.value = null
  }

  return {
    technologies,
    loading,
    isFresh,
    setTechnologies,
    fetchTechnologies,
    searchLocal,
    searchFromApi,
    createTechnology,
    updateTechnology,
    deleteTechnology,
    invalidate
  }
})
