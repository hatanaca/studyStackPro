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
  /** Na página de uma tecnologia: só o tema estudado (título), sem repetir o nome da tecnologia no cartão. */
  topicOnly?: boolean
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

const accentColor = computed(() => props.session.technology?.color ?? 'var(--color-text-muted)')

const displayTitle = computed(() => {
  if (props.topicOnly) {
    return props.session.title?.trim() || 'Sem tema definido'
  }
  return (
    props.session.title?.trim() ||
    props.session.technology?.name ||
    'Sem tecnologia'
  )
})

const showTechUnderTitle = computed(
  () =>
    !props.topicOnly &&
    !!(props.session.title?.trim() && props.session.technology?.name),
)
</script>

<template>
  <div class="session-card">
    <div class="session-card__accent" :style="{ background: accentColor }" aria-hidden="true" />
    <div ref="contentRef" class="session-card__body">
      <RouterLink
        :to="{ name: 'session-detail', params: { id: session.id } }"
        class="session-card__icon"
        :title="'Ver sessão'"
      >
        <span class="session-card__icon-inner" aria-hidden="true">📚</span>
      </RouterLink>
      <div class="session-card__main">
        <div class="session-card__title-row">
          <RouterLink
            :to="{ name: 'session-detail', params: { id: session.id } }"
            class="session-card__title"
          >
            {{ displayTitle }}
          </RouterLink>
          <span class="session-card__date-sep" aria-hidden="true">·</span>
          <time class="session-card__date" :datetime="session.started_at">{{ formattedDate }}</time>
        </div>
        <p v-if="showTechUnderTitle" class="session-card__tech-under">
          {{ session.technology?.name }}
        </p>
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
        <div class="session-card__links">
          <button type="button" class="session-card__link" @click="emit('edit', session)">Editar</button>
        </div>
      </div>
      <div class="session-card__side">
        <span class="session-card__duration-strong">
          {{ session.duration_formatted ?? (session.ended_at == null ? 'Em andamento' : '—') }}
        </span>
        <button
          type="button"
          class="session-card__delete"
          aria-label="Excluir sessão"
          @click="emit('delete', session)"
        >
          ✕
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.session-card {
  position: relative;
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  box-shadow: none;
  overflow: hidden;
  transition:
    border-color var(--duration-fast) ease,
    background var(--duration-fast) ease;
}
.session-card:hover {
  border-color: color-mix(in srgb, var(--color-primary) 22%, var(--color-border));
  background: color-mix(in srgb, var(--color-bg-soft) 35%, var(--color-bg-card));
}
.session-card__accent {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  border-radius: var(--radius-md) 0 0 var(--radius-md);
}
.session-card__body {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-sm) var(--spacing-sm) calc(var(--spacing-sm) + 3px);
  min-width: 0;
}
.session-card__icon {
  flex-shrink: 0;
  align-self: flex-start;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: var(--radius-sm);
  background: var(--color-bg-soft);
  border: 1px solid var(--color-border);
  text-decoration: none;
  transition:
    background var(--duration-fast) ease,
    border-color var(--duration-fast) ease;
}
.session-card__icon:hover {
  background: color-mix(in srgb, var(--color-bg-soft) 70%, var(--color-primary));
  border-color: color-mix(in srgb, var(--color-primary) 35%, var(--color-border));
}
.session-card__icon-inner {
  font-size: 0.95rem;
  line-height: 1;
}
.session-card__main {
  flex: 1;
  min-width: 0;
}
.session-card__title-row {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 0.25rem 0.35rem;
  margin-bottom: 0;
}
.session-card__title {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: var(--text-xs);
  color: var(--color-text);
  text-decoration: none;
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-snug);
  min-width: 0;
}
.session-card__date-sep {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  user-select: none;
}
.session-card__title:hover {
  color: var(--color-primary);
}
.session-card__tech-under {
  margin: 2px 0 0;
  font-size: 10px;
  font-weight: 600;
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
}
.session-card__date {
  margin: 0;
  font-size: 10px;
  font-weight: 500;
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
  white-space: nowrap;
}
.session-card__notes {
  font-size: var(--text-xs);
  line-height: var(--leading-snug);
  color: var(--color-text-muted);
  margin: var(--spacing-2xs) 0 0;
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
.session-card__links {
  margin-top: var(--spacing-xs);
}
.session-card__link {
  padding: 0;
  border: none;
  background: none;
  font-size: var(--text-xs);
  font-weight: 500;
  color: var(--color-text-muted);
  cursor: pointer;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.session-card__link:hover {
  color: var(--color-primary);
}
.session-card__side {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  justify-content: flex-start;
  gap: var(--spacing-xs);
  min-width: 2.75rem;
}
.session-card__duration-strong {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: var(--text-xs);
  color: var(--color-text);
  white-space: nowrap;
}
.session-card__delete {
  width: 1.75rem;
  height: 1.75rem;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-soft);
  color: var(--color-text-muted);
  font-size: var(--text-xs);
  line-height: 1;
  cursor: pointer;
  transition:
    border-color var(--duration-fast) ease,
    color var(--duration-fast) ease,
    background var(--duration-fast) ease;
}
.session-card__delete:hover {
  border-color: var(--color-error);
  color: var(--color-error);
  background: var(--color-error-soft);
}
.session-card__delete:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
</style>
