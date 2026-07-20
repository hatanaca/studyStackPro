<script setup lang="ts">
/**
 * @module CanvasEditor
 * @description Componente principal do editor de canvas.
 *
 * Integra a toolbar, workspace com canvas Fabric.js e sidebar de propriedades.
 * Suporta drag & drop de imagens, atalhos de teclado (Ctrl+Z/Y, Delete),
 * modal de seleção de imagens do mural, exportação em múltiplos formatos
 * e integração com organogramas (mapa de estudos).
 */
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { useCanvas } from '@/features/canvas/composables/useCanvas'
import CanvasToolbar from '@/features/canvas/components/CanvasToolbar.vue'
import CanvasSidebar from '@/features/canvas/components/CanvasSidebar.vue'
import ExportDialog from '@/features/canvas/components/ExportDialog.vue'
import { useCanvasStore } from '@/features/canvas/store/canvas.store'
import type { CanvasTool } from '@/features/canvas/types/canvas.types'

const route = useRoute()
const store = useCanvasStore()
/** Referência reativa do elemento `<canvas>` HTML */
const canvasEl = ref<HTMLCanvasElement>()
/** Texto de depuração exibido na barra de ações */
const debugInfo = ref('Aguardando inicialização...')

const {
  canvas,
  setTool,
  addShape,
  addText,
  addStickyNote,
  addHighlight,
  addImage,
  addImageFromMural,
  deleteSelected,
  undo,
  redo,
  zoomIn,
  zoomOut,
  zoomReset,
  toDataURL,
  clearCanvas,
  saveToMural,
  getMuralImages,
  importFromOrganogram,
  exportToOrganogram,
  downloadPNG,
  downloadJSON,
  downloadSVG,
} = useCanvas(canvasEl)

/** Controla a visibilidade do diálogo de exportação */
const showExportDialog = ref(false)
/** Controla a visibilidade do modal de seleção de imagens do mural */
const showMuralPicker = ref(false)
/** Controla a visibilidade do modal de integração com organograma */
const showOrganogramDialog = ref(false)
/** ID da tecnologia atual extraído dos parâmetros da rota */
const technologyId = computed(() => route.params.id as string | undefined)
/** Lista de URLs de imagens disponíveis no mural da tecnologia atual */
const muralImages = computed(() => (technologyId.value ? getMuralImages(technologyId.value) : []))

/**
 * @description Atualiza as informações de depuração do canvas a cada segundo.
 * Exibe dimensões, contagem de objetos, estado de eventos e pointer-events.
 */
function updateDebug() {
  const c = canvas.value
  if (!c) {
    debugInfo.value = 'Canvas não inicializado'
    return
  }
  const upper = c.upperCanvasEl
  const container = upper?.parentElement
  debugInfo.value = [
    `Canvas: ${c.width}x${c.height}`,
    `Objetos: ${c.getObjects().length}`,
    `upper-canvas: ${!!upper}`,
    `pointer-events: ${upper ? getComputedStyle(upper).pointerEvents : 'N/A'}`,
    `upper position: ${upper ? getComputedStyle(upper).position : 'N/A'}`,
    `upper zIndex: ${upper ? getComputedStyle(upper).zIndex : 'N/A'}`,
    `container overflow: ${container ? getComputedStyle(container).overflow : 'N/A'}`,
    `selection: ${c.selection}`,
    `isDrawingMode: ${c.isDrawingMode}`,
    `activeObj: ${c.getActiveObject()?.type || 'none'}`,
  ].join(' | ')
}

let _debugInterval: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  _debugInterval = setInterval(updateDebug, 1000)
})

onBeforeUnmount(() => {
  if (_debugInterval) {
    clearInterval(_debugInterval)
    _debugInterval = null
  }
})

/**
 * @description Trata a seleção de ferramenta disparada pela toolbar.
 * @param tool - Ferramenta selecionada pelo usuário
 */
function handleTool(tool: CanvasTool) {
  setTool(tool)
}

/**
 * @description Trata o evento de drop de arquivo de imagem no workspace do canvas.
 * @param e - Evento de drag and drop contendo o arquivo de imagem
 */
function handleDrop(e: DragEvent) {
  e.preventDefault()
  const file = e.dataTransfer?.files[0]
  if (file && file.type.startsWith('image/')) addImage(file)
}

/**
 * @description Trata a seleção de imagem via input de arquivo.
 * @param e - Evento de mudança do input file
 */
function handleImageUpload(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) addImage(file)
  input.value = ''
}

/**
 * @description Processa a exportação do canvas no formato selecionado.
 * @param format - Formato de exportação: 'png', 'jpeg', 'svg' ou 'json'
 * @param options - Opções adicionais como qualidade e nome do arquivo
 */
function handleExport(format: string, options?: any) {
  switch (format) {
    case 'png':
      downloadPNG()
      break
    case 'svg':
      downloadSVG()
      break
    case 'json':
      downloadJSON()
      break
    case 'jpeg': {
      const data = toDataURL({ format: 'jpeg', quality: options?.quality || 0.9, multiplier: 2 })
      if (data) {
        const a = document.createElement('a')
        a.href = data
        a.download = 'canvas.jpg'
        a.click()
      }
      break
    }
  }
  showExportDialog.value = false
}

/**
 * @description Seleciona uma imagem do mural e a adiciona ao canvas.
 * @param url - Data URL da imagem selecionada no mural
 */
function pickMuralImage(url: string) {
  addImageFromMural(technologyId.value!, url)
  showMuralPicker.value = false
}

/**
 * @description Salva o estado atual do canvas como imagem no mural da tecnologia.
 */
function saveCanvasToMural() {
  if (technologyId.value) saveToMural(technologyId.value)
}

/**
 * @description Trata a importação ou exportação de dados do organograma.
 * @param action - 'import' para carregar dados do organograma para o canvas,
 *                 'export' para salvar o conteúdo do canvas como organograma
 */
function handleOrganogram(action: 'import' | 'export') {
  if (!technologyId.value) return
  if (action === 'import') {
    importFromOrganogram(technologyId.value)
  } else {
    exportToOrganogram(technologyId.value)
  }
  showOrganogramDialog.value = false
}

/**
 * @description Trata atalhos de teclado do editor.
 *
 * Suporta: Delete/Backspace para excluir seleção, Ctrl+Z para desfazer,
 * Ctrl+Shift+Z / Ctrl+Y para refazer. Ignora quando o foco está em
 * campos de entrada ou texto em edição no canvas.
 *
 * @param e - Evento de teclado do navegador
 */
function onKeydown(e: KeyboardEvent) {
  const target = e.target as HTMLElement
  const tag = target.tagName
  if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return
  if (target.isContentEditable) return

  // Check if Fabric canvas has an active text object being edited
  const fabricCanvas = (window as any).__fabricCanvas
  if (fabricCanvas) {
    const active =
      typeof fabricCanvas.getActiveObject === 'function'
        ? fabricCanvas.getActiveObject()
        : fabricCanvas.value?.getActiveObject?.()
    if (active && typeof active.isEditing === 'function' && active.isEditing()) return
  }

  if (e.key === 'Delete' || e.key === 'Backspace') {
    if (store.hasSelection) {
      e.preventDefault()
      deleteSelected()
    }
  }
  if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
    e.preventDefault()
    if (e.shiftKey) { redo() } else { undo() }
  }
  if ((e.ctrlKey || e.metaKey) && e.key === 'y') {
    e.preventDefault()
    redo()
  }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))
</script>

<template>
  <div class="canvas-editor">
    <CanvasToolbar
      @tool="handleTool"
      @undo="undo"
      @redo="redo"
      @zoom-in="zoomIn"
      @zoom-out="zoomOut"
      @zoom-reset="zoomReset"
      @export="showExportDialog = true"
      @add-image="($refs.fileInput as HTMLInputElement).click()"
      @clear="clearCanvas"
      @add-shape="addShape"
      @add-text="addText"
      @add-sticky="addStickyNote"
      @add-highlight="addHighlight"
      @delete="deleteSelected"
    />

    <div class="canvas-editor__actions">
      <button v-if="technologyId" class="canvas-editor__action-btn" @click="showMuralPicker = true">
        🖼 Mural
      </button>
      <button v-if="technologyId" class="canvas-editor__action-btn" @click="saveCanvasToMural">
        💾 Salvar no Mural
      </button>
      <button
        v-if="technologyId"
        class="canvas-editor__action-btn"
        @click="showOrganogramDialog = true"
      >
        🔗 Organograma
      </button>
      <button class="canvas-editor__action-btn" @click="downloadPNG">📥 Download PNG</button>
      <span class="canvas-editor__debug">{{ debugInfo }}</span>
    </div>

    <div class="canvas-editor__workspace">
      <div class="canvas-editor__container" @drop="handleDrop" @dragover.prevent>
        <canvas id="fabric-canvas" ref="canvasEl" />
      </div>
      <CanvasSidebar v-if="store.hasSelection" />
    </div>

    <input
      ref="fileInput"
      type="file"
      accept="image/*"
      class="canvas-editor__file-input"
      @change="handleImageUpload"
    />

    <div
      v-if="showMuralPicker"
      class="canvas-editor__modal-backdrop"
      @click.self="showMuralPicker = false"
    >
      <div class="canvas-editor__modal">
        <div class="canvas-editor__modal-header">
          <h3>Imagens do Mural</h3>
          <button @click="showMuralPicker = false">✕</button>
        </div>
        <div class="canvas-editor__modal-body">
          <p v-if="muralImages.length === 0" class="canvas-editor__modal-empty">
            Nenhuma imagem no mural.
          </p>
          <div v-else class="canvas-editor__mural-grid">
            <button
              v-for="(img, i) in muralImages"
              :key="i"
              class="canvas-editor__mural-item"
              @click="pickMuralImage(img)"
            >
              <img :src="img" alt="" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="showOrganogramDialog"
      class="canvas-editor__modal-backdrop"
      @click.self="showOrganogramDialog = false"
    >
      <div class="canvas-editor__modal canvas-editor__modal--sm">
        <div class="canvas-editor__modal-header">
          <h3>Organograma</h3>
          <button @click="showOrganogramDialog = false">✕</button>
        </div>
        <div class="canvas-editor__modal-body">
          <p class="canvas-editor__modal-desc">Conectar com o mapa de estudos.</p>
          <div class="canvas-editor__modal-actions">
            <button class="canvas-editor__modal-btn" @click="handleOrganogram('import')">
              📥 Importar do Organograma
            </button>
            <button class="canvas-editor__modal-btn" @click="handleOrganogram('export')">
              📤 Exportar para Organograma
            </button>
          </div>
        </div>
      </div>
    </div>

    <ExportDialog
      v-if="showExportDialog"
      @close="showExportDialog = false"
      @export="handleExport"
    />
  </div>
</template>

<style scoped>
.canvas-editor {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 4rem);
  background: var(--color-bg);
  outline: none;
  user-select: text;
  -webkit-user-select: text;
  border-radius: 10px 10px 0 0;
  overflow: hidden;
}
.canvas-editor__actions {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 12px;
  border-bottom: 1px solid var(--color-border);
  background: var(--color-bg-card);
  height: 36px;
  box-sizing: border-box;
}
.canvas-editor__action-btn {
  height: 28px;
  padding: 0 10px;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  background: transparent;
  color: var(--color-text);
  font-size: 11px;
  font-weight: 500;
  cursor: pointer;
  transition:
    background 0.12s,
    border-color 0.12s;
  display: flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}
.canvas-editor__action-btn:hover {
  background: var(--color-bg-soft);
  border-color: var(--color-primary);
}
.canvas-editor__workspace {
  display: flex;
  flex: 1;
  min-height: 0;
  padding: 8px;
  gap: 8px;
}
.canvas-editor__container {
  flex: 1 1 0%;
  min-width: 0;
  position: relative;
  background: var(--color-bg-card, #1c1c1f);
  border-radius: 8px;
  overflow: hidden;
}
.canvas-editor__file-input {
  display: none;
}
.canvas-editor__modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.canvas-editor__modal {
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  width: 100%;
  max-width: 500px;
  max-height: 80vh;
  overflow: hidden;
}
.canvas-editor__modal--sm {
  max-width: 380px;
}
.canvas-editor__modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-sm) var(--spacing-md);
  border-bottom: 1px solid var(--color-border);
}
.canvas-editor__modal-header h3 {
  margin: 0;
  font-size: var(--text-base);
}
.canvas-editor__modal-header button {
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  font-size: var(--text-lg);
}
.canvas-editor__modal-body {
  padding: var(--spacing-md);
  overflow-y: auto;
}
.canvas-editor__modal-empty {
  text-align: center;
  color: var(--color-text-muted);
}
.canvas-editor__modal-desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-md);
}
.canvas-editor__modal-actions {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.canvas-editor__modal-btn {
  width: 100%;
  height: 2.25rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: var(--color-bg);
  color: var(--color-text);
  font-size: var(--text-sm);
  cursor: pointer;
  text-align: left;
  padding: 0 var(--spacing-md);
}
.canvas-editor__modal-btn:hover {
  background: var(--color-primary-soft);
  border-color: var(--color-primary);
}
.canvas-editor__mural-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-xs);
}
.canvas-editor__mural-item {
  aspect-ratio: 1;
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
  cursor: pointer;
  padding: 0;
  background: none;
}
.canvas-editor__mural-item:hover {
  border-color: var(--color-primary);
}
.canvas-editor__mural-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.canvas-editor__debug {
  flex: 1;
  font-size: var(--text-xs);
  color: var(--color-text-muted, #71717a);
  text-align: right;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 0 8px;
  line-height: 1.75rem;
}

@media (max-width: 640px) {
  .canvas-editor__actions {
    overflow-x: auto;
    flex-wrap: nowrap;
    gap: 4px;
    padding: 4px 8px;
  }
  .canvas-editor__action-btn {
    white-space: nowrap;
    flex-shrink: 0;
    font-size: 10px;
    padding: 0 8px;
    height: 26px;
  }
  .canvas-editor__debug {
    display: none;
  }
}
</style>

<style>
.canvas-editor,
.canvas-editor * {
  user-select: text !important;
  -webkit-user-select: text !important;
}
.canvas-container {
  position: relative !important;
}
.canvas-container canvas {
  display: block !important;
}
</style>
