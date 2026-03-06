<script setup lang="ts">
import { RouterLink } from 'vue-router'
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
        <span
          v-if="session.mood"
          class="mood"
        >Mood: {{ session.mood }}/5</span>
      </div>
      <p
        v-if="session.notes"
        class="session-card__notes"
      >
        {{ session.notes }}
      </p>
      <div class="session-card__actions">
        <RouterLink
          :to="{ name: 'session-detail', params: { id: session.id } }"
          class="btn btn--ghost"
        >
          Ver
        </RouterLink>
        <button
          type="button"
          class="btn btn--ghost"
          @click="emit('edit', session)"
        >
          Editar
        </button>
        <button
          type="button"
          class="btn btn--ghost btn--danger"
          @click="emit('delete', session)"
        >
          Excluir
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.session-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  transition: box-shadow var(--duration-normal) var(--ease-in-out), border-color var(--duration-fast) ease;
}
.session-card:hover {
  box-shadow: var(--shadow-md);
  border-color: color-mix(in srgb, var(--color-primary) 25%, var(--color-border));
}
.session-card__bar {
  height: 3px;
}
.session-card__content {
  padding: var(--widget-padding);
}
.session-card__main {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-xs);
  flex-wrap: wrap;
}
.session-card__tech {
  font-weight: 600;
  font-size: var(--text-sm);
  color: var(--color-text);
}
.session-card__duration {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  white-space: nowrap;
}
.session-card__meta {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin-bottom: var(--spacing-sm);
}
.session-card__meta .mood {
  margin-left: var(--spacing-sm);
}
.session-card__notes {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: var(--spacing-sm) 0 0;
  line-height: 1.45;
  word-break: break-word;
}
.session-card__actions {
  display: flex;
  gap: var(--spacing-xs);
  margin-top: var(--spacing-md);
  flex-wrap: wrap;
}
.btn {
  padding: 0.3rem 0.6rem;
  font-size: var(--text-xs);
  font-weight: 500;
  border: 1px solid transparent;
  border-radius: var(--radius-sm);
  cursor: pointer;
  background: transparent;
  color: var(--color-text-muted);
  text-decoration: none;
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease, border-color var(--duration-fast) ease;
}
.btn--ghost:hover {
  background: var(--color-bg-soft);
  color: var(--color-text);
}
.btn--danger:hover {
  background: var(--color-error-soft);
  color: var(--color-error);
}
</style>
