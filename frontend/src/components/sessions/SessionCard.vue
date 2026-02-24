<script setup lang="ts">
import type { StudySession } from '@/types/domain.types'

defineProps<{
  session: StudySession
}>()

const emit = defineEmits<{
  edit: [StudySession]
  delete: [StudySession]
}>()
</script>

<template>
  <div class="session-card">
    <div
      class="session-card__bar"
      :style="{ background: session.technology?.color ?? '#94a3b8' }"
    />
    <div class="session-card__content">
      <div class="session-card__main">
        <span class="session-card__tech">
          {{ session.technology?.name ?? 'Sem tecnologia' }}
        </span>
        <span class="session-card__duration">
          {{ session.duration_formatted ?? 'Em andamento' }}
        </span>
      </div>
      <div class="session-card__meta">
        <span>{{ new Date(session.started_at).toLocaleString('pt-BR') }}</span>
        <span v-if="session.mood" class="mood">Mood: {{ session.mood }}/5</span>
      </div>
      <p v-if="session.notes" class="session-card__notes">{{ session.notes }}</p>
      <div class="session-card__actions">
        <button type="button" class="btn btn--ghost" @click="emit('edit', session)">
          Editar
        </button>
        <button type="button" class="btn btn--ghost btn--danger" @click="emit('delete', session)">
          Excluir
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.session-card {
  background: #fff;
  border-radius: 0.5rem;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}
.session-card__bar {
  height: 4px;
}
.session-card__content {
  padding: 1rem;
}
.session-card__main {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 0.25rem;
}
.session-card__tech {
  font-weight: 600;
  color: #1e293b;
}
.session-card__duration {
  font-size: 0.875rem;
  color: #64748b;
}
.session-card__meta {
  font-size: 0.8125rem;
  color: #64748b;
  margin-bottom: 0.5rem;
}
.session-card__meta .mood {
  margin-left: 0.5rem;
}
.session-card__notes {
  font-size: 0.875rem;
  color: #475569;
  margin: 0.5rem 0 0;
  line-height: 1.4;
}
.session-card__actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.75rem;
}
.btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.8125rem;
  border: none;
  border-radius: 0.25rem;
  cursor: pointer;
  background: transparent;
  color: #64748b;
}
.btn--ghost:hover {
  background: #f1f5f9;
  color: #475569;
}
.btn--danger:hover {
  background: #fef2f2;
  color: #dc2626;
}
</style>
