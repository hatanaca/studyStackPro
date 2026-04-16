<script setup lang="ts">
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useElementSize } from '@vueuse/core'
import type { StudySession } from '@/types/domain.types'
import {
  measureText,
  getSessionNotesFont,
  sessionCardNotesMaxWidth,
} from '@/composables/useTextMeasure'

const dateFormatter = new Intl.DateTimeFormat('pt-BR', {
  dateStyle: 'short',
  timeStyle: 'short',
})

const MAX_VISIBLE_LINES = 3

const props = defineProps<{
  session: StudySession
}>()

const emit = defineEmits<{
  edit: [StudySession]
  delete: [StudySession]
}>()

const contentRef = ref<HTMLElement>()
const { width: contentWidth } = useElementSize(contentRef)
const expanded = ref(false)

const notesLayout = computed(() => {
  const notes = props.session.notes
  const w = contentWidth.value
  if (!notes || w <= 0) return { lineCount: 0, height: 0 }
  const inner = sessionCardNotesMaxWidth(w)
  if (inner <= 0) return { lineCount: 0, height: 0 }
  const { font, lineHeightPx } = getSessionNotesFont()
  return measureText(notes, font, inner, lineHeightPx)
})

const needsTruncation = computed(() => notesLayout.value.lineCount > MAX_VISIBLE_LINES)

const formattedDate = computed(() => {
  try {
    return dateFormatter.format(new Date(props.session.started_at))
  } catch {
    return props.session.started_at
  }
})
</script>

<template>
  <div class="session-card">
    <div
      class="session-card__bar"
      :style="{ background: session.technology?.color ?? 'var(--color-text-muted)' }"
    />
    <div ref="contentRef" class="session-card__content">
      <div class="session-card__main">
        <span class="session-card__tech">
          {{ session.technology?.name ?? 'Sem tecnologia' }}
        </span>
        <span class="session-card__duration">
          {{ session.duration_formatted ?? 'Em andamento' }}
        </span>
      </div>
      <div class="session-card__meta">
        <span>{{ formattedDate }}</span>
        <span v-if="session.mood" class="mood">Mood: {{ session.mood }}/5</span>
      </div>
      <p
        v-if="session.notes"
        class="session-card__notes"
        :class="{ 'session-card__notes--clamped': needsTruncation && !expanded }"
      >
        {{ session.notes }}
      </p>
      <button
        v-if="needsTruncation"
        type="button"
        class="session-card__toggle"
        @click="expanded = !expanded"
      >
        {{ expanded ? 'Ver menos' : 'Ver mais' }}
      </button>
      <div class="session-card__actions">
        <RouterLink
          :to="{ name: 'session-detail', params: { id: session.id } }"
          class="btn btn--ghost"
        >
          Ver
        </RouterLink>
        <button type="button" class="btn btn--ghost" @click="emit('edit', session)">Editar</button>
        <button type="button" class="btn btn--ghost btn--danger" @click="emit('delete', session)">
          Excluir
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.session-card {
  background: var(--color-bg-card);
  border-radius: var(--card-chrome-radius);
  overflow: hidden;
  box-shadow: var(--card-chrome-shadow);
  border: var(--card-chrome-border);
  transition:
    box-shadow var(--duration-normal) var(--ease-out-expo),
    border-color var(--duration-fast) ease;
}
.session-card:hover {
  box-shadow: var(--shadow-card-hover);
  border-color: color-mix(in srgb, var(--color-primary) 25%, var(--color-border));
}
.session-card__bar {
  height: var(--spacing-xs);
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
  font-family: var(--font-display);
  font-weight: 700;
  font-size: var(--text-sm);
  line-height: var(--leading-snug);
  color: var(--color-text);
  letter-spacing: var(--tracking-tight);
}
.session-card__duration {
  font-size: var(--text-sm);
  line-height: var(--leading-snug);
  color: var(--color-text-muted);
  white-space: nowrap;
}
.session-card__meta {
  font-size: var(--text-xs);
  line-height: var(--leading-normal);
  color: var(--color-text-muted);
  margin-bottom: var(--spacing-sm);
}
.session-card__meta .mood {
  margin-left: var(--spacing-sm);
}
.session-card__notes {
  font-size: var(--text-sm);
  line-height: var(--leading-snug);
  color: var(--color-text-muted);
  margin: var(--spacing-sm) 0 0;
  /* Alinhado ao alvo do @chenglou/pretext: word-break normal + overflow-wrap break-word */
  overflow-wrap: break-word;
  word-break: normal;
}
.session-card__notes--clamped {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.session-card__toggle {
  padding: 0;
  border: none;
  background: transparent;
  color: var(--color-primary);
  font-size: var(--text-xs);
  font-weight: 500;
  cursor: pointer;
  margin-top: var(--spacing-xs);
  transition: color var(--duration-fast) ease;
}
.session-card__toggle:hover {
  color: var(--color-primary-hover);
}
.session-card__toggle:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
  border-radius: var(--radius-sm);
}
.session-card__actions {
  display: flex;
  gap: var(--spacing-xs);
  margin-top: var(--spacing-lg);
  flex-wrap: wrap;
}
.btn {
  padding: var(--spacing-xs) var(--spacing-sm);
  font-size: var(--text-xs);
  line-height: var(--leading-snug);
  font-weight: 500;
  border: 1px solid transparent;
  border-radius: var(--radius-sm);
  cursor: pointer;
  background: transparent;
  color: var(--color-text-muted);
  text-decoration: none;
  transition:
    background var(--duration-fast) ease,
    color var(--duration-fast) ease,
    border-color var(--duration-fast) ease;
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
