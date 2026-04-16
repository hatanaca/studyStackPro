<script setup lang="ts">
import { ref, computed } from 'vue'
import PageView from '@/components/layout/PageView.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import Callout from '@/components/ui/Callout.vue'
import ErrorCard from '@/components/ui/ErrorCard.vue'
import Skeleton from 'primevue/skeleton'
import { sessionsApi } from '@/api/modules/sessions.api'
import { parseSessionsListResponse } from '@/types/schemas/api.schemas'
import { usePdfGenerator } from '@/composables/usePdfGenerator'
import type { StudySession } from '@/types/domain.types'

const dateRange = ref<{ start: string; end: string }>({ start: '', end: '' })
/** Falha de rede / API ao buscar sessões */
const fetchError = ref('')
/** Período válido mas sem sessões (não é erro técnico) */
const periodEmptyMessage = ref('')
const warning = ref('')
const isLoading = ref(false)
const { generating, generateReport } = usePdfGenerator()

const canGenerate = computed(
  () => dateRange.value.start && dateRange.value.end && !generating.value && !isLoading.value
)

const dateError = computed(() => {
  if (dateRange.value.start && dateRange.value.end && dateRange.value.start > dateRange.value.end) {
    return 'A data inicial deve ser anterior à data final.'
  }
  return ''
})

const PAGE_SIZE = 50

async function fetchAllSessions(dateFrom: string, dateTo: string): Promise<StudySession[]> {
  const allSessions: StudySession[] = []
  let currentPage = 1
  let lastPage = 1

  do {
    const res = await sessionsApi.list({
      date_from: dateFrom,
      date_to: dateTo,
      per_page: PAGE_SIZE,
      page: currentPage,
    })
    const parsed = parseSessionsListResponse(res.data)
    allSessions.push(...(parsed.data as StudySession[]))

    if (parsed.meta) {
      lastPage = parsed.meta.last_page
    }
    currentPage++
  } while (currentPage <= lastPage)

  return allSessions
}

async function onGenerate() {
  fetchError.value = ''
  periodEmptyMessage.value = ''
  warning.value = ''
  if (dateError.value) {
    return
  }
  isLoading.value = true
  try {
    const sessions = await fetchAllSessions(dateRange.value.start, dateRange.value.end)

    if (sessions.length === 0) {
      periodEmptyMessage.value =
        'Nenhuma sessão encontrada no período selecionado. Escolha outras datas ou registre sessões antes.'
      return
    }

    await generateReport({
      title: 'Relatório de Atividades — StudyTrack Pro',
      dateRange: dateRange.value,
      sessions,
    })
  } catch {
    fetchError.value = 'Não foi possível buscar as sessões. Verifique a conexão e tente de novo.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Relatórios' }]"
    title="Relatórios"
    subtitle="Gere relatórios de estudo por período em PDF."
    narrow
  >
    <template #hint>
      Selecione um período para gerar um relatório em PDF com resumo e detalhamento de sessões.
    </template>
    <section class="reports-card">
      <h2 class="reports-card__title">Relatório de atividades</h2>
      <div class="reports-form">
        <div class="reports-field">
          <label class="reports-label">Período</label>
          <div class="reports-dates">
            <input
              v-model="dateRange.start"
              type="date"
              class="reports-input"
              aria-label="Data inicial"
            />
            <span class="reports-sep">até</span>
            <input
              v-model="dateRange.end"
              type="date"
              class="reports-input"
              aria-label="Data final"
            />
          </div>
        </div>
        <BaseButton variant="primary" :disabled="!canGenerate || !!dateError" @click="onGenerate">
          {{ isLoading ? 'Buscando…' : generating ? 'Gerando…' : 'Gerar relatório PDF' }}
        </BaseButton>
      </div>
      <div
        v-if="isLoading"
        class="reports-loading"
        role="status"
        aria-live="polite"
        aria-label="Buscando sessões para o relatório"
      >
        <Skeleton height="3rem" class="reports-loading__skel" />
        <Skeleton height="3rem" class="reports-loading__skel" />
      </div>
      <Callout v-if="dateError" variant="warning" :title="dateError" />
      <ErrorCard
        v-else-if="fetchError"
        title="Erro ao montar o relatório"
        :message="fetchError"
        :on-retry="onGenerate"
      />
      <Callout v-else-if="periodEmptyMessage" variant="info" :title="periodEmptyMessage" />
      <Callout v-else-if="warning" variant="info" :title="warning" />
      <Callout v-else-if="!isLoading" variant="info" title="Relatório em PDF">
        O relatório inclui resumo geral (sessões, tempo, tecnologias) e uma tabela detalhada de
        todas as sessões no período. Selecione as datas e clique em gerar.
      </Callout>
    </section>
  </PageView>
</template>

<style scoped>
.reports-card {
  background: var(--surface-page-header-bg);
  border: var(--card-chrome-border);
  border-radius: var(--card-chrome-radius);
  padding: var(--spacing-xl);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
  box-shadow: var(--card-chrome-shadow);
}
.reports-card__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--text-lg);
  font-weight: 700;
  color: var(--color-text);
  line-height: var(--leading-tight);
  letter-spacing: var(--tracking-tight);
}
.reports-form {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: var(--spacing-lg);
  padding-bottom: var(--spacing-xl);
  border-bottom: 1px solid var(--color-border);
}
.reports-field {
  display: flex;
  flex-direction: column;
  gap: var(--form-field-gap);
  min-width: clamp(12rem, 40vw, 18rem);
  flex: 1 1 16rem;
}
.reports-label {
  font-size: var(--form-label-size);
  font-weight: var(--form-label-weight);
  letter-spacing: var(--form-label-tracking);
  color: var(--form-label-color);
}
.reports-dates {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.reports-input {
  min-width: clamp(8.75rem, 32vw, 12rem);
  min-height: var(--form-input-height);
  padding: var(--form-input-padding);
  border: 1px solid var(--form-input-border);
  border-radius: var(--form-input-radius);
  font-size: var(--form-input-font-size);
  font-family: inherit;
  background: var(--form-input-bg);
  color: var(--form-input-text);
  outline: none;
  box-sizing: border-box;
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.reports-input:focus-visible {
  border-color: var(--form-input-border-focus);
  box-shadow: var(--form-input-shadow-focus);
}
.reports-loading {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  padding-top: var(--spacing-sm);
}
.reports-loading__skel {
  border-radius: var(--radius-md);
}
.reports-sep {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
  flex-shrink: 0;
}
@media (max-width: 640px) {
  .reports-form {
    align-items: stretch;
  }
  .reports-field,
  .reports-dates,
  .reports-input {
    min-width: 0;
    width: 100%;
  }
  .reports-dates {
    flex-direction: column;
    align-items: stretch;
  }
  .reports-sep {
    display: none;
  }
}
</style>
