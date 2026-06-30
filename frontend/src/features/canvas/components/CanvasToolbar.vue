<script setup lang="ts">
/**
 * @module CanvasToolbar
 * @description Barra de ferramentas do editor de canvas.
 *
 * Exibe grupo de ferramentas (seleção, desenho, formas, texto, destaque, nota adesiva),
 * seletores de cores (preenchimento e contorno), controle de espessura, zoom,
 * e botões de ação (undo, redo, imagem, excluir, exportar).
 *
 * @emits tool - Quando uma ferramenta de seleção/desenho é ativada
 * @emits undo - Solicita desfazer a última ação
 * @emits redo - Solicita refazer a ação desfeita
 * @emits zoomIn - Solicita aumento do zoom
 * @emits zoomOut - Solicita diminuição do zoom
 * @emits zoomReset - Solicita reset do zoom para 100%
 * @emits export - Solicita abertura do diálogo de exportação
 * @emits addImage - Solicita seleção de arquivo de imagem
 * @emits clear - Solicita limpeza total do canvas
 * @emits addShape - Solicita adição de uma forma geométrica
 * @emits addText - Solicita adição de um objeto de texto
 * @emits addSticky - Solicita adição de uma nota adesiva
 * @emits addHighlight - Solicita adição de um marcador de destaque
 * @emits delete - Solicita exclusão dos objetos selecionados
 */
import { useCanvasStore } from '../store/canvas.store'
import type { CanvasTool } from '../types/canvas.types'

const store = useCanvasStore()

const emit = defineEmits<{
  tool: [tool: CanvasTool]
  undo: []
  redo: []
  zoomIn: []
  zoomOut: []
  zoomReset: []
  export: []
  addImage: []
  clear: []
  addShape: [type: string]
  addText: [type: 'text' | 'textbox']
  addSticky: []
  addHighlight: []
  delete: []
}>()

/** Lista de ferramentas disponíveis na toolbar com ícones e rótulos */
const tools: { tool: CanvasTool; icon: string; label: string }[] = [
  { tool: 'select', icon: '⊹', label: 'Selecionar' },
  { tool: 'pencil', icon: '✏', label: 'Pincel' },
  { tool: 'eraser', icon: '◻', label: 'Borracha' },
  { tool: 'line', icon: '╱', label: 'Linha' },
  { tool: 'rect', icon: '▭', label: 'Retângulo' },
  { tool: 'circle', icon: '○', label: 'Círculo' },
  { tool: 'triangle', icon: '△', label: 'Triângulo' },
  { tool: 'text', icon: 'T', label: 'Texto' },
  { tool: 'textbox', icon: '¶', label: 'Caixa de Texto' },
  { tool: 'highlight', icon: '▨', label: 'Destaque' },
  { tool: 'sticky', icon: '◻', label: 'Nota Adesiva' },
]

/**
 * @description Processa a seleção de uma ferramenta e emite o evento correspondente.
 *
 * Redireciona para o emit adequado conforme o tipo de ferramenta:
 * formas, textos, notas adesivas e destaques são emitidos como eventos separados.
 *
 * @param tool - Ferramenta selecionada pelo usuário
 */
function selectTool(tool: CanvasTool) {
  if (tool === 'highlight') {
    emit('addHighlight')
  } else if (tool === 'sticky') {
    emit('addSticky')
  } else if (tool === 'rect' || tool === 'circle' || tool === 'triangle' || tool === 'line') {
    emit('addShape', tool)
  } else if (tool === 'text') {
    emit('addText', 'text')
  } else if (tool === 'textbox') {
    emit('addText', 'textbox')
  } else {
    emit('tool', tool)
  }
}
</script>

<template>
  <div class="canvas-toolbar">
    <div class="canvas-toolbar__group">
      <span class="canvas-toolbar__label">Ferramentas</span>
      <div class="canvas-toolbar__tools">
        <button
          v-for="t in tools"
          :key="t.tool"
          :class="['canvas-toolbar__btn', { 'canvas-toolbar__btn--active': store.activeTool === t.tool }]"
          :title="t.label"
          @click="selectTool(t.tool)"
        >
          <span class="canvas-toolbar__icon">{{ t.icon }}</span>
        </button>
      </div>
    </div>

    <div class="canvas-toolbar__separator" />

    <div class="canvas-toolbar__group">
      <span class="canvas-toolbar__label">Cores</span>
      <div class="canvas-toolbar__colors">
        <label class="canvas-toolbar__color-label" title="Cor de preenchimento">
          <span class="canvas-toolbar__color-text">Preench.</span>
          <input
            type="color"
            :value="store.fillColor"
            class="canvas-toolbar__color-input"
            @input="store.fillColor = ($event.target as HTMLInputElement).value"
          />
        </label>
        <label class="canvas-toolbar__color-label" title="Cor do contorno">
          <span class="canvas-toolbar__color-text">Contorno</span>
          <input
            type="color"
            :value="store.strokeColor"
            class="canvas-toolbar__color-input"
            @input="store.strokeColor = ($event.target as HTMLInputElement).value"
          />
        </label>
        <label class="canvas-toolbar__size-label" title="Espessura">
          <span class="canvas-toolbar__size-text">Tamanho</span>
          <input
            type="range"
            :value="store.strokeWidth"
            min="1"
            max="20"
            class="canvas-toolbar__size-input"
            @input="store.strokeWidth = Number(($event.target as HTMLInputElement).value)"
          />
        </label>
      </div>
    </div>

    <div class="canvas-toolbar__separator" />

    <div class="canvas-toolbar__group">
      <span class="canvas-toolbar__label">Zoom</span>
      <div class="canvas-toolbar__zoom">
        <button class="canvas-toolbar__btn" title="Zoom −" @click="emit('zoomOut')">−</button>
        <button class="canvas-toolbar__btn canvas-toolbar__zoom-value" title="Resetar zoom" @click="emit('zoomReset')">
          {{ store.zoom }}%
        </button>
        <button class="canvas-toolbar__btn" title="Zoom +" @click="emit('zoomIn')">+</button>
      </div>
    </div>

    <div class="canvas-toolbar__separator" />

    <div class="canvas-toolbar__group">
      <span class="canvas-toolbar__label">Ações</span>
      <div class="canvas-toolbar__actions">
        <button class="canvas-toolbar__btn" :disabled="!store.canUndo" title="Desfazer (Ctrl+Z)" @click="emit('undo')">↩</button>
        <button class="canvas-toolbar__btn" :disabled="!store.canRedo" title="Refazer (Ctrl+Y)" @click="emit('redo')">↪</button>
        <button class="canvas-toolbar__btn" title="Adicionar imagem" @click="emit('addImage')">🖼</button>
        <button class="canvas-toolbar__btn" title="Excluir seleção" @click="emit('delete')">🗑</button>
        <button class="canvas-toolbar__btn" title="Exportar" @click="emit('export')">💾</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.canvas-toolbar {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-xs) var(--spacing-sm);
  background: var(--color-bg-card);
  border-bottom: 1px solid var(--color-border);
  flex-wrap: wrap;
  min-height: 3rem;
}
.canvas-toolbar__group {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}
.canvas-toolbar__label {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  font-weight: 500;
  white-space: nowrap;
}
.canvas-toolbar__tools,
.canvas-toolbar__colors,
.canvas-toolbar__zoom,
.canvas-toolbar__actions {
  display: flex;
  align-items: center;
  gap: 2px;
}
.canvas-toolbar__separator {
  width: 1px;
  height: 1.5rem;
  background: var(--color-border);
}
.canvas-toolbar__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border: 1px solid transparent;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--color-text);
  cursor: pointer;
  font-size: var(--text-sm);
  transition: all var(--duration-fast) ease;
}
.canvas-toolbar__btn:hover:not(:disabled) {
  background: var(--color-bg-soft);
  border-color: var(--color-border);
}
.canvas-toolbar__btn--active {
  background: var(--color-primary-soft) !important;
  color: var(--color-primary) !important;
  border-color: var(--color-primary) !important;
}
.canvas-toolbar__btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.canvas-toolbar__icon {
  font-size: 1rem;
}
.canvas-toolbar__color-label {
  display: flex;
  align-items: center;
  gap: var(--spacing-2xs);
  cursor: pointer;
}
.canvas-toolbar__color-text,
.canvas-toolbar__size-text {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
.canvas-toolbar__color-input {
  width: 1.5rem;
  height: 1.5rem;
  padding: 0;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  cursor: pointer;
}
.canvas-toolbar__size-input {
  width: 4rem;
  height: 1.5rem;
}
.canvas-toolbar__zoom-value {
  width: auto;
  padding: 0 var(--spacing-xs);
  font-size: var(--text-xs);
  font-weight: 600;
  cursor: pointer;
}
</style>
