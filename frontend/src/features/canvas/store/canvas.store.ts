/**
 * @module canvas.store
 * @description Store Pinia do módulo de canvas.
 *
 * Gerencia o estado global do editor de canvas, incluindo a ferramenta
 * ativa, nível de zoom, objeto selecionado, cores, propriedades de texto,
 * histórico de ações e status de inicialização.
 */
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { CanvasTool } from '../types/canvas.types'

export const useCanvasStore = defineStore('canvas', () => {
  /** Ferramenta ativa no editor (seleção, desenho, formas, etc.) */
  const activeTool = ref<CanvasTool>('select')
  /** Indica se há ações anteriores disponíveis para desfazer */
  const canUndo = ref(false)
  /** Indica se há ações posteriores disponíveis para refazer */
  const canRedo = ref(false)
  /** Nível de zoom atual do canvas (10–500, em percentual) */
  const zoom = ref(100)
  /** Dados serializados do objeto selecionado no canvas, ou `null` */
  const selectedObject = ref<any>(null)
  /** Indica se o canvas está em modo de desenho livre */
  const isDrawing = ref(false)
  /** Índice atual na pilha de histórico de ações */
  const historyIndex = ref(-1)
  /** Quantidade total de entradas no histórico */
  const historyLength = ref(0)
  /** Cor de preenchimento atual para novos objetos */
  const fillColor = ref('#fafafa')
  /** Cor de contorno atual para novos objetos */
  const strokeColor = ref('#fafafa')
  /** Espessura do contorno em pixels para novos objetos */
  const strokeWidth = ref(2)
  /** Tamanho da fonte em pixels para novos objetos de texto */
  const fontSize = ref(24)
  /** Família tipográfica para novos objetos de texto */
  const fontFamily = ref('Arial')
  /** Indica se o canvas Fabric.js foi inicializado com sucesso */
  const canvasReady = ref(false)

  /** `true` quando há um objeto selecionado no canvas */
  const hasSelection = computed(() => selectedObject.value !== null)

  /**
   * @description Define a ferramenta ativa no editor de canvas.
   * @param tool - Identificador da ferramenta a ser ativada
   */
  function setTool(tool: CanvasTool) {
    activeTool.value = tool
  }

  /**
   * @description Armazena os dados serializados do objeto selecionado no canvas.
   * @param obj - Objeto JSON serializado do Fabric.js representando a seleção
   */
  function setSelectedObject(obj: any) {
    selectedObject.value = obj
  }

  /**
   * @description Remove a seleção atual, definindo o objeto selecionado como nulo.
   */
  function clearSelection() {
    selectedObject.value = null
  }

  /**
   * @description Atualiza o estado do histórico de ações (undo/redo).
   * @param index - Índice atual na pilha de histórico
   * @param length - Quantidade total de entradas no histórico
   */
  function updateHistory(index: number, length: number) {
    historyIndex.value = index
    historyLength.value = length
    canUndo.value = index > 0
    canRedo.value = index < length - 1
  }

  /**
   * @description Define o nível de zoom do canvas, limitando entre 10% e 500%.
   * @param value - Percentual de zoom desejado (10–500)
   */
  function setZoom(value: number) {
    zoom.value = Math.min(500, Math.max(10, value))
  }

  return {
    activeTool,
    canUndo,
    canRedo,
    zoom,
    selectedObject,
    isDrawing,
    historyIndex,
    historyLength,
    fillColor,
    strokeColor,
    strokeWidth,
    fontSize,
    fontFamily,
    canvasReady,
    hasSelection,
    setTool,
    setSelectedObject,
    clearSelection,
    updateHistory,
    setZoom,
  }
})
