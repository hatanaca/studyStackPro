<script setup lang="ts">
import { ref } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import FormSection from '@/components/ui/FormSection.vue'

const exporting = ref(false)
function handleExport() {
  exporting.value = true
  setTimeout(() => { exporting.value = false }, 1000)
}
</script>

<template>
  <FormSection
    title="Dados"
    description="Exporte ou limpe dados locais (ex.: metas salvas no navegador)."
    grouped
  >
    <div class="data-section">
      <BaseButton
        variant="outline"
        :disabled="exporting"
        @click="handleExport"
      >
        {{ exporting ? 'Preparando...' : 'Exportar dados locais' }}
      </BaseButton>
      <p class="data-section__hint">
        Isso exporta metas e preferências armazenadas no seu navegador. As sessões de estudo vêm do servidor.
      </p>
    </div>
  </FormSection>
</template>

<style scoped>
.data-section {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}
.data-section__hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: 0;
  line-height: 1.45;
}
</style>
