<script setup lang="ts">
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Fieldset from 'primevue/fieldset'

const router = useRouter()
function handleExport() {
  router.push({ name: 'settings-export' })
}
</script>

<template>
  <Fieldset legend="Dados">
    <p class="data-section__desc">
      Em Exportar dados (Configurações) você baixa sessões e métricas por período (servidor). Metas e
      preferências continuam armazenadas no navegador.
    </p>
    <div class="data-section">
      <Button
        label="Ir para exportação"
        severity="secondary"
        variant="outlined"
        @click="handleExport"
      />
      <p class="data-section__hint">
        O download em CSV ou JSON usa os dados sincronizados com o servidor, não um backup completo
        do app.
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
  line-height: var(--leading-snug);
  letter-spacing: var(--tracking-tight);
}
.data-section__hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: 0;
  line-height: var(--leading-snug);
  letter-spacing: var(--tracking-tight);
}
.data-section :deep(.p-button) {
  min-height: var(--touch-target-min);
}
.data-section :deep(.p-button:focus-visible) {
  outline: none;
  box-shadow: var(--shadow-focus);
}
</style>
