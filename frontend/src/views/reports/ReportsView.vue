<script setup lang="ts">
import { ref } from 'vue'
import PageView from '@/components/layout/PageView.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import Callout from '@/components/ui/Callout.vue'

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
            >
            <span class="reports-sep">até</span>
            <input
              v-model="dateRange.end"
              type="date"
              class="reports-input"
              aria-label="Data final"
            >
          </div>
        </div>
        <BaseButton
          variant="primary"
          disabled
        >
          Gerar relatório (em breve)
        </BaseButton>
      </div>
      <Callout
        variant="info"
        title="Em breve: download em PDF"
      >
        A geração de relatórios em PDF com resumo por período e gráficos está em desenvolvimento.
        Por enquanto, use a página Exportar para baixar dados em CSV ou JSON.
      </Callout>
    </section>
  </PageView>
</template>

<style scoped>
.reports-card {
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xl);
  box-shadow: var(--shadow-sm);
}
.reports-card__title {
  margin: 0;
  font-size: var(--text-lg);
  font-weight: 600;
  color: var(--color-text);
  line-height: var(--leading-snug);
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
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.reports-input:focus {
  border-color: var(--form-input-border-focus);
  box-shadow: var(--form-input-shadow-focus);
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
