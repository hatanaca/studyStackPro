<script setup lang="ts">
import { ref, computed } from 'vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseDateRangePicker from '@/components/ui/BaseDateRangePicker.vue'
import FormSection from '@/components/ui/FormSection.vue'
import PageView from '@/components/layout/PageView.vue'
import { useAnalyticsStore } from '@/stores/analytics.store'

const analyticsStore = useAnalyticsStore()
const dateRange = ref<{ start: string; end: string } | null>(null)
const format = ref<'csv' | 'json'>('csv')
const exporting = ref(false)
const exportDone = ref(false)
const exportError = ref<string | null>(null)

const canExport = computed(() => !!dateRange.value?.start && !!dateRange.value?.end)

/** Filtra e ordena dias do time series dentro do intervalo selecionado */
function getDataInRange() {
  const start = dateRange.value?.start
  const end = dateRange.value?.end
  if (!start || !end) return []
  const all = analyticsStore.timeSeriesData['30d'] ?? analyticsStore.timeSeriesData['7d'] ?? []
  return all
    .filter(d => d.date >= start && d.date <= end)
    .sort((a, b) => a.date.localeCompare(b.date))
}

function buildCSV(rows: { date: string; total_minutes: number; session_count?: number }[]): string {
  const header = 'Data,Minutos,Sessões'
  const lines = rows.map(r => `${r.date},${r.total_minutes ?? 0},${r.session_count ?? 0}`)
  return [header, ...lines].join('\n')
}

function buildJSON(rows: { date: string; total_minutes: number; session_count?: number }[]): string {
  return JSON.stringify({ exported_at: new Date().toISOString(), period: dateRange.value, data: rows }, null, 2)
}

function downloadBlob(blob: Blob, filename: string) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}

async function doExport() {
  if (!dateRange.value || !canExport.value) return
  exporting.value = true
  exportDone.value = false
  exportError.value = null
  try {
    const rows = getDataInRange()
    const filename = `studytrack-${dateRange.value.start}-${dateRange.value.end}.${format.value}`
    if (format.value === 'csv') {
      const csv = buildCSV(rows)
      downloadBlob(new Blob([csv], { type: 'text/csv;charset=utf-8' }), filename)
    } else {
      const json = buildJSON(rows)
      downloadBlob(new Blob([json], { type: 'application/json' }), filename)
    }
    exportDone.value = true
  } catch (e) {
    exportError.value = e instanceof Error ? e.message : 'Erro ao gerar exportação'
  } finally {
    exporting.value = false
  }
}
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Exportar' }]"
    title="Exportar dados"
    subtitle="Exporte suas sessões e métricas por período para análise externa."
    narrow
  >
    <template #hint>
      O arquivo inclui data, minutos e quantidade de sessões por dia no intervalo escolhido.
    </template>
    <BaseCard
      title="Opções de exportação"
      class="export-view__card"
    >
      <FormSection
        title="Período"
        description="Selecione o intervalo de datas para incluir no export."
        grouped
      >
        <BaseDateRangePicker
          v-model="dateRange"
          placeholder-start="Data inicial"
          placeholder-end="Data final"
        />
      </FormSection>
      <FormSection
        title="Formato"
        description="Formato do arquivo gerado."
        grouped
      >
        <div class="export-view__format">
          <label class="export-view__radio-label">
            <input
              v-model="format"
              type="radio"
              value="csv"
              name="export-format"
            >
            CSV (planilha)
          </label>
          <label class="export-view__radio-label">
            <input
              v-model="format"
              type="radio"
              value="json"
              name="export-format"
            >
            JSON
          </label>
        </div>
      </FormSection>
      <div class="export-view__actions">
        <BaseButton
          :disabled="!canExport || exporting"
          @click="doExport"
        >
          {{ exporting ? 'Exportando...' : 'Gerar exportação' }}
        </BaseButton>
      </div>
      <div
        v-if="exportDone"
        class="export-view__success"
        role="status"
        aria-live="polite"
      >
        <span class="export-view__success-icon" aria-hidden="true">✓</span>
        <span>Exportação gerada. O download foi iniciado. Verifique a pasta de downloads do navegador.</span>
      </div>
      <p
        v-if="exportError"
        class="export-view__error"
      >
        {{ exportError }}
      </p>
    </BaseCard>
  </PageView>
</template>

<style scoped>
.export-view__card {
  margin-top: 0;
  border-radius: var(--radius-md);
  overflow: hidden;
}
.export-view__format {
  display: flex;
  gap: var(--spacing-md);
  flex-wrap: wrap;
}
.export-view__radio-label {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-text);
  cursor: pointer;
  padding: var(--spacing-xs) 0;
}
.export-view__actions {
  margin-top: var(--spacing-md);
}
.export-view__success {
  margin-top: var(--spacing-md);
  padding: var(--spacing-md);
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-sm);
  background: var(--color-success-soft);
  border: 1px solid color-mix(in srgb, var(--color-success) 35%, transparent);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  color: var(--color-success);
  line-height: 1.45;
}
.export-view__success-icon {
  font-weight: 700;
  flex-shrink: 0;
}
.export-view__error {
  margin-top: var(--spacing-md);
  padding: var(--spacing-md);
  background: var(--color-error-soft);
  border: 1px solid color-mix(in srgb, var(--color-error) 35%, transparent);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  color: var(--color-error);
  line-height: 1.45;
}
</style>
