<script setup lang="ts">
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

function selectTool(tool: CanvasTool) {
  if (tool === 'highlight') emit('addHighlight')
  else if (tool === 'sticky') emit('addSticky')
  else if (['rect', 'circle', 'triangle', 'line'].includes(tool)) emit('addShape', tool)
  else if (tool === 'text') emit('addText', 'text')
  else if (tool === 'textbox') emit('addText', 'textbox')
  else emit('tool', tool)
}
</script>

<template>
  <div class="ct">
    <!-- Ferramentas de desenho -->
    <div class="ct__section">
      <button
        v-for="t in tools"
        :key="t.tool"
        :class="['ct__btn', { 'ct__btn--on': store.activeTool === t.tool }]"
        :title="t.label"
        @click="selectTool(t.tool)"
      >
        {{ t.icon }}
      </button>
    </div>

    <div class="ct__div" />

    <!-- Cores -->
    <div class="ct__section ct__section--colors">
      <label class="ct__swatch" title="Preenchimento">
        <input
          type="color"
          :value="store.fillColor"
          class="ct__swatch-input"
          @input="store.fillColor = ($event.target as HTMLInputElement).value"
        />
        <span class="ct__swatch-fill" :style="{ background: store.fillColor }" />
      </label>
      <label class="ct__swatch" title="Contorno">
        <input
          type="color"
          :value="store.strokeColor"
          class="ct__swatch-input"
          @input="store.strokeColor = ($event.target as HTMLInputElement).value"
        />
        <span class="ct__swatch-fill" :style="{ background: store.strokeColor }" />
      </label>
      <div class="ct__slider" title="Espessura do contorno">
        <input
          type="range"
          :value="store.strokeWidth"
          min="1"
          max="20"
          class="ct__range"
          @input="store.strokeWidth = Number(($event.target as HTMLInputElement).value)"
        />
      </div>
    </div>

    <div class="ct__div" />

    <!-- Zoom -->
    <div class="ct__section">
      <button class="ct__btn" title="Zoom −" @click="emit('zoomOut')">−</button>
      <span class="ct__zoom" title="Resetar zoom" @click="emit('zoomReset')">{{ store.zoom }}%</span>
      <button class="ct__btn" title="Zoom +" @click="emit('zoomIn')">+</button>
    </div>

    <div class="ct__div" />

    <!-- Ações -->
    <div class="ct__section">
      <button class="ct__btn" :disabled="!store.canUndo" title="Desfazer (Ctrl+Z)" @click="emit('undo')">↩</button>
      <button class="ct__btn" :disabled="!store.canRedo" title="Refazer (Ctrl+Y)" @click="emit('redo')">↪</button>
      <button class="ct__btn" title="Imagem" @click="emit('addImage')">🖼</button>
      <button class="ct__btn" title="Excluir" @click="emit('delete')">🗑</button>
      <button class="ct__btn" title="Exportar" @click="emit('export')">💾</button>
    </div>
  </div>
</template>

<style scoped>
.ct {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: var(--color-bg-card);
  border-bottom: 1px solid var(--color-border);
  height: 40px;
  box-sizing: border-box;
}

.ct__section {
  display: flex;
  align-items: center;
  gap: 2px;
}

.ct__section--colors {
  gap: 8px;
}

.ct__div {
  width: 1px;
  height: 20px;
  background: var(--color-border);
  flex-shrink: 0;
}

/* Botões de ferramenta */
.ct__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  padding: 0;
  border: none;
  border-radius: 6px;
  background: transparent;
  color: var(--color-text);
  cursor: pointer;
  font-size: 14px;
  flex-shrink: 0;
  transition: background 0.12s, color 0.12s;
}

.ct__btn:hover:not(:disabled) {
  background: var(--color-bg-soft);
}

.ct__btn--on {
  background: var(--color-primary-soft);
  color: var(--color-primary);
}

.ct__btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

/* Swatches de cor */
.ct__swatch {
  position: relative;
  width: 15px;
  height: 15px;
  cursor: pointer;
  flex-shrink: 0;
}

.ct__swatch-input {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.ct__swatch-fill {
  display: block;
  width: 15px;
  height: 15px;
  border-radius: 3px;
  border: 1px solid var(--color-border);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1);
  transition: transform 0.12s, box-shadow 0.12s;
  pointer-events: none;
}

.ct__swatch:hover .ct__swatch-fill {
  transform: scale(1.15);
  box-shadow: 0 0 0 2px var(--color-primary);
}

/* Slider de espessura */
.ct__slider {
  display: flex;
  align-items: center;
}

.ct__range {
  width: 60px;
  height: 4px;
  accent-color: var(--color-primary);
  cursor: pointer;
}

/* Zoom */
.ct__zoom {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted);
  min-width: 36px;
  text-align: center;
  cursor: pointer;
  padding: 4px 6px;
  border-radius: 4px;
  transition: background 0.12s;
  user-select: none;
}

.ct__zoom:hover {
  background: var(--color-bg-soft);
}

@media (max-width: 640px) {
  .ct { flex-wrap: wrap; gap: 6px; padding: 6px 10px; height: auto; min-height: 40px; }
  .ct__section { gap: 2px; }
  .ct__section--colors { gap: 8px; }
  .ct__btn { width: 36px; height: 36px; font-size: 14px; }
  .ct__swatch { width: 20px; height: 20px; }
  .ct__swatch-fill { width: 20px; height: 20px; }
  .ct__range { width: 52px; }
  .ct__div { display: none; }
}
</style>
