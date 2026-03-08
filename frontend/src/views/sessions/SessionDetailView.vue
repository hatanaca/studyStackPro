<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getApiErrorMessage } from '@/api/client'
import { sessionsApi } from '@/api/modules/sessions.api'
import { formatDateTime } from '@/utils/formatters'
import PageView from '@/components/layout/PageView.vue'
import BaseBadge from '@/components/ui/BaseBadge.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import KeyValueList from '@/components/ui/KeyValueList.vue'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
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

const pageSubtitle = computed(() => {
  const s = session.value
  if (!s) return ''
  const tech = s.technology?.name ?? 'Estudo'
  const dur = s.duration_formatted ?? (s.duration_min != null ? `${s.duration_min} min` : '')
  return dur ? `${tech} · ${dur}` : tech
})

function goBack() {
  router.push({ name: 'sessions' })
}
</script>

<template>
  <PageView
    :breadcrumb="[
      { label: 'Dashboard', to: '/' },
      { label: 'Sessões', to: '/sessions' },
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
      <SkeletonLoader class="session-detail__skeleton" />
    </div>
    <template v-else-if="error">
      <ErrorCard
        :message="error"
        :on-retry="fetchSession"
      />
      <BaseButton
        variant="outline"
        class="session-detail__back"
        aria-label="Voltar para Sessões"
        @click="goBack"
      >
        Voltar para Sessões
      </BaseButton>
    </template>
    <template v-else-if="session">
      <div class="session-detail__actions">
        <BaseButton
          variant="ghost"
          size="sm"
          aria-label="Voltar para Sessões"
          @click="goBack"
        >
          ← Voltar
        </BaseButton>
      </div>
      <article
        class="session-detail__card"
        :style="session.technology?.color ? { '--session-tech-color': session.technology.color } : undefined"
      >
        <div
          v-if="session.technology?.color"
          class="session-detail__card-bar"
          aria-hidden="true"
        />
        <div class="session-detail__card-inner">
          <div
            v-if="session.technology"
            class="session-detail__badge-wrap"
          >
            <BaseBadge
              :label="session.technology.name"
              :color="session.technology.color"
            />
            <span
              v-if="session.duration_formatted || session.duration_min != null"
              class="session-detail__duration"
            >
              {{ session.duration_formatted ?? `${session.duration_min} min` }}
            </span>
          </div>
          <KeyValueList
            :items="sessionMetaItems"
            layout="row"
          />
        </div>
      </article>
    </template>
  </PageView>
</template>

<style scoped>
.session-detail__loading {
  padding: var(--spacing-md) 0;
}
.session-detail__skeleton {
  min-height: 8rem;
  border-radius: var(--radius-md);
}
.session-detail__actions {
  margin-bottom: var(--spacing-md);
}
.session-detail__back {
  margin-top: var(--spacing-md);
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
  height: 4px;
  background: var(--session-tech-color, var(--color-primary));
}
.session-detail__card-inner {
  padding: var(--spacing-lg) var(--spacing-xl);
}
.session-detail__badge-wrap {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  flex-wrap: wrap;
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--color-border);
}
.session-detail__duration {
  font-size: var(--text-lg);
  font-weight: 700;
  color: var(--color-text);
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.02em;
}
@media (max-width: 480px) {
  .session-detail__card-inner {
    padding: var(--spacing-md);
  }
}
</style>
