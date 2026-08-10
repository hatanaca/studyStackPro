<script setup lang="ts">
/**
 * @module ExportDialog
 * @description Diálogo modal de exportação do canvas.
 *
 * Permite ao usuário selecionar o formato de exportação (PNG, JPEG, SVG ou JSON),
 * ajustar a qualidade (para JPEG) e definir o nome do arquivo.
 *
 * @emits close - Solicita o fechamento do diálogo
 * @emits export - Dispara a exportação com o formato e opções selecionados
 */
import { ref } from 'vue'

const emit = defineEmits<{
  close: []
  export: [format: string, options?: any]
}>()

const selectedFormat = ref('png')
const quality = ref(90)
const filename = ref('canvas-export')

/** Lista de formatos disponíveis para exportação com descrições */
const formats = [
  { value: 'png', label: 'PNG', description: 'Imagem sem perda de qualidade' },
  { value: 'jpeg', label: 'JPEG', description: 'Imagem com compressão' },
  { value: 'svg', label: 'SVG', description: 'Vetor escalável' },
  { value: 'json', label: 'JSON', description: 'Dados editáveis' },
]

/**
 * @description Emite o evento de exportação com o formato e opções selecionados pelo usuário.
 */
function handleExport() {
  emit('export', selectedFormat.value, {
    quality: quality.value / 100,
    filename: filename.value,
  })
}
</script>

<template>
  <div class="export-dialog__backdrop" @click.self="emit('close')">
    <div class="export-dialog">
      <div class="export-dialog__header">
        <h3 class="export-dialog__title">Exportar Canvas</h3>
        <button class="export-dialog__close" @click="emit('close')">✕</button>
      </div>

      <div class="export-dialog__body">
        <div class="export-dialog__field">
          <label class="export-dialog__label">Formato</label>
          <div class="export-dialog__formats">
            <label
              v-for="fmt in formats"
              :key="fmt.value"
              :class="[
                'export-dialog__format',
                { 'export-dialog__format--active': selectedFormat === fmt.value },
              ]"
            >
              <input
                v-model="selectedFormat"
                type="radio"
                :value="fmt.value"
                class="export-dialog__radio"
              />
              <span class="export-dialog__format-label">{{ fmt.label }}</span>
              <span class="export-dialog__format-desc">{{ fmt.description }}</span>
            </label>
          </div>
        </div>

        <div v-if="selectedFormat === 'jpeg'" class="export-dialog__field">
          <label class="export-dialog__label">Qualidade: {{ quality }}%</label>
          <input
            v-model.number="quality"
            type="range"
            min="10"
            max="100"
            class="export-dialog__range"
          />
        </div>

        <div class="export-dialog__field">
          <label class="export-dialog__label">Nome do arquivo</label>
          <input
            v-model="filename"
            type="text"
            class="export-dialog__input"
            placeholder="canvas-export"
          />
        </div>
      </div>

      <div class="export-dialog__footer">
        <button class="export-dialog__btn export-dialog__btn--secondary" @click="emit('close')">
          Cancelar
        </button>
        <button class="export-dialog__btn export-dialog__btn--primary" @click="handleExport">
          Exportar
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.export-dialog__backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.export-dialog {
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-lg);
  width: 100%;
  max-width: 420px;
  overflow: hidden;
}

.export-dialog__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-sm) var(--spacing-md);
  border-bottom: 1px solid var(--color-border);
}

.export-dialog__title {
  font-size: var(--text-base);
  font-weight: 600;
  margin: 0;
}

.export-dialog__close {
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  font-size: var(--text-lg);
}

.export-dialog__body {
  padding: var(--spacing-md);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.export-dialog__field {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.export-dialog__label {
  font-size: var(--text-sm);
  font-weight: 500;
}

.export-dialog__formats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-xs);
}

.export-dialog__format {
  display: flex;
  flex-direction: column;
  padding: var(--spacing-sm);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--duration-fast) ease;
}

.export-dialog__format:hover {
  border-color: var(--color-primary);
}

.export-dialog__format--active {
  border-color: var(--color-primary);
  background: var(--color-primary-soft);
}

.export-dialog__radio {
  display: none;
}

.export-dialog__format-label {
  font-size: var(--text-sm);
  font-weight: 600;
}

.export-dialog__format-desc {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.export-dialog__range {
  width: 100%;
}

.export-dialog__input {
  height: 2rem;
  padding: 0 var(--spacing-sm);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: var(--color-bg);
  color: var(--color-text);
  font-size: var(--text-sm);
}

.export-dialog__footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-xs);
  padding: var(--spacing-sm) var(--spacing-md);
  border-top: 1px solid var(--color-border);
}

.export-dialog__btn {
  height: 2rem;
  padding: 0 var(--spacing-md);
  border-radius: var(--radius-sm);
  font-size: var(--text-sm);
  font-weight: 500;
  cursor: pointer;
  transition: all var(--duration-fast) ease;
}

.export-dialog__btn--secondary {
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  color: var(--color-text);
}

.export-dialog__btn--secondary:hover {
  background: var(--color-bg-soft);
}

.export-dialog__btn--primary {
  background: var(--color-primary);
  border: 1px solid var(--color-primary);
  color: white;
}

.export-dialog__btn--primary:hover {
  opacity: 0.9;
}
</style>
