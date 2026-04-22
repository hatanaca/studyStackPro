<script setup lang="ts">
/**
 * Mapa de estudos: persistência local, import/export JSON, Vue Flow e edição num único componente.
 */
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { VueFlow, addEdge, isEdge, useVueFlow } from '@vue-flow/core'
import type {
  Connection,
  Edge,
  EdgeMouseEvent,
  Node,
  NodeMouseEvent,
  XYPosition,
} from '@vue-flow/core'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from '@/composables/useToast'
import { normalizeStudyPathNode, type TopicNodeData } from '@/features/study-path/studyPathNode'

import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'

const SAVE_DEBOUNCE_MS = 400

const props = defineProps<{
  technologyId: string
}>()

const toast = useToast()
const confirm = useConfirm()
const { fitView, project, vueFlowRef } = useVueFlow()

function storageKey(): string {
  return `studytrack.study-flow.v1.${props.technologyId}`
}

const defaultNodesTemplate: Node[] = [
  { id: '1', position: { x: 100, y: 120 }, data: { label: 'Tópico inicial' } },
  { id: '2', position: { x: 420, y: 120 }, data: { label: 'Próximo passo' } },
]
const defaultNodes = defaultNodesTemplate.map((n) => normalizeStudyPathNode({ ...n }))
const defaultEdges: Edge[] = [{ id: 'e-1-2', source: '1', target: '2' }]

const nodes = ref<Node[]>([])
const edges = ref<Edge[]>([])

const importOpen = ref(false)
const importText = ref('')

const selectedNodeId = ref<string | null>(null)
const barLabel = ref('')
const barInputRef = ref<{ $el?: HTMLElement } | null>(null)
const ctxMenu = ref<{ x: number; y: number; flow: XYPosition } | null>(null)

/** Zoom com roda só depois de clicar dentro da área do mapa (evita capturar scroll da página). */
const mapWheelFocused = ref(false)
const mapViewportRef = ref<HTMLElement | null>(null)

function onDocumentPointerDownCapture(ev: PointerEvent) {
  const root = mapViewportRef.value
  const t = ev.target
  if (root && t instanceof Node && root.contains(t)) {
    mapWheelFocused.value = true
  } else {
    mapWheelFocused.value = false
  }
}

interface StoredFlow {
  nodes: Node[]
  edges: Edge[]
}

function isRecord(v: unknown): v is Record<string, unknown> {
  return v !== null && typeof v === 'object'
}

function parseStored(json: unknown): StoredFlow | null {
  if (!isRecord(json)) return null
  const n = json.nodes
  const e = json.edges
  if (!Array.isArray(n) || !Array.isArray(e)) return null
  return { nodes: n as Node[], edges: e as Edge[] }
}

function loadFromStorage() {
  if (!props.technologyId) return
  try {
    const raw = localStorage.getItem(storageKey())
    if (!raw) {
      nodes.value = defaultNodes.map((x) => normalizeStudyPathNode({ ...x }))
      edges.value = defaultEdges.map((x) => ({ ...x }))
      return
    }
    const parsed = parseStored(JSON.parse(raw))
    if (parsed?.nodes?.length) {
      nodes.value = parsed.nodes.map((n) => normalizeStudyPathNode(n))
      edges.value = parsed.edges ?? []
    } else {
      nodes.value = defaultNodes.map((x) => normalizeStudyPathNode({ ...x }))
      edges.value = defaultEdges.map((x) => ({ ...x }))
    }
  } catch {
    nodes.value = defaultNodes.map((x) => normalizeStudyPathNode({ ...x }))
    edges.value = defaultEdges.map((x) => ({ ...x }))
  }
}

function saveToStorage() {
  if (!props.technologyId) return
  try {
    const payload: StoredFlow = {
      nodes: nodes.value.map((node) => ({ ...node })),
      edges: edges.value.map((edge) => ({ ...edge })),
    }
    localStorage.setItem(storageKey(), JSON.stringify(payload))
  } catch {
    // ignore quota / private mode
  }
}

let saveTimer: ReturnType<typeof setTimeout> | null = null
function scheduleSave() {
  if (saveTimer) clearTimeout(saveTimer)
  saveTimer = setTimeout(() => {
    saveTimer = null
    saveToStorage()
  }, SAVE_DEBOUNCE_MS)
}

watch(
  () => props.technologyId,
  () => {
    if (saveTimer) {
      clearTimeout(saveTimer)
      saveTimer = null
    }
    loadFromStorage()
  },
  { immediate: true }
)

watch([nodes, edges], scheduleSave, { deep: true })

function closeCtxMenu() {
  ctxMenu.value = null
}

/** Converte clientX/Y do browser para coordenadas de fluxo (Vue Flow espera posição relativa ao container). */
function clientToFlowPosition(clientX: number, clientY: number): XYPosition {
  const el = vueFlowRef.value
  const bounds = el?.getBoundingClientRect()
  const relX = bounds ? clientX - bounds.left : clientX
  const relY = bounds ? clientY - bounds.top : clientY
  try {
    const flow = project({ x: relX, y: relY })
    if (!Number.isFinite(flow.x) || !Number.isFinite(flow.y)) return { x: 0, y: 0 }
    return flow
  } catch {
    return { x: 0, y: 0 }
  }
}

function openCtxMenu(clientX: number, clientY: number) {
  const flow = clientToFlowPosition(clientX, clientY)
  ctxMenu.value = { x: clientX, y: clientY, flow }
}

function clearTopicSelection() {
  selectedNodeId.value = null
  barLabel.value = ''
}

const ctxMenuStyleComp = computed(() => {
  if (!ctxMenu.value) return {}
  if (typeof window === 'undefined') {
    return { left: `${ctxMenu.value.x}px`, top: `${ctxMenu.value.y}px` }
  }
  const pad = 8
  const x = Math.max(pad, Math.min(ctxMenu.value.x, window.innerWidth - 216))
  const y = Math.max(pad, Math.min(ctxMenu.value.y, window.innerHeight - 248))
  return { left: `${x}px`, top: `${y}px` }
})

onMounted(() => {
  document.addEventListener('pointerdown', onDocumentPointerDownCapture, true)
  void nextTick(() => {
    void fitView({ padding: 0.3, maxZoom: 0.52, duration: 200 })
  })
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocumentPointerDownCapture, true)
})

function onPaneLeftClick() {
  closeCtxMenu()
  clearTopicSelection()
}

function onNodeClick(e: NodeMouseEvent) {
  closeCtxMenu()
  selectedNodeId.value = e.node.id
  barLabel.value = String((e.node.data as TopicNodeData)?.label ?? '')
}

function onPaneContextMenu(ev: MouseEvent) {
  ev.preventDefault()
  clearTopicSelection()
  openCtxMenu(ev.clientX, ev.clientY)
}

function onNodeContextMenu(e: NodeMouseEvent) {
  e.event.preventDefault()
  closeCtxMenu()
  selectedNodeId.value = e.node.id
  barLabel.value = String((e.node.data as TopicNodeData)?.label ?? '')
  const ev = e.event as MouseEvent
  openCtxMenu(ev.clientX, ev.clientY)
}

function onEdgeContextMenu(e: EdgeMouseEvent) {
  e.event.preventDefault()
  const ev = e.event as MouseEvent
  openCtxMenu(ev.clientX, ev.clientY)
}

function onConnect(c: Connection) {
  const combined = [...nodes.value, ...edges.value]
  addEdge(c, combined)
  edges.value = combined.filter(isEdge)
}

function applyBarLabel() {
  const id = selectedNodeId.value
  if (!id) return
  const text = barLabel.value.trim() || 'Sem título'
  nodes.value = nodes.value.map((n) =>
    n.id === id
      ? normalizeStudyPathNode({
          ...n,
          data: { ...(n.data as TopicNodeData), label: text },
        })
      : n
  )
}

function addNode() {
  const id = `n-${Date.now()}`
  nodes.value = [
    ...nodes.value,
    normalizeStudyPathNode({
      id,
      position: { x: 180 + Math.random() * 220, y: 80 + Math.random() * 160 },
      data: { label: 'Novo tópico', shape: 'rect' },
    }),
  ]
  closeCtxMenu()
}

function addNodeAt(flowPos: XYPosition) {
  const id = `n-${Date.now()}`
  nodes.value = [
    ...nodes.value,
    normalizeStudyPathNode({
      id,
      position: { ...flowPos },
      data: { label: 'Novo tópico', shape: 'rect' },
    }),
  ]
  closeCtxMenu()
}

function clearFlow() {
  nodes.value = defaultNodes.map((x) => normalizeStudyPathNode({ ...x }))
  edges.value = defaultEdges.map((x) => ({ ...x }))
  void nextTick(() => fitView({ padding: 0.3, maxZoom: 0.52, duration: 220 }))
  closeCtxMenu()
}

function layoutHorizontal() {
  const sorted = [...nodes.value].sort((a, b) => String(a.id).localeCompare(String(b.id)))
  nodes.value = sorted.map((n, i) =>
    normalizeStudyPathNode({
      ...n,
      position: { x: 40 + i * 260, y: 160 },
    })
  )
  void nextTick(() => fitView({ padding: 0.28, maxZoom: 0.55, duration: 220 }))
  closeCtxMenu()
}

function layoutVertical() {
  const sorted = [...nodes.value].sort((a, b) => String(a.id).localeCompare(String(b.id)))
  nodes.value = sorted.map((n, i) =>
    normalizeStudyPathNode({
      ...n,
      position: { x: 200, y: 40 + i * 140 },
    })
  )
  void nextTick(() => fitView({ padding: 0.28, maxZoom: 0.55, duration: 220 }))
  closeCtxMenu()
}

function layoutGrid() {
  const sorted = [...nodes.value].sort((a, b) => String(a.id).localeCompare(String(b.id)))
  const n = sorted.length
  const cols = Math.max(1, Math.ceil(Math.sqrt(n)))
  nodes.value = sorted.map((node, i) => {
    const row = Math.floor(i / cols)
    const col = i % cols
    return normalizeStudyPathNode({
      ...node,
      position: { x: 40 + col * 260, y: 40 + row * 160 },
    })
  })
  void nextTick(() => fitView({ padding: 0.28, maxZoom: 0.55, duration: 220 }))
  closeCtxMenu()
}

function fitMap() {
  void fitView({ padding: 0.3, maxZoom: 0.55, duration: 220 })
  closeCtxMenu()
}

function deleteSelectedNode() {
  const id = selectedNodeId.value
  if (!id) return
  confirm.require({
    header: 'Excluir tópico',
    message: 'Este tópico e todas as ligações associadas serão removidos.',
    acceptLabel: 'Excluir',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      nodes.value = nodes.value.filter((n) => n.id !== id)
      edges.value = edges.value.filter((e) => e.source !== id && e.target !== id)
      selectedNodeId.value = null
    },
  })
}

function clearAllEdges() {
  confirm.require({
    header: 'Apagar ligações',
    message: 'Remove todas as ligações entre tópicos. Os tópicos mantêm-se.',
    acceptLabel: 'Apagar ligações',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    accept: () => {
      edges.value = []
    },
  })
  closeCtxMenu()
}

function confirmResetMap() {
  confirm.require({
    header: 'Repor mapa',
    message: 'Volta ao modelo inicial e apaga o mapa atual.',
    acceptLabel: 'Repor',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    acceptProps: { size: 'small' },
    rejectProps: { size: 'small', severity: 'secondary' },
    accept: () => clearFlow(),
  })
}

function exportJson() {
  const text = JSON.stringify({ nodes: nodes.value, edges: edges.value }, null, 2)
  const blob = new Blob([text], { type: 'application/json;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `studytrack-mapa-${props.technologyId}.json`
  a.click()
  URL.revokeObjectURL(url)
  toast.success('JSON exportado.')
  closeCtxMenu()
}

function openImport() {
  importText.value = ''
  importOpen.value = true
  closeCtxMenu()
}

function applyImport() {
  try {
    const parsed = parseStored(JSON.parse(importText.value))
    if (!parsed?.nodes?.length) {
      toast.error('JSON inválido: precisa de `nodes` (array) e opcionalmente `edges`.')
      return
    }
    nodes.value = parsed.nodes.map((n) => normalizeStudyPathNode(n))
    edges.value = parsed.edges ?? []
    saveToStorage()
    importOpen.value = false
    toast.success('Mapa importado.')
  } catch {
    toast.error('Não foi possível ler o JSON.')
  }
}

function focusBarInput() {
  void nextTick(() => {
    const el = barInputRef.value?.$el as HTMLElement | undefined
    const input = el?.querySelector?.('input') as HTMLInputElement | null
    input?.focus()
  })
}

function onNodeDoubleClick(e: NodeMouseEvent) {
  closeCtxMenu()
  selectedNodeId.value = e.node.id
  barLabel.value = String((e.node.data as TopicNodeData)?.label ?? '')
  focusBarInput()
}
</script>

<template>
  <div class="study-path-editor">
    <div class="study-path-editor__shell">
      <div class="study-path-editor__toolbar-row" role="toolbar" aria-label="Ferramentas do mapa">
        <Button
          type="button"
          label="Adicionar tópico"
          size="small"
          class="study-path-editor__tb-btn"
          @click="addNode"
        />
        <Button
          type="button"
          label="Repor mapa"
          size="small"
          severity="secondary"
          outlined
          class="study-path-editor__tb-btn"
          @click="confirmResetMap"
        />
        <span class="study-path-editor__tb-div" aria-hidden="true" />
        <Button
          type="button"
          label="Linha"
          size="small"
          severity="secondary"
          outlined
          class="study-path-editor__tb-btn"
          @click="layoutHorizontal"
        />
        <Button
          type="button"
          label="Coluna"
          size="small"
          severity="secondary"
          outlined
          class="study-path-editor__tb-btn"
          @click="layoutVertical"
        />
        <Button
          type="button"
          label="Grelha"
          size="small"
          severity="secondary"
          outlined
          class="study-path-editor__tb-btn"
          @click="layoutGrid"
        />
        <span class="study-path-editor__tb-div" aria-hidden="true" />
        <Button
          type="button"
          label="Encaixar vista"
          size="small"
          severity="secondary"
          outlined
          class="study-path-editor__tb-btn"
          @click="fitMap"
        />
        <Button
          type="button"
          label="Exportar JSON"
          size="small"
          severity="secondary"
          outlined
          class="study-path-editor__tb-btn"
          @click="exportJson"
        />
        <Button
          type="button"
          label="Importar JSON"
          size="small"
          severity="secondary"
          outlined
          class="study-path-editor__tb-btn"
          @click="openImport"
        />
      </div>

      <Transition name="study-path-fade">
        <div
          v-if="selectedNodeId"
          class="study-path-editor__form"
          role="region"
          aria-label="Menu do tópico selecionado"
          @pointerdown.stop
        >
          <div class="study-path-editor__grid study-path-editor__grid--topic">
            <label class="study-path-editor__grid-label" for="study-path-node-label">Texto</label>
            <InputText
              id="study-path-node-label"
              ref="barInputRef"
              v-model="barLabel"
              class="study-path-editor__grid-input"
              maxlength="120"
              @keydown.enter.prevent="applyBarLabel"
            />
            <Button
              type="button"
              label="Aplicar"
              size="small"
              class="study-path-editor__grid-apply"
              @click="applyBarLabel"
            />
            <div class="study-path-editor__grid-actions">
              <Button
                type="button"
                label="Excluir tópico"
                size="small"
                severity="danger"
                outlined
                @click="deleteSelectedNode"
              />
              <Button
                type="button"
                label="Apagar ligações"
                size="small"
                severity="secondary"
                outlined
                @click="clearAllEdges"
              />
            </div>
          </div>
        </div>
      </Transition>

      <p class="study-path-editor__hint">
        <strong>Um clique</strong> num tópico abre a edição. <strong>Duplo clique</strong> foca o
        texto. <strong>Botão direito</strong> abre o menu geral. Clique
        <strong>dentro do mapa</strong> antes de usar a roda para zoom; com o rato fora, a página
        rola normalmente.
      </p>

      <div
        class="study-path-editor__viewport"
        role="application"
        aria-label="Editor de mapa de estudos"
      >
        <div ref="mapViewportRef" class="study-path-editor__viewport-inner" tabindex="-1">
          <VueFlow
            v-model:nodes="nodes"
            v-model:edges="edges"
            class="study-path-editor__flow"
            :fit-view-on-init="false"
            :default-viewport="{ zoom: 0.48, x: 0, y: 0 }"
            :min-zoom="0.22"
            :max-zoom="1.75"
            :nodes-draggable="true"
            :nodes-connectable="true"
            :elements-selectable="false"
            :select-nodes-on-drag="false"
            :zoom-on-scroll="mapWheelFocused"
            @connect="onConnect"
            @node-click="onNodeClick"
            @node-double-click="onNodeDoubleClick"
            @pane-click="onPaneLeftClick"
            @pane-context-menu="onPaneContextMenu"
            @node-context-menu="onNodeContextMenu"
            @edge-context-menu="onEdgeContextMenu"
          />
        </div>

        <div
          v-show="ctxMenu"
          class="study-path-editor__ctx-backdrop"
          aria-hidden="true"
          @pointerdown.prevent="closeCtxMenu"
        />
        <div
          v-if="ctxMenu"
          class="study-path-editor__ctx-menu"
          role="menu"
          :style="ctxMenuStyleComp"
          @pointerdown.stop
        >
          <button
            type="button"
            class="study-path-editor__ctx-item"
            role="menuitem"
            @click="addNodeAt(ctxMenu.flow)"
          >
            Adicionar tópico aqui
          </button>
          <button type="button" class="study-path-editor__ctx-item" role="menuitem" @click="fitMap">
            Encaixar vista
          </button>
          <button
            type="button"
            class="study-path-editor__ctx-item"
            role="menuitem"
            @click="layoutHorizontal"
          >
            Layout em linha
          </button>
          <button
            type="button"
            class="study-path-editor__ctx-item"
            role="menuitem"
            @click="layoutVertical"
          >
            Layout em coluna
          </button>
          <button
            type="button"
            class="study-path-editor__ctx-item"
            role="menuitem"
            @click="layoutGrid"
          >
            Layout em grelha
          </button>
          <button
            type="button"
            class="study-path-editor__ctx-item"
            role="menuitem"
            @click="clearAllEdges"
          >
            Apagar ligações
          </button>
          <button
            type="button"
            class="study-path-editor__ctx-item"
            role="menuitem"
            @click="openImport"
          >
            Importar JSON…
          </button>
          <button
            type="button"
            class="study-path-editor__ctx-item"
            role="menuitem"
            @click="exportJson"
          >
            Exportar JSON
          </button>
        </div>
      </div>
    </div>

    <Dialog
      v-model:visible="importOpen"
      header="Importar mapa (JSON)"
      modal
      :style="{ width: 'min(92vw, 36rem)' }"
      @hide="importOpen = false"
    >
      <Textarea
        v-model="importText"
        class="study-path-editor__import-text"
        rows="12"
        placeholder='Cole o conteúdo do arquivo exportado, ex.: { "nodes": [...], "edges": [...] }'
        auto-resize
      />
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="importOpen = false" />
        <Button label="Aplicar" @click="applyImport" />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.study-path-editor {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
  min-height: min(76vh, 700px);
}
.study-path-editor__shell {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}
.study-path-editor__toolbar-row {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: var(--spacing-xs);
  padding: var(--spacing-md) var(--spacing-lg);
  overflow-x: auto;
  overscroll-behavior-x: contain;
  scrollbar-width: thin;
  border-bottom: 1px solid var(--color-border);
  background: color-mix(in srgb, var(--color-bg-card) 94%, var(--color-bg-soft));
  flex-shrink: 0;
}
.study-path-editor__toolbar-row::-webkit-scrollbar {
  height: 6px;
}
.study-path-editor__toolbar-row::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-border) 70%, transparent);
}
.study-path-editor__tb-btn {
  flex: 0 0 auto;
}
.study-path-editor__tb-div {
  width: 1px;
  height: 1.25rem;
  background: var(--color-border);
  flex-shrink: 0;
  margin: 0 var(--spacing-2xs, 0.125rem);
  opacity: 0.9;
}
.study-path-editor__toolbar-row :deep(.p-button) {
  white-space: nowrap;
  font-size: calc(var(--text-xs) * 0.9);
  padding-block: 0.2rem;
  padding-inline: 0.45rem;
  min-width: 0;
}
.study-path-editor__toolbar-row :deep(.p-button .p-button-label) {
  font-weight: 600;
}
.study-path-editor__toolbar-row :deep(.p-button:focus-visible) {
  box-shadow: var(--shadow-focus);
}
.study-path-editor__form {
  padding: var(--spacing-md) var(--spacing-lg);
  border-bottom: 1px solid var(--color-border);
  background: color-mix(in srgb, var(--color-bg-card) 98%, transparent);
}
.study-path-editor__grid--topic {
  display: grid;
  grid-template-columns: minmax(4.25rem, auto) minmax(0, 1fr) auto;
  gap: var(--spacing-sm) var(--spacing-md);
  align-items: center;
}
.study-path-editor__grid-label {
  font-size: var(--text-xs);
  font-weight: 700;
  color: var(--color-text-muted);
  margin: 0;
  justify-self: start;
}
.study-path-editor__grid-input {
  width: 100%;
  min-width: 0;
}
.study-path-editor__grid-apply {
  justify-self: end;
}
.study-path-editor__grid-actions {
  grid-column: 1 / -1;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--spacing-sm);
  padding-top: var(--spacing-xs);
}
.study-path-editor__form :deep(.p-button) {
  font-size: var(--text-xs);
  padding-block: 0.2rem;
  padding-inline: 0.5rem;
}
.study-path-editor__form :deep(.p-button .p-button-label) {
  font-weight: 600;
}
.study-path-editor__hint {
  margin: 0;
  padding: var(--spacing-sm) var(--spacing-lg) var(--spacing-md);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
}
.study-path-editor__hint strong {
  font-weight: 700;
  color: var(--color-text);
}
.study-path-editor__viewport {
  position: relative;
  flex: 1 1 auto;
  min-height: 440px;
  min-width: 0;
  padding: var(--spacing-md) var(--spacing-lg) var(--spacing-lg);
  background: color-mix(in srgb, var(--color-bg-soft) 88%, var(--color-bg-card));
}
.study-path-editor__viewport-inner {
  height: 100%;
  min-height: 400px;
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--color-bg-soft);
  border: 1px solid color-mix(in srgb, var(--color-border) 90%, transparent);
  box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--color-text) 4%, transparent);
  outline: none;
}
.study-path-editor__viewport-inner:focus-visible {
  box-shadow:
    inset 0 0 0 1px color-mix(in srgb, var(--color-text) 4%, transparent),
    var(--shadow-focus);
}
.study-path-editor__flow {
  width: 100%;
  height: 100%;
  min-height: 400px;
}
.study-path-editor__flow :deep(.vue-flow__node.study-topic .vue-flow__node-default) {
  box-shadow: var(--shadow-sm);
  transition: border-radius 0.15s ease;
}
.study-path-editor__flow :deep(.vue-flow__node.study-topic--rect .vue-flow__node-default) {
  border-radius: 6px !important;
}
.study-path-editor__flow :deep(.vue-flow__node.study-topic--round .vue-flow__node-default) {
  border-radius: 22px !important;
}
.study-path-editor__flow :deep(.vue-flow__node.study-topic--circle .vue-flow__node-default) {
  border-radius: 999px !important;
  width: 96px !important;
  height: 96px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  text-align: center;
}
.study-path-editor__ctx-backdrop {
  position: absolute;
  inset: 0;
  z-index: 8;
  background: transparent;
}
.study-path-editor__ctx-menu {
  position: fixed;
  z-index: 40;
  min-width: 12.5rem;
  padding: var(--spacing-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
  box-shadow: var(--shadow-md);
  transform: translate(-4px, -4px);
}
.study-path-editor__ctx-item {
  display: block;
  width: 100%;
  text-align: left;
  padding: var(--spacing-sm) var(--spacing-md);
  margin: 0;
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-text);
  cursor: pointer;
}
.study-path-editor__ctx-item:hover {
  background: var(--color-bg-soft);
}
.study-path-editor__import-text {
  width: 100%;
  font-family: ui-monospace, monospace;
  font-size: var(--text-xs);
}
.study-path-fade-enter-active,
.study-path-fade-leave-active {
  transition: opacity 0.15s ease;
}
.study-path-fade-enter-from,
.study-path-fade-leave-to {
  opacity: 0;
}
</style>
