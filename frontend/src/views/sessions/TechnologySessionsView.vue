<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'
import PageView from '@/components/layout/PageView.vue'
import { sessionsApi } from '@/api/modules/sessions.api'
import { technologiesApi } from '@/api/modules/technologies.api'
import { formatDate } from '@/utils/formatters'
import type { StudySession } from '@/types/domain.types'
import type { Technology } from '@/types/domain.types'

const route = useRoute()
const router = useRouter()

const technology = ref<Technology | null>(null)
const sessions = ref<StudySession[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

const id = computed(() => route.params.id as string)

interface DayRow {
  date: string
  totalMinutes: number
}

const tableRows = computed((): DayRow[] => {
  const byDay = new Map<string, number>()
  for (const s of sessions.value) {
    const dateStr = s.started_at?.slice(0, 10) ?? ''
    if (!dateStr) continue
    const current = byDay.get(dateStr) ?? 0
    byDay.set(dateStr, current + (s.duration_min ?? 0))
  }
  return Array.from(byDay.entries())
    .map(([date, totalMinutes]) => ({ date, totalMinutes }))
    .sort((a, b) => b.date.localeCompare(a.date))
})

const breadcrumbItems = computed(() => [
  { label: 'Sessões', to: '/sessions' },
  { label: 'Por tecnologia', to: '/sessions' },
  { label: technology.value?.name ?? 'Detalhes', to: undefined },
])

async function loadTechnology() {
  if (!id.value) return
  try {
    const { data } = await technologiesApi.getOne(id.value)
    if (data.success && data.data) technology.value = data.data
    else error.value = 'Tecnologia não encontrada.'
  } catch {
    error.value = 'Tecnologia não encontrada.'
  }
}

async function loadSessions() {
  if (!id.value) return
  try {
    const { data } = await sessionsApi.list({
      technology_id: id.value,
      per_page: 500,
    })
    if (data.success && Array.isArray(data.data)) {
      sessions.value = data.data
    } else {
      sessions.value = []
    }
  } catch {
    sessions.value = []
  }
}

onMounted(async () => {
  loading.value = true
  error.value = null
  await loadTechnology()
  if (technology.value) await loadSessions()
  loading.value = false
})

watch(id, async () => {
  await loadTechnology()
  if (technology.value) await loadSessions()
})

function goBack() {
  router.push({ name: 'sessions' })
}

async function retry() {
  loading.value = true
  error.value = null
  await loadTechnology()
  if (technology.value) await loadSessions()
  loading.value = false
}

function formatMinutes(m: number) {
  if (m < 60) return `${m} min`
  const h = Math.floor(m / 60)
  const min = m % 60
  return min > 0 ? `${h}h ${min}min` : `${h}h`
}
</script>

<template>
  <PageView
    :breadcrumb="breadcrumbItems"
    class="technology-sessions-view"
  >
    <div
      v-if="loading"
      class="technology-sessions-view__loading"
      role="status"
      aria-live="polite"
      aria-label="Carregando"
    >
      <Skeleton class="technology-sessions-view__skeleton" height="6rem" />
      <Skeleton class="technology-sessions-view__skeleton" height="8rem" />
    </div>
    <div
      v-else-if="error"
      class="technology-sessions-view__error"
    >
      <p>{{ error }}</p>
      <div class="technology-sessions-view__error-actions">
        <Button
          label="Tentar novamente"
          @click="retry"
        />
        <Button
          label="Voltar"
          severity="secondary"
          variant="outlined"
          @click="goBack"
        />
      </div>
    </div>
    <template v-else-if="technology">
      <header class="technology-sessions-view__header">
        <button
          type="button"
          class="technology-sessions-view__back"
          @click="goBack"
        >
          ← Voltar
        </button>
        <h1 class="technology-sessions-view__title">
          Sessões por dia — {{ technology.name }}
        </h1>
      </header>

      <Card class="technology-sessions-view__card">
        <div class="technology-sessions-view__table-wrap scroll-pretty">
          <table class="technology-sessions-view__table">
            <thead>
              <tr>
                <th>Data</th>
                <th>Tempo estudado</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in tableRows"
                :key="row.date"
              >
                <td>{{ formatDate(row.date) }}</td>
                <td>{{ formatMinutes(row.totalMinutes) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p
          v-if="!loading && !tableRows.length"
          class="technology-sessions-view__empty"
        >
          Nenhuma sessão registrada para esta tecnologia.
        </p>
      </Card>
    </template>
  </PageView>
</template>

<style scoped>
.technology-sessions-view {
  max-width: 800px;
}
.technology-sessions-view__loading,
.technology-sessions-view__error {
  padding: var(--spacing-2xl);
  text-align: center;
  color: var(--color-text-muted);
  background: color-mix(in srgb, var(--color-bg-soft) 80%, var(--color-bg-card));
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  line-height: var(--leading-normal);
}
.technology-sessions-view__error p {
  margin: 0 0 var(--spacing-lg);
}
.technology-sessions-view__error-actions {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.technology-sessions-view__skeleton {
  border-radius: var(--radius-md);
  margin-bottom: var(--spacing-lg);
}
.technology-sessions-view__header {
  margin-bottom: var(--spacing-xl);
  padding: var(--spacing-sm) 0;
}
.technology-sessions-view__back {
  background: none;
  border: none;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  font-weight: 500;
  cursor: pointer;
  margin-bottom: var(--spacing-sm);
  padding: var(--spacing-xs) 0;
  min-height: var(--input-height-sm);
  transition: color var(--duration-fast) ease;
}
.technology-sessions-view__back:hover {
  color: var(--color-primary);
}
.technology-sessions-view__title {
  font-size: var(--text-xl);
  font-weight: 700;
  color: var(--color-text);
  margin: 0;
}
.technology-sessions-view__card {
  padding: 0;
  overflow: visible;
  border-radius: var(--radius-md);
}
.technology-sessions-view__table-wrap {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}
.technology-sessions-view__table {
  width: 100%;
  min-width: 280px;
  border-collapse: collapse;
  font-size: var(--text-sm);
  line-height: var(--leading-normal);
}
.technology-sessions-view__table th,
.technology-sessions-view__table td {
  padding: var(--spacing-md) var(--spacing-lg);
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}
@media (max-width: 640px) {
  .technology-sessions-view__table th,
  .technology-sessions-view__table td {
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: var(--text-xs);
  }
}
.technology-sessions-view__table th {
  font-weight: 600;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  background: var(--color-bg-soft);
}
.technology-sessions-view__table tbody tr {
  transition: background var(--duration-fast) ease;
}
.technology-sessions-view__table tbody tr:hover {
  background: var(--color-bg-soft);
}
.technology-sessions-view__empty {
  margin: 0;
  padding: var(--spacing-2xl);
  text-align: center;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  line-height: var(--leading-normal);
}
</style>
