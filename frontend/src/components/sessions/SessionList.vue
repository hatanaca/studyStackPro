<script setup lang="ts">
import { ref, watch } from 'vue'
import SessionCard from './SessionCard.vue'
import SessionFilters from './SessionFilters.vue'
import { apiClient } from '@/api/client'
import type { StudySession } from '@/types/domain.types'
import type { ApiResponse } from '@/types/api.types'

const filters = ref<{
  technology_id?: string
  date_from?: string
  date_to?: string
  min_duration?: number
  mood?: number
}>({})

const sessions = ref<StudySession[]>([])
const meta = ref<{ current_page: number; last_page: number; per_page: number; total: number } | null>(null)
const loading = ref(false)
const currentPage = ref(1)

async function fetchSessions() {
  loading.value = true
  try {
    const params: Record<string, string | number | undefined> = {
      page: currentPage.value,
      per_page: 15,
      ...filters.value
    }
    if (filters.value.technology_id) params.technology_id = filters.value.technology_id
    if (filters.value.date_from) params.date_from = filters.value.date_from
    if (filters.value.date_to) params.date_to = filters.value.date_to
    if (filters.value.min_duration != null) params.min_duration = filters.value.min_duration
    if (filters.value.mood != null) params.mood = filters.value.mood

    const { data } = await apiClient.get<ApiResponse<StudySession[]> & { meta?: typeof meta.value }>(
      '/study-sessions',
      { params }
    )
    if (data.success && Array.isArray(data.data)) {
      sessions.value = data.data
      meta.value = (data as { meta?: typeof meta.value }).meta ?? null
    }
  } finally {
    loading.value = false
  }
}

watch(
  () => [filters.value, currentPage.value],
  () => fetchSessions(),
  { deep: true }
)

fetchSessions()

function onFiltersChange() {
  currentPage.value = 1
  fetchSessions()
}
</script>

<template>
  <div class="session-list">
    <h2>Sessões de estudo</h2>
    <SessionFilters v-model="filters" @change="onFiltersChange" />
    <div v-if="loading" class="loading">Carregando...</div>
    <div v-else-if="sessions.length" class="session-list__grid">
      <SessionCard
        v-for="s in sessions"
        :key="s.id"
        :session="s"
        @edit="() => {}"
        @delete="() => {}"
      />
    </div>
    <p v-else class="empty">Nenhuma sessão registrada.</p>
  </div>
</template>

<style scoped>
.session-list h2 {
  font-size: 1.25rem;
  margin-bottom: 1rem;
}
.session-list__grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.loading,
.empty {
  color: #64748b;
  margin-top: 1rem;
}
</style>
