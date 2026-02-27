import { useTechnologiesStore } from '@/stores/technologies.store'

/**
 * Composable para CRUD de tecnologias e busca por trigrama
 */
export function useTechnologies() {
  const store = useTechnologiesStore()

  return {
    technologies: store.technologies,
    loading: store.loading,
    fetchTechnologies: store.fetchTechnologies,
    search: store.searchFromApi,
    create: store.createTechnology,
  }
}
