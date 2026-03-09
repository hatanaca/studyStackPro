<script setup lang="ts">
import { ref } from 'vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Message from 'primevue/message'
import PageView from '@/components/layout/PageView.vue'

const dateRange = ref<{ start: string; end: string }>({ start: '', end: '' })
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Relatórios' }]"
    title="Relatórios"
    subtitle="Gere relatórios de estudo por período. Em breve: PDF e resumos por tecnologia."
    narrow
  >
    <template #hint>
      Esta página é um placeholder. Em breve: download em PDF com resumo e gráficos por período.
    </template>
    <Card class="reports-view__card">
      <template #title>Relatório de atividades</template>
      <template #content>
        <div class="reports-view__form">
          <div class="reports-view__field">
            <label class="reports-view__label">Período</label>
            <div class="reports-view__dates">
              <input
                v-model="dateRange.start"
                type="date"
                class="p-inputtext p-component"
                aria-label="Data inicial"
              >
              <span class="reports-view__sep">até</span>
              <input
                v-model="dateRange.end"
                type="date"
                class="p-inputtext p-component"
                aria-label="Data final"
              >
            </div>
          </div>
          <Button
            label="Gerar relatório (em breve)"
            disabled
          />
        </div>
        <Message
          severity="info"
          :closable="false"
        >
          <strong>Em breve: download em PDF</strong><br>
          A geração de relatórios em PDF com resumo por período e gráficos está em desenvolvimento. Por enquanto, use a página Exportar para baixar dados em CSV ou JSON.
        </Message>
      </template>
    </Card>
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
.reports-view__dates {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.reports-view__dates input { min-width: 140px; }
.reports-view__sep { font-size: var(--text-sm); color: var(--color-text-muted); }
</style>
