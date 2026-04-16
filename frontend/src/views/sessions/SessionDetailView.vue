<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getApiErrorMessage } from '@/api/client'
import { sessionsApi } from '@/api/modules/sessions.api'
import { formatDateTime } from '@/utils/formatters'
import PageView from '@/components/layout/PageView.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'
import type { StudySession } from '@/types/domain.types'

const route = useRoute()
const router = useRouter()

const session = ref<StudySession | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

const id = computed(() => route.params.id as string)

const sessionMetaItems = computed(() => {
  const s = session.value
  if (!s) return []
  const duration = s.duration_formatted ?? (s.duration_min != null ? `${s.duration_min} min` : '—')
  return [
    { label: 'Tecnologia', value: s.technology?.name ?? '—' },
    { label: 'Início', value: s.started_at ? formatDateTime(s.started_at) : '—' },
    { label: 'Fim', value: s.ended_at ? formatDateTime(s.ended_at) : '—' },
    { label: 'Duração', value: duration },
    ...(s.notes ? [{ label: 'Notas', value: s.notes }] : []),
    ...(s.mood != null ? [{ label: 'Humor', value: String(s.mood) }] : []),
  ]
})

async function fetchSession() {
  if (!id.value) return
  loading.value = true
  error.value = null
  try {
    const res = await sessionsApi.getOne(id.value)
    if (res.data?.success && res.data?.data) {
      session.value = res.data.data
    } else {
      const msg =
        (res.data as { error?: { message?: string } })?.error?.message ?? 'Sessão não encontrada.'
      error.value = msg
    }
  } catch (err: unknown) {
    error.value = getApiErrorMessage(err) || 'Sessão não encontrada.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchSession()
})

watch(id, () => {
  fetchSession()
})

const pageSubtitle = computed(() => {
  const s = session.value
  if (!s) return ''
  const tech = s.technology?.name ?? 'Estudo'
  const dur = s.duration_formatted ?? (s.duration_min != null ? `${s.duration_min} min` : '')
  return dur ? `${tech} · ${dur}` : tech
})

function goBack() {
  if (session.value?.technology?.id) {
    router.push({ name: 'technology-detail', params: { id: session.value.technology.id } })
  } else {
    router.push({ name: 'technologies' })
  }
}
</script>

<template>
  <PageView
    :breadcrumb="[
      { label: 'Dashboard', to: '/' },
      { label: 'Tecnologias', to: '/technologies' },
      ...(session?.technology
        ? [{ label: session.technology.name, to: `/technologies/${session.technology.id}` }]
        : []),
      { label: 'Sessão de estudo' },
    ]"
    title="Sessão de estudo"
    :subtitle="pageSubtitle"
    narrow
  >
    <div
      v-if="loading"
      class="session-detail__loading"
      role="status"
      aria-live="polite"
      aria-label="Carregando sessão"
    >
      <Skeleton class="session-detail__skeleton" height="8rem" />
    </div>
    <template v-else-if="error">
      <ErrorCard :message="error" :on-retry="fetchSession" class="session-detail__message" />
      <Button
        label="Voltar para Sessões"
        severity="secondary"
        variant="outlined"
        class="session-detail__back"
        aria-label="Voltar para Sessões"
        @click="goBack"
      />
    </template>
    <template v-else-if="session">
      <div class="session-detail__actions">
        <Button
          label="Voltar para Sessões"
          icon="pi pi-arrow-left"
          icon-pos="left"
          severity="secondary"
          variant="text"
          size="small"
          aria-label="Voltar para Sessões"
          @click="goBack"
        />
      </div>
      <article
        class="session-detail__card"
        :style="
          session.technology?.color
            ? { '--session-tech-color': session.technology.color }
            : undefined
        "
      >
        <div v-if="session.technology?.color" class="session-detail__card-bar" aria-hidden="true" />
        <div class="session-detail__card-inner">
          <div v-if="session.technology" class="session-detail__badge-wrap">
            <Tag
              :value="session.technology.name"
              :style="{
                background: session.technology.color,
                color: 'var(--color-primary-contrast)',
              }"
            />
            <span
              v-if="session.duration_formatted || session.duration_min != null"
              class="session-detail__duration"
            >
              {{ session.duration_formatted ?? `${session.duration_min} min` }}
            </span>
          </div>
          <dl class="session-detail__meta key-value-list">
            <div v-for="item in sessionMetaItems" :key="item.label" class="key-value-list__row">
              <dt class="key-value-list__term">{{ item.label }}</dt>
              <dd class="key-value-list__value">{{ item.value }}</dd>
            </div>
          </dl>
        </div>
      </article>
    </template>
  </PageView>
</template>

<style scoped>
.session-detail__loading {
  padding: var(--spacing-lg) 0;
}
.session-detail__skeleton {
  min-height: calc(4 * var(--spacing-2xl));
  border-radius: var(--radius-md);
}
.session-detail__actions {
  margin-bottom: var(--spacing-lg);
}
.session-detail__message {
  margin-bottom: var(--spacing-lg);
}
.session-detail__back {
  margin-top: var(--spacing-lg);
}
.key-value-list {
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.key-value-list__row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
  align-items: baseline;
  gap: var(--spacing-lg);
  padding: var(--spacing-sm) 0;
  border-bottom: 1px solid var(--color-border);
}
.key-value-list__row:last-child {
  border-bottom: none;
}
.key-value-list__term {
  font-size: var(--text-xs);
  font-weight: 600;
  line-height: var(--leading-normal);
  letter-spacing: var(--tracking-tight);
  color: var(--color-text-muted);
  margin: 0;
}
.key-value-list__value {
  font-size: var(--text-sm);
  line-height: var(--leading-snug);
  letter-spacing: var(--tracking-normal);
  color: var(--color-text);
  margin: 0;
  word-break: break-word;
}
.session-detail__card {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  transition: box-shadow var(--duration-normal) var(--ease-in-out);
}
.session-detail__card:hover {
  box-shadow: var(--shadow-md);
}
.session-detail__card-bar {
  height: var(--spacing-xs);
  background: var(--session-tech-color, var(--color-primary));
}
.session-detail__card-inner {
  padding: var(--spacing-xl) var(--spacing-2xl);
}
.session-detail__badge-wrap {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  flex-wrap: wrap;
  margin-bottom: var(--spacing-xl);
  padding-bottom: var(--spacing-lg);
  border-bottom: 1px solid var(--color-border);
}
.session-detail__duration {
  font-size: var(--text-lg);
  font-weight: 700;
  line-height: var(--leading-tight);
  letter-spacing: var(--tracking-tight);
  color: var(--color-text);
  font-variant-numeric: tabular-nums;
}
@media (max-width: 640px) {
  .session-detail__card-inner {
    padding: var(--spacing-lg);
  }
}
</style>
