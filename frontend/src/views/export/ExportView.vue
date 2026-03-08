<script setup lang="ts">
import { ref, computed } from 'vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseDateRangePicker from '@/components/ui/BaseDateRangePicker.vue'
import FormSection from '@/components/ui/FormSection.vue'
import PageView from '@/components/layout/PageView.vue'
import { analyticsApi } from '@/api/modules/analytics.api'

const dateRange = ref<{ start: string; end: string } | null>(null)
const format = ref<'csv' | 'json'>('csv')
const exporting = ref(false)
const exportDone = ref(false)
const exportError = ref<string | null>(null)

const canExport = computed(() => !!dateRange.value?.start && !!dateRange.value?.end)

type ExportRow = { date: string; total_minutes: number; session_count?: number }

function buildCSV(rows: ExportRow[]): string {
  const header = 'Data,Minutos,Sessões'
  const lines = rows.map(r => `${r.date},${r.total_minutes ?? 0},${r.session_count ?? 0}`)
  return [header, ...lines].join('\n')
}

function buildJSON(
  rows: ExportRow[],
  meta: { exported_at: string; period: { start: string; end: string } }
): string {
  return JSON.stringify({ ...meta, data: rows }, null, 2)
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
    const res = await analyticsApi.getExport({
      start: dateRange.value.start,
      end: dateRange.value.end,
    })
    // API retorna { success, data: { exported_at, period, data } }
    const payload = res.data?.data as
      | { exported_at: string; period: { start: string; end: string }; data: ExportRow[] }
      | undefined
    if (!payload?.data) {
      throw new Error('Resposta inválida da API')
    }
    const rows: ExportRow[] = payload.data
    const filename = `studytrack-${dateRange.value.start}-${dateRange.value.end}.${format.value}`
    if (format.value === 'csv') {
      const csv = buildCSV(rows)
      downloadBlob(new Blob([csv], { type: 'text/csv;charset=utf-8' }), filename)
    } else {
      const json = buildJSON(rows, {
        exported_at: payload.exported_at ?? new Date().toISOString(),
        period: payload.period ?? dateRange.value,
      })
      downloadBlob(new Blob([json], { type: 'application/json' }), filename)
    }
    exportDone.value = true
  } catch (e: unknown) {
    let msg: string | null = null
    if (e && typeof e === 'object' && 'response' in e && e.response && typeof e.response === 'object' && 'data' in e.response) {
      const data = (e.response as { data?: Record<string, unknown> }).data as Record<string, unknown> | undefined
      const err = data?.error as { message?: string } | undefined
      const firstError = data?.errors && typeof data.errors === 'object'
        ? (Object.values(data.errors as Record<string, string[]>) as string[][])[0]?.[0]
        : null
      msg = err?.message ?? (data?.message as string) ?? firstError ?? null
    }
    exportError.value = msg ?? (e instanceof Error ? e.message : 'Erro ao gerar exportação')
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
      Os dados são buscados no servidor para o período escolhido. O arquivo inclui data, minutos e quantidade de sessões por dia.
    </template>
    <BaseCard
      title="Opções de exportação"
      class="export-view__card"
      :aria-busy="exporting"
      aria-live="polite"
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
      <fieldset class="export-view__fieldset">
        <legend class="export-view__legend">
          Formato
        </legend>
        <p class="export-view__fieldset-desc">
          Formato do arquivo gerado.
        </p>
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
      </fieldset>
      <div class="export-view__actions">
        <BaseButton
          :disabled="!canExport || exporting"
          :aria-busy="exporting"
          aria-live="polite"
          @click="doExport"
        >
          {{ exporting ? 'Buscando dados no servidor…' : 'Gerar exportação' }}
        </BaseButton>
      </div>
      <div
        v-if="exportDone"
        class="export-view__success"
        role="status"
        aria-live="polite"
      >
        <span
          class="export-view__success-icon"
          aria-hidden="true"
        >✓</span>
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
.export-view__fieldset {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--spacing-md);
  margin: 0 0 var(--spacing-md);
}
.export-view__legend {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text);
  padding: 0 var(--spacing-xs);
  margin: 0 0 var(--spacing-xs);
}
.export-view__fieldset-desc {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-sm);
  line-height: 1.4;
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
