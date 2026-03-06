<script setup lang="ts">
import { ref } from 'vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseDateRangePicker from '@/components/ui/BaseDateRangePicker.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import PageView from '@/components/layout/PageView.vue'

const dateRange = ref<{ start: string; end: string } | null>(null)
const generating = ref(false)

async function generateReport() {
  if (!dateRange.value) return
  generating.value = true
  await new Promise(r => setTimeout(r, 1500))
  generating.value = false
}
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Relatórios' }]"
    title="Relatórios"
    subtitle="Gere relatórios de estudo por período. Em breve: PDF e resumos por tecnologia."
    narrow
  >
    <template #hint>
      Selecione o período e clique em Gerar. Em breve você poderá baixar um PDF com resumo e gráficos.
    </template>
    <BaseCard
      title="Relatório de atividades"
      class="reports-view__card"
    >
      <div class="reports-view__form">
        <div class="reports-view__field">
          <label class="reports-view__label">Período</label>
          <BaseDateRangePicker
            v-model="dateRange"
            placeholder-start="Data inicial"
            placeholder-end="Data final"
          />
        </div>
        <BaseButton
          :disabled="!dateRange?.start || !dateRange?.end || generating"
          @click="generateReport"
        >
          {{ generating ? 'Gerando...' : 'Gerar relatório' }}
        </BaseButton>
      </div>
      <EmptyState
        title="Relatórios em desenvolvimento"
        description="Em breve você poderá baixar um resumo em PDF do seu estudo no período selecionado, com totais por tecnologia e evolução semanal."
        icon="📄"
      />
    </BaseCard>
  </PageView>
</template>

<style scoped>
.reports-view__card {
  margin-top: 0;
  border-radius: var(--radius-md);
  overflow: hidden;
}
.reports-view__form {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  padding: var(--spacing-md) 0;
  border-bottom: 1px solid var(--color-border);
}
.reports-view__field {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  min-width: 200px;
}
.reports-view__label {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
}
</style>
