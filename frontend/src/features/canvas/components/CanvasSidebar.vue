<script setup lang="ts">
/**
 * @module CanvasSidebar
 * @description Painel lateral de propriedades do objeto selecionado no canvas.
 *
 * Exibe e permite editar: cores (preenchimento e contorno), espessura do contorno,
 * opacidade, propriedades de texto (tamanho e família da fonte), posição (X/Y),
 * rotação, espelhamento (X/Y) e exclusão do objeto.
 *
 * A sidebar é exibida apenas quando há um objeto selecionado no canvas.
 */
import { ref, watch, computed } from 'vue'
import { useCanvasStore } from '../store/canvas.store'
import { getFabricCanvas } from '../composables/canvasInstance'

const store = useCanvasStore()

const fillColor = ref('#fafafa')
const strokeColor = ref('#fafafa')
const strokeWidth = ref(1)
const opacity = ref(100)
const fontSize = ref(24)
const fontFamily = ref('Arial')
const objectName = ref('')
const objectLeft = ref(0)
const objectTop = ref(0)
const objectAngle = ref(0)

const hasSelection = computed(() => store.hasSelection)

watch(
  () => store.selectedObject,
  (obj) => {
    if (!obj) return
    fillColor.value = obj.fill || '#000000'
    strokeColor.value = obj.stroke || '#000000'
    strokeWidth.value = obj.strokeWidth || 1
    opacity.value = Math.round((obj.opacity || 1) * 100)
    fontSize.value = obj.fontSize || 24
    fontFamily.value = obj.fontFamily || 'Arial'
    objectName.value = obj.type || ''
    objectLeft.value = Math.round(obj.left || 0)
    objectTop.value = Math.round(obj.top || 0)
    objectAngle.value = Math.round(obj.angle || 0)
  },
  { immediate: true }
)

/**
 * @description Atualiza uma propriedade do objeto selecionado no canvas.
 * @param prop - Nome da propriedade Fabric.js a ser alterada
 * @param value - Novo valor da propriedade
 */
function updateProp(prop: string, value: any) {
  const canvasRef = getFabricCanvas()
  if (!canvasRef?.value) return
  const obj = canvasRef.value.getActiveObject()
  if (obj) {
    obj.set(prop, value)
    canvasRef.value.renderAll()
  }
}

/**
 * @description Trata a mudança da cor de preenchimento via input color.
 * @param e - Evento de mudança do input
 */
function handleFillChange(e: Event) {
  const value = (e.target as HTMLInputElement).value
  fillColor.value = value
  updateProp('fill', value)
}

/**
 * @description Trata a mudança da cor do contorno via input color.
 * @param e - Evento de mudança do input
 */
function handleStrokeChange(e: Event) {
  const value = (e.target as HTMLInputElement).value
  strokeColor.value = value
  updateProp('stroke', value)
}

/**
 * @description Trata a mudança da espessura do contorno via range input.
 * @param e - Evento de mudança do input range
 */
function handleStrokeWidthChange(e: Event) {
  const value = Number((e.target as HTMLInputElement).value)
  strokeWidth.value = value
  updateProp('strokeWidth', value)
}

/**
 * @description Trata a mudança da opacidade do objeto via range input.
 * @param e - Evento de mudança do input range (valor 0–100)
 */
function handleOpacityChange(e: Event) {
  const value = Number((e.target as HTMLInputElement).value)
  opacity.value = value
  updateProp('opacity', value / 100)
}

/**
 * @description Trata a mudança do tamanho da fonte via input numérico.
 * @param e - Evento de mudança do input number
 */
function handleFontSizeChange(e: Event) {
  const value = Number((e.target as HTMLInputElement).value)
  fontSize.value = value
  updateProp('fontSize', value)
}

/**
 * @description Trata a mudança da família tipográfica via select.
 * @param e - Evento de mudança do select
 */
function handleFontFamilyChange(e: Event) {
  const value = (e.target as HTMLSelectElement).value
  fontFamily.value = value
  updateProp('fontFamily', value)
}

/**
 * @description Trata a mudança da posição horizontal (X) do objeto.
 * @param e - Evento de mudança do input number
 */
function handleLeftChange(e: Event) {
  const value = Number((e.target as HTMLInputElement).value)
  objectLeft.value = value
  updateProp('left', value)
}

/**
 * @description Trata a mudança da posição vertical (Y) do objeto.
 * @param e - Evento de mudança do input number
 */
function handleTopChange(e: Event) {
  const value = Number((e.target as HTMLInputElement).value)
  objectTop.value = value
  updateProp('top', value)
}

/**
 * @description Trata a mudança do ângulo de rotação do objeto.
 * @param e - Evento de mudança do input range (0–360 graus)
 */
function handleAngleChange(e: Event) {
  const value = Number((e.target as HTMLInputElement).value)
  objectAngle.value = value
  updateProp('angle', value)
}

/**
 * @description Espelha o objeto selecionado horizontalmente (eixo X).
 */
function flipX() {
  const canvasRef = getFabricCanvas()
  if (!canvasRef?.value) return
  const obj = canvasRef.value.getActiveObject()
  if (obj) {
    obj.set('flipX', !obj.flipX)
    canvasRef.value.renderAll()
  }
}

/**
 * @description Espelha o objeto selecionado verticalmente (eixo Y).
 */
function flipY() {
  const canvasRef = getFabricCanvas()
  if (!canvasRef?.value) return
  const obj = canvasRef.value.getActiveObject()
  if (obj) {
    obj.set('flipY', !obj.flipY)
    canvasRef.value.renderAll()
  }
}

/**
 * @description Remove todos os objetos selecionados do canvas e limpa a seleção na store.
 */
function deleteObj() {
  const canvasRef = getFabricCanvas()
  if (!canvasRef?.value) return
  const active = canvasRef.value.getActiveObjects()
  if (active.length) {
    active.forEach((obj: any) => canvasRef.value!.remove(obj))
    canvasRef.value.discardActiveObject()
    canvasRef.value.renderAll()
    store.clearSelection()
  }
}

/** Indica se o objeto selecionado é um elemento de texto (i-text, textbox ou text) */
const isText = computed(() => {
  const obj = store.selectedObject
  return obj && (obj.type === 'i-text' || obj.type === 'textbox' || obj.type === 'text')
})
</script>

<template>
  <aside v-if="hasSelection" class="canvas-sidebar">
    <div class="canvas-sidebar__header">
      <h3 class="canvas-sidebar__title">Propriedades</h3>
      <button class="canvas-sidebar__close" @click="store.clearSelection()">✕</button>
    </div>

    <div class="canvas-sidebar__section">
      <span class="canvas-sidebar__label">Tipo: {{ objectName }}</span>
    </div>

    <div class="canvas-sidebar__section">
      <h4 class="canvas-sidebar__section-title">Aparência</h4>

      <div class="canvas-sidebar__field">
        <label class="canvas-sidebar__field-label">Preenchimento</label>
        <div class="canvas-sidebar__field-row">
          <input
            type="color"
            :value="fillColor"
            class="canvas-sidebar__color"
            @input="handleFillChange"
          />
          <span class="canvas-sidebar__color-value">{{ fillColor }}</span>
        </div>
      </div>

      <div class="canvas-sidebar__field">
        <label class="canvas-sidebar__field-label">Contorno</label>
        <div class="canvas-sidebar__field-row">
          <input
            type="color"
            :value="strokeColor"
            class="canvas-sidebar__color"
            @input="handleStrokeChange"
          />
          <span class="canvas-sidebar__color-value">{{ strokeColor }}</span>
        </div>
      </div>

      <div class="canvas-sidebar__field">
        <label class="canvas-sidebar__field-label">Espessura do contorno</label>
        <input
          type="range"
          :value="strokeWidth"
          min="0"
          max="20"
          class="canvas-sidebar__range"
          @input="handleStrokeWidthChange"
        />
      </div>

      <div class="canvas-sidebar__field">
        <label class="canvas-sidebar__field-label">Opacidade: {{ opacity }}%</label>
        <input
          type="range"
          :value="opacity"
          min="0"
          max="100"
          class="canvas-sidebar__range"
          @input="handleOpacityChange"
        />
      </div>
    </div>

    <div v-if="isText" class="canvas-sidebar__section">
      <h4 class="canvas-sidebar__section-title">Texto</h4>

      <div class="canvas-sidebar__field">
        <label class="canvas-sidebar__field-label">Tamanho da fonte</label>
        <input
          type="number"
          :value="fontSize"
          min="8"
          max="200"
          class="canvas-sidebar__input"
          @input="handleFontSizeChange"
        />
      </div>

      <div class="canvas-sidebar__field">
        <label class="canvas-sidebar__field-label">Família da fonte</label>
        <select :value="fontFamily" class="canvas-sidebar__select" @change="handleFontFamilyChange">
          <option value="Arial">Arial</option>
          <option value="Helvetica">Helvetica</option>
          <option value="Times New Roman">Times New Roman</option>
          <option value="Courier New">Courier New</option>
          <option value="Georgia">Georgia</option>
          <option value="Verdana">Verdana</option>
        </select>
      </div>
    </div>

    <div class="canvas-sidebar__section">
      <h4 class="canvas-sidebar__section-title">Posição</h4>

      <div class="canvas-sidebar__field-row">
        <div class="canvas-sidebar__field">
          <label class="canvas-sidebar__field-label">X</label>
          <input
            type="number"
            :value="objectLeft"
            class="canvas-sidebar__input"
            @input="handleLeftChange"
          />
        </div>
        <div class="canvas-sidebar__field">
          <label class="canvas-sidebar__field-label">Y</label>
          <input
            type="number"
            :value="objectTop"
            class="canvas-sidebar__input"
            @input="handleTopChange"
          />
        </div>
      </div>

      <div class="canvas-sidebar__field">
        <label class="canvas-sidebar__field-label">Rotação: {{ objectAngle }}°</label>
        <input
          type="range"
          :value="objectAngle"
          min="0"
          max="360"
          class="canvas-sidebar__range"
          @input="handleAngleChange"
        />
      </div>
    </div>

    <div class="canvas-sidebar__section">
      <h4 class="canvas-sidebar__section-title">Transformar</h4>
      <div class="canvas-sidebar__field-row">
        <button class="canvas-sidebar__btn" @click="flipX">Espelhar X</button>
        <button class="canvas-sidebar__btn" @click="flipY">Espelhar Y</button>
      </div>
    </div>

    <div class="canvas-sidebar__section canvas-sidebar__section--danger">
      <button class="canvas-sidebar__btn canvas-sidebar__btn--danger" @click="deleteObj">
        Excluir objeto
      </button>
    </div>
  </aside>
</template>

<style scoped>
.canvas-sidebar {
  width: 220px;
  background: var(--color-bg-card);
  border-left: 1px solid var(--color-border);
  padding: var(--spacing-sm) var(--spacing-sm);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  flex-shrink: 0;
  border-top: none;
}

.canvas-sidebar__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.canvas-sidebar__title {
  font-size: var(--text-sm);
  font-weight: 600;
  margin: 0;
}

.canvas-sidebar__close {
  background: none;
  border: none;
  color: var(--color-text-muted);
  cursor: pointer;
  font-size: var(--text-sm);
  padding: var(--spacing-2xs);
}

.canvas-sidebar__close:hover {
  color: var(--color-text);
}

.canvas-sidebar__section {
  padding-top: var(--spacing-xs);
  border-top: 1px solid var(--color-border);
}

.canvas-sidebar__section--danger {
  margin-top: auto;
}

.canvas-sidebar__section-title {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-xs);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.canvas-sidebar__label {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.canvas-sidebar__field {
  margin-bottom: var(--spacing-xs);
}

.canvas-sidebar__field-label {
  display: block;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin-bottom: var(--spacing-2xs);
}

.canvas-sidebar__field-row {
  display: flex;
  gap: var(--spacing-xs);
  align-items: center;
}

.canvas-sidebar__color {
  width: 1.5rem;
  height: 1.5rem;
  padding: 0;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  cursor: pointer;
}

.canvas-sidebar__color-value {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  font-family: monospace;
}

.canvas-sidebar__range {
  width: 100%;
}

.canvas-sidebar__input {
  width: 100%;
  height: 1.75rem;
  padding: 0 var(--spacing-xs);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: var(--color-bg);
  color: var(--color-text);
  font-size: var(--text-xs);
}

.canvas-sidebar__select {
  width: 100%;
  height: 1.75rem;
  padding: 0 var(--spacing-xs);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: var(--color-bg);
  color: var(--color-text);
  font-size: var(--text-xs);
}

.canvas-sidebar__btn {
  flex: 1;
  height: 1.75rem;
  padding: 0 var(--spacing-xs);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: var(--color-bg);
  color: var(--color-text);
  font-size: var(--text-xs);
  cursor: pointer;
  transition: all var(--duration-fast) ease;
}

.canvas-sidebar__btn:hover {
  background: var(--color-bg-soft);
}

.canvas-sidebar__btn--danger {
  color: var(--color-error);
  border-color: color-mix(in srgb, var(--color-error) 30%, transparent);
}

.canvas-sidebar__btn--danger:hover {
  background: var(--color-error-soft);
}

@media (max-width: 640px) {
  .canvas-sidebar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    max-height: 60vh;
    border-left: none;
    border-top: 1px solid var(--color-border);
    border-radius: var(--radius-xl) var(--radius-xl) 0 0;
    z-index: 100;
    box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.12);
    padding-top: 0;
  }
  .canvas-sidebar__header {
    padding: var(--spacing-sm) var(--spacing-md);
    position: relative;
  }
  .canvas-sidebar__header::before {
    content: '';
    display: block;
    width: 32px;
    height: 4px;
    border-radius: 2px;
    background: var(--color-text-muted);
    opacity: 0.25;
    margin: 0 auto var(--spacing-sm);
  }
}
</style>
