<script setup lang="ts">
import { ref } from 'vue'
import Button from 'primevue/button'
import Fieldset from 'primevue/fieldset'

const exporting = ref(false)
function handleExport() {
  exporting.value = true
  setTimeout(() => { exporting.value = false }, 1000)
}
</script>

<template>
  <Fieldset legend="Dados">
    <p class="data-section__desc">
      Exporte ou limpe dados locais (ex.: metas salvas no navegador).
    </p>
    <div class="data-section">
      <Button
        :label="exporting ? 'Preparando...' : 'Exportar dados locais'"
        severity="secondary"
        variant="outlined"
        :loading="exporting"
        @click="handleExport"
      />
      <p class="data-section__hint">
        Isso exporta metas e preferências armazenadas no seu navegador. As sessões de estudo vêm do servidor.
      </p>
    </div>
  </Fieldset>
</template>

<style scoped>
.data-section {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}
.data-section__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-sm);
  line-height: 1.45;
}
.data-section__hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: 0;
  line-height: 1.45;
}
</style>
