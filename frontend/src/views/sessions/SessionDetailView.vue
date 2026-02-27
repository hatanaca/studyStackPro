<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { sessionsApi } from '@/api/modules/sessions.api'
import { useToast } from '@/composables/useToast'
import { formatDateTime } from '@/utils/formatters'
import type { StudySession } from '@/types/domain.types'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const session = ref<StudySession | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const id = computed(() => route.params.id as string)

onMounted(async () => {
  try {
    const res = await sessionsApi.getOne(id.value)
    session.value = res.data.data
  } catch {
    error.value = 'Sessão não encontrada.'
    toast.error('Sessão não encontrada.')
    router.replace({ name: 'sessions' })
  } finally {
    loading.value = false
  }
})

function goBack() {
  router.push({ name: 'sessions' })
}
</script>

<template>
  <div class="session-detail">
    <div
      v-if="loading"
      class="session-detail__loading"
    >
      Carregando...
    </div>
    <div
      v-else-if="error"
      class="session-detail__error"
    >
      {{ error }}
    </div>
    <template v-else-if="session">
      <div class="session-detail__header">
        <button
          class="session-detail__back"
          @click="goBack"
        >
          ← Voltar
        </button>
      </div>
      <div class="session-detail__card">
        <h2 class="session-detail__title">
          Sessão de estudo
        </h2>
        <dl class="session-detail__meta">
          <dt>Tecnologia</dt>
          <dd>{{ session.technology?.name ?? 'N/A' }}</dd>
          <dt>Início</dt>
          <dd>{{ formatDateTime(session.started_at) }}</dd>
          <dt>Fim</dt>
          <dd>{{ session.ended_at ? formatDateTime(session.ended_at) : 'Em andamento' }}</dd>
          <dt>Duração</dt>
          <dd>{{ session.duration_formatted ?? 'Em andamento' }}</dd>
          <dt v-if="session.notes">
            Notas
          </dt>
          <dd v-if="session.notes">
            {{ session.notes }}
          </dd>
        </dl>
      </div>
    </template>
  </div>
</template>

<style scoped>
.session-detail {
  padding: 0;
}
.session-detail__header {
  margin-bottom: 1rem;
}
.session-detail__back {
  padding: 0.5rem 0.75rem;
  background: transparent;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  cursor: pointer;
  color: #64748b;
}
.session-detail__back:hover {
  background: #f1f5f9;
}
.session-detail__card {
  background: #fff;
  border-radius: 0.5rem;
  padding: 1.5rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.session-detail__title {
  font-size: 1.25rem;
  margin-bottom: 1rem;
}
.session-detail__meta {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.5rem 1.5rem;
}
.session-detail__meta dt {
  color: #64748b;
  font-size: 0.875rem;
}
.session-detail__loading,
.session-detail__error {
  padding: 2rem;
  text-align: center;
  color: #64748b;
}
</style>
