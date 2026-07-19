/**
 * @module useCanvas
 * @description Composable principal do editor de canvas Fabric.js.
 *
 * Fornece todas as funcionalidades necessárias para manipulação do canvas:
 * inicialização, ferramentas de desenho, formas geométricas, texto, imagens,
 * sistema de histórico (undo/redo), zoom, serialização JSON, integração com
 * mural de imagens e importação/exportação de organogramas.
 */
import { watch, onBeforeUnmount, type Ref } from 'vue'
import { Canvas, PencilBrush, Rect, Circle, Triangle, Line, IText, Textbox, Image as FabricImage } from 'fabric'
import { useCanvasStore } from '../store/canvas.store'
import { setFabricCanvas } from './canvasInstance'
import type { CanvasTool } from '../types/canvas.types'
import { handleError } from '@/utils/handleError'

const MURAL_PREFIX = 'studytrack.mural.'

/**
 * @description Composable principal que gerencia toda a lógica do editor de canvas Fabric.js.
 *
 * Inicializa o canvas, configura ferramentas de desenho, gerencia formas geométricas,
 * texto, imagens, sistema de histórico (undo/redo), zoom, serialização JSON e
 * integração com mural de imagens e organogramas.
 *
 * @param canvasEl - Referência reativa Vue do elemento `<canvas>` HTML
 * @returns Objeto com todas as funções e reatividades do canvas para uso nos componentes
 */
export function useCanvas(canvasEl: Ref<HTMLCanvasElement | undefined>) {
  let _canvas: Canvas | null = null
  const store = useCanvasStore()
  let resizeObserver: ResizeObserver | null = null
  const history: string[] = []
  let historyIndex = -1
  let isRestoringHistory = false

  const fabricCanvas = {
    get value() { return _canvas },
  }

  /**
   * @description Salva o estado atual do canvas no histórico de ações.
   * Serializa o canvas como JSON e mantém apenas o histórico a partir do
   * ponto de desfazer mais recente, descartando entradas futuras.
   */
  function saveHistory() {
    if (!_canvas || isRestoringHistory) return
    const json = JSON.stringify(_canvas.toJSON())
    history.splice(historyIndex + 1)
    history.push(json)
    historyIndex = history.length - 1
    store.updateHistory(historyIndex, history.length)
  }

  /**
   * @description Inicializa a instância do canvas Fabric.js.
   *
   * Configura dimensões, cor de fundo, opções de seleção e registra
   * listeners para eventos de seleção, modificação de objetos e criação
   * de caminhos. Também configura o observer de redimensionamento responsivo.
   */
  function initCanvas() {
    const el = canvasEl.value
    if (!el || _canvas) return

    const parent = el.parentElement
    const w = parent?.clientWidth || 800
    const h = parent?.clientHeight || 600

    _canvas = new Canvas(el, {
      width: w,
      height: h,
      backgroundColor: '#ffffff',
      selection: true,
      selectionColor: 'rgba(139,92,246,0.1)',
      selectionBorderColor: '#8b5cf6',
      selectionLineWidth: 2,
    })

    const c = _canvas

    c.on('selection:created', (e: any) => {
      const obj = e.selected?.[0]
      if (obj) store.setSelectedObject(obj.toJSON())
    })
    c.on('selection:updated', (e: any) => {
      const obj = e.selected?.[0]
      if (obj) store.setSelectedObject(obj.toJSON())
    })
    c.on('selection:cleared', () => store.clearSelection())
    c.on('object:modified', () => saveHistory())
    c.on('path:created', () => saveHistory())

    store.canvasReady = true
    setFabricCanvas(_canvas ? { value: _canvas } as any : null)
    saveHistory()

    let resizeTimer: ReturnType<typeof setTimeout> | null = null
    resizeObserver = new ResizeObserver(() => {
      if (resizeTimer) clearTimeout(resizeTimer)
      resizeTimer = setTimeout(() => {
        if (!c || !parent) return
        const nw = parent.clientWidth, nh = parent.clientHeight
        if (nw > 0 && nh > 0 && (c.width !== nw || c.height !== nh)) {
          c.setDimensions({ width: nw, height: nh })
        }
      }, 150)
    })
    if (parent) resizeObserver.observe(parent)
  }

  watch(canvasEl, (el) => { if (el) initCanvas() }, { immediate: true })

  /**
   * @description Desfaz a última ação, restaurando o estado anterior do canvas.
   * Não executa se já estiver no início do histórico.
   */
  function undo() {
    if (historyIndex <= 0 || !_canvas) return
    isRestoringHistory = true
    historyIndex--
    _canvas.loadFromJSON(JSON.parse(history[historyIndex])).then(() => {
      _canvas?.renderAll()
      isRestoringHistory = false
      store.updateHistory(historyIndex, history.length)
    }).catch(() => { isRestoringHistory = false })
  }

  /**
   * @description Refaz a ação desfeita, avançando para o próximo estado do histórico.
   * Não executa se já estiver no final do histórico.
   */
  function redo() {
    if (historyIndex >= history.length - 1 || !_canvas) return
    isRestoringHistory = true
    historyIndex++
    _canvas.loadFromJSON(JSON.parse(history[historyIndex])).then(() => {
      _canvas?.renderAll()
      isRestoringHistory = false
      store.updateHistory(historyIndex, history.length)
    }).catch(() => { isRestoringHistory = false })
  }

  /**
   * @description Define a ferramenta ativa no canvas e configura o modo apropriado.
   *
   * Para ferramentas de desenho (pencil/eraser), ativa o modo de desenho livre.
   * Para outras ferramentas, garante que o canvas esteja no modo de seleção padrão.
   *
   * @param tool - Identificador da ferramenta a ser ativada
   */
  function setTool(tool: CanvasTool) {
    if (!_canvas) return
    store.setTool(tool)
    _canvas.isDrawingMode = false
    _canvas.selection = true
    _canvas.defaultCursor = 'default'
    _canvas.hoverCursor = 'default'
    _canvas.forEachObject((obj: any) => { obj.selectable = true; obj.evented = true })
    if (_canvas.freeDrawingBrush) {
      try { _canvas.freeDrawingBrush = null as any } catch { _canvas.freeDrawingBrush = undefined as any }
    }
    if (tool === 'pencil') {
      _canvas.isDrawingMode = true
      _canvas.freeDrawingBrush = new PencilBrush(_canvas)
      _canvas.freeDrawingBrush.color = store.strokeColor
      _canvas.freeDrawingBrush.width = store.strokeWidth
      _canvas.defaultCursor = 'crosshair'
    } else if (tool === 'eraser') {
      _canvas.isDrawingMode = true
      _canvas.freeDrawingBrush = new PencilBrush(_canvas)
      _canvas.freeDrawingBrush.color = '#ffffff'
      _canvas.freeDrawingBrush.width = store.strokeWidth * 3
      _canvas.defaultCursor = 'crosshair'
    }
  }

  /**
   * @description Adiciona uma forma geométrica ao centro do canvas.
   *
   * Cria retângulos, círculos, triângulos ou linhas com as cores e espessura
   * atuais definidas na store. A forma é posicionada no centro visível do canvas.
   *
   * @param type - Tipo da forma: 'rect', 'circle', 'triangle' ou 'line'
   */
  function addShape(type: string) {
    if (!_canvas) return
    const cx = (_canvas.width || 800) / 2, cy = (_canvas.height || 600) / 2
    const base = { fill: store.fillColor, stroke: store.strokeColor, strokeWidth: store.strokeWidth, selectable: true }
    let obj: any = null
    if (type === 'rect') obj = new Rect({ ...base, left: cx - 50, top: cy - 40, width: 100, height: 80 })
    else if (type === 'circle') obj = new Circle({ ...base, left: cx - 40, top: cy - 40, radius: 40 })
    else if (type === 'triangle') obj = new Triangle({ ...base, left: cx - 50, top: cy - 40, width: 100, height: 80 })
    else if (type === 'line') obj = new Line([cx - 60, cy, cx + 60, cy], { stroke: store.strokeColor, strokeWidth: store.strokeWidth, selectable: true })
    if (obj) {
      _canvas.add(obj)
      _canvas.setActiveObject(obj)
      obj.setCoords()
      _canvas.renderAll()
    }
  }

  /**
   * @description Adiciona um objeto de texto editável ao centro do canvas.
   *
   * @param type - Tipo do texto: 'text' para texto inline ou 'textbox' para caixa com quebra de linha
   */
  function addText(type: 'text' | 'textbox' = 'text') {
    if (!_canvas) return
    const cx = (_canvas.width || 800) / 2, cy = (_canvas.height || 600) / 2
    const opts = { left: cx - 60, top: cy - 15, fontSize: store.fontSize, fontFamily: store.fontFamily, fill: store.fillColor, selectable: true, editable: true }
    const obj = type === 'textbox' ? new Textbox('Digite aqui...', { ...opts, width: 200 }) : new IText('Texto', opts)
    _canvas.add(obj)
    _canvas.setActiveObject(obj)
    obj.setCoords()
    _canvas.renderAll()
  }

  /**
   * @description Adiciona uma nota adesiva (sticky note) ao centro do canvas.
   *
   * Cria um retângulo amarelo arredondado com um campo de texto editável.
   */
  function addStickyNote() {
    if (!_canvas) return
    const cx = (_canvas.width || 800) / 2, cy = (_canvas.height || 600) / 2
    const bg = new Rect({ left: cx - 75, top: cy - 75, width: 150, height: 150, fill: '#FEF08A', stroke: '#EAB308', strokeWidth: 1, rx: 8, ry: 8, selectable: true })
    const txt = new Textbox('Nota', { left: cx - 65, top: cy - 65, width: 130, fontSize: 16, fontFamily: 'Arial', fill: '#fafafa', selectable: true, editable: true })
    _canvas.add(bg, txt)
    _canvas.setActiveObject(bg)
    bg.setCoords()
    _canvas.renderAll()
  }

  /**
   * @description Adiciona um marcador de destaque semitransparente ao centro do canvas.
   *
   * Cria um retângulo com preenchimento amarelo semitransparente,
   * útil para destacar porções de texto ou imagem.
   */
  function addHighlight() {
    if (!_canvas) return
    const cx = (_canvas.width || 800) / 2, cy = (_canvas.height || 600) / 2
    const obj = new Rect({ left: cx - 80, top: cy - 15, width: 160, height: 30, fill: 'rgba(250,204,21,0.4)', stroke: 'transparent', strokeWidth: 0, selectable: true })
    _canvas.add(obj)
    _canvas.setActiveObject(obj)
    obj.setCoords()
    _canvas.renderAll()
  }

  /**
   * @description Lê um arquivo de imagem e a adiciona ao canvas.
   *
   * Converte o arquivo para Data URL usando FileReader e o delega
   * para `addImageFromURL`.
   *
   * @param file - Arquivo de imagem selecionado pelo usuário
   */
  function addImage(file: File) {
    const reader = new FileReader()
    reader.onload = (e) => addImageFromURL(e.target?.result as string)
    reader.readAsDataURL(file)
  }

  /**
   * @description Adiciona uma imagem a partir de uma URL ao centro do canvas.
   *
   * Redimensiona automaticamente a imagem para caber dentro de 80% do canvas,
   * mantendo a proporção original.
   *
   * @param url - URL ou Data URL da imagem a ser adicionada
   */
  function addImageFromURL(url: string) {
    if (!_canvas || !url) return
    FabricImage.fromURL(url, { crossOrigin: 'anonymous' }).then((img: any) => {
      const cw = _canvas!.width || 800, ch = _canvas!.height || 600
      const scale = Math.min((cw * 0.8) / (img.width || 1), (ch * 0.8) / (img.height || 1), 1)
      img.scale(scale)
      img.set({ left: (cw - (img.width || 0) * scale) / 2, top: (ch - (img.height || 0) * scale) / 2, selectable: true })
      _canvas!.add(img)
      _canvas!.setActiveObject(img)
      img.setCoords()
      _canvas!.renderAll()
    }).catch(handleError('useCanvas-loadImage'))
  }

  /**
   * @description Remove todos os objetos selecionados do canvas.
   *
   * Descarta a seleção ativa e salva o estado no histórico.
   */
  function deleteSelected() {
    if (!_canvas) return
    _canvas.getActiveObjects().forEach((obj: any) => _canvas!.remove(obj))
    _canvas.discardActiveObject()
    _canvas.renderAll()
    saveHistory()
  }

  /**
   * @description Atualiza uma propriedade do objeto ativamente selecionado no canvas.
   *
   * @param prop - Nome da propriedade a ser alterada (ex: 'fill', 'fontSize')
   * @param value - Novo valor para a propriedade
   */
  function updateObjectProp(prop: string, value: any) {
    if (!_canvas) return
    const obj = _canvas.getActiveObject()
    if (obj) { obj.set(prop, value); _canvas.renderAll(); saveHistory() }
  }

  /**
   * @description Aumenta o zoom do canvas em 10%, limitado a 500%.
   */
  function zoomIn() {
    if (!_canvas) return
    const z = Math.min(5, _canvas.getZoom() * 1.1)
    _canvas.zoomToPoint({ x: (_canvas.width || 0) / 2, y: (_canvas.height || 0) / 2 } as any, z)
    store.setZoom(Math.round(z * 100))
  }

  /**
   * @description Diminui o zoom do canvas em 10%, limitado a 10%.
   */
  function zoomOut() {
    if (!_canvas) return
    const z = Math.max(0.1, _canvas.getZoom() * 0.9)
    _canvas.zoomToPoint({ x: (_canvas.width || 0) / 2, y: (_canvas.height || 0) / 2 } as any, z)
    store.setZoom(Math.round(z * 100))
  }

  /**
   * @description Reseta o zoom do canvas para 100% (escala original).
   */
  function zoomReset() {
    if (!_canvas) return
    _canvas.setViewportTransform([1, 0, 0, 1, 0, 0])
    store.setZoom(100)
  }

  /**
   * @description Limpa todos os objetos do canvas e redefine o fundo como branco.
   * Salva o estado limpo no histórico de ações.
   */
  function clearCanvas() {
    if (!_canvas) return
    _canvas.clear()
    _canvas.backgroundColor = '#ffffff'
    _canvas.renderAll()
    saveHistory()
  }

  /**
   * @description Serializa o estado completo do canvas como objeto JSON.
   * @returns Objeto JSON com todos os objetos e configurações do canvas, ou `null`
   */
  function toJSON() { return _canvas?.toJSON() || null }

  /**
   * @description Restaura o estado do canvas a partir de um objeto JSON previamente serializado.
   * @param json - Objeto JSON representando o estado do canvas
   */
  function fromJSON(json: any) {
    if (!_canvas) return
    isRestoringHistory = true
    _canvas.loadFromJSON(json).then(() => { _canvas?.renderAll(); isRestoringHistory = false; saveHistory() }).catch(() => { isRestoringHistory = false })
  }
  /**
   * @description Exporta o canvas como Data URL (base64).
   * @param opts - Opções de exportação: formato (png/jpeg), qualidade e multiplicador de resolução
   * @returns String Data URL da imagem, ou string vazia se o canvas não estiver disponível
   */
  function toDataURL(opts?: { format?: string; quality?: number; multiplier?: number }) { return _canvas?.toDataURL(opts as any) || '' }

  /**
   * @description Exporta o canvas como SVG (Scalable Vector Graphics).
   * @returns String SVG do conteúdo do canvas, ou string vazia se não disponível
   */
  function toSVG() { return _canvas?.toSVG() || '' }

  /**
   * @description Salva o estado do canvas no armazenamento local do navegador.
   * @param key - Chave do localStorage onde o JSON será armazenado
   */
  function saveToLocalStorage(key: string) { const j = toJSON(); if (j) localStorage.setItem(key, JSON.stringify(j)) }

  /**
   * @description Restaura o estado do canvas a partir do armazenamento local do navegador.
   * @param key - Chave do localStorage de onde o JSON será lido
   */
  function loadFromLocalStorage(key: string) { const r = localStorage.getItem(key); if (r) try { fromJSON(JSON.parse(r)) } catch {} }

  /**
   * @description Recupera as URLs das imagens salvas no mural para uma tecnologia específica.
   *
   * @param tid - ID da tecnologia (technology id)
   * @returns Array de URLs de imagens encontradas no mural
   */
  function getMuralImages(tid: string): string[] {
    const r = localStorage.getItem(`${MURAL_PREFIX}${tid}`)
    if (!r) return []
    try { return JSON.parse(r).filter((i: any) => i.type === 'image' && i.url).map((i: any) => i.url) } catch { return [] }
  }

  /**
   * @description Adiciona ao canvas uma imagem previamente salva no mural.
   *
   * @param _tid - ID da tecnologia (não utilizado internamente, mantido para consistência da API)
   * @param imageUrl - Data URL da imagem a ser adicionada
   */
  function addImageFromMural(_tid: string, imageUrl: string) { addImageFromURL(imageUrl) }

  /**
   * @description Exporta o estado atual do canvas como imagem e salva no mural da tecnologia.
   *
   * Captura o canvas como PNG com resolução 2x e adiciona ao array de
   * itens do mural associado ao ID da tecnologia.
   *
   * @param tid - ID da tecnologia (technology id)
   */
  function saveToMural(tid: string) {
    const d = toDataURL({ format: 'png', quality: 1, multiplier: 2 })
    if (!d) return
    const k = `${MURAL_PREFIX}${tid}`
    let items: any[] = []
    try { items = JSON.parse(localStorage.getItem(k) || '[]') } catch { items = [] }
    items.push({ id: `canvas-${Date.now()}`, type: 'image', url: d })
    localStorage.setItem(k, JSON.stringify(items))
  }

  /**
   * @description Importa dados de um organograma (mapa de estudos) e os converte em objetos de canvas.
   *
   * Lê os nós e arestas do organograma armazenados no localStorage e cria
   * retângulos com rótulos para os nós e linhas para as conexões.
   *
   * @param tid - ID da tecnologia (technology id) para localizar os dados do organograma
   */
  function importFromOrganogram(tid: string) {
    const r = localStorage.getItem(`studytrack.study-flow.v1.${tid}`)
    if (!r || !_canvas) return
    try {
      const { nodes = [], edges = [] } = JSON.parse(r)
      const nodeMap = new Map<string, { x: number; y: number }>()
      nodes.forEach((n: any) => {
        const x = n.position?.x || 0, y = n.position?.y || 0
        nodeMap.set(n.id, { x, y })
        const rect = new Rect({ left: x, top: y, width: 180, height: 60, fill: '#1c1c1f', stroke: '#8b5cf6', strokeWidth: 2, rx: 8, ry: 8, selectable: true })
        rect.setCoords()
        _canvas!.add(rect)
        const txt = new IText(n.data?.label || n.id, { left: x + 10, top: y + 20, fontSize: 14, fontFamily: 'Arial', fill: '#8b5cf6', selectable: false, evented: false })
        _canvas!.add(txt)
      })
      edges.forEach((e: any) => {
        const from = nodeMap.get(e.source), to = nodeMap.get(e.target)
        if (from && to) _canvas!.add(new Line([from.x + 90, from.y + 60, to.x + 90, to.y], { stroke: '#a1a1aa', strokeWidth: 2, selectable: false, evented: false }))
      })
      _canvas.renderAll()
      saveHistory()
    } catch {}
  }

  /**
   * @description Exporta o conteúdo do canvas como dados de organograma (nós e arestas).
   *
   * Analisa retângulos e linhas do canvas para reconstruir o grafo do organograma,
   * associando textos próximos como rótulos dos nós.
   *
   * @param tid - ID da tecnologia (technology id) para armazenar os dados exportados
   */
  function exportToOrganogram(tid: string) {
    if (!_canvas) return
    const objects = _canvas.getObjects(), nodes: any[] = [], edges: any[] = []
    const centers = new Map<string, { x: number; y: number }>()
    objects.forEach((obj: any, i: number) => {
      if (obj.type === 'rect') {
        const id = `node-${i}`, x = obj.left || 0, y = obj.top || 0
        centers.set(id, { x: x + 90, y: y + 30 })
        const txt = objects.find((o: any) => (o.type === 'i-text' || o.type === 'text') && Math.abs((o.left || 0) - (x + 10)) < 20 && Math.abs((o.top || 0) - (y + 20)) < 20) as any
        nodes.push({ id, type: 'default', position: { x, y }, data: { label: txt?.text ?? 'Nó' } })
      }
    })
    objects.forEach((obj: any, i: number) => {
      if (obj.type === 'line' && (obj.points?.length ?? 0) >= 4) {
        let fromId = '', toId = ''
        centers.forEach((ct: { x: number; y: number }, id: string) => {
          if (Math.abs(obj.points![0] - ct.x) < 5 && Math.abs(obj.points![1] - ct.y) < 5) fromId = id
          if (Math.abs(obj.points![2] - ct.x) < 5 && Math.abs(obj.points![3] - ct.y) < 5) toId = id
        })
        if (fromId && toId) edges.push({ id: `edge-${i}`, source: fromId, target: toId })
      }
    })
    if (nodes.length) localStorage.setItem(`studytrack.study-flow.v1.${tid}`, JSON.stringify({ nodes, edges }))
  }

  /**
   * @description Dispara o download do canvas como arquivo PNG com resolução 2x.
   */
  function downloadPNG() { const u = toDataURL({ format: 'png', quality: 1, multiplier: 2 }); if (u) { const a = document.createElement('a'); a.href = u; a.download = 'canvas.png'; a.click() } }

  /**
   * @description Dispara o download do canvas como arquivo SVG vetorial.
   */
  function downloadSVG() { const s = toSVG(); if (s) { const url = URL.createObjectURL(new Blob([s], { type: 'image/svg+xml' })); const a = document.createElement('a'); a.href = url; a.download = 'canvas.svg'; a.click(); setTimeout(() => URL.revokeObjectURL(url), 100) } }

  /**
   * @description Dispara o download do estado do canvas como arquivo JSON formatado.
   */
  function downloadJSON() { const j = JSON.stringify(toJSON(), null, 2); const url = URL.createObjectURL(new Blob([j], { type: 'application/json' })); const a = document.createElement('a'); a.href = url; a.download = 'canvas.json'; a.click(); setTimeout(() => URL.revokeObjectURL(url), 100) }

  onBeforeUnmount(() => { resizeObserver?.disconnect(); resizeObserver = null; _canvas?.dispose(); _canvas = null; setFabricCanvas(null as any) })

  return {
    canvas: fabricCanvas,
    setTool, addShape, addText, addStickyNote, addHighlight,
    addImage, addImageFromURL, addImageFromMural,
    deleteSelected, updateObjectProp,
    undo, redo, zoomIn, zoomOut, zoomReset, clearCanvas,
    toJSON, fromJSON, toDataURL, toSVG,
    saveToLocalStorage, loadFromLocalStorage,
    getMuralImages, saveToMural,
    importFromOrganogram, exportToOrganogram,
    downloadPNG, downloadSVG, downloadJSON,
  }
}
