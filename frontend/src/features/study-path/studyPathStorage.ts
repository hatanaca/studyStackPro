import type { Node, Edge } from '@vue-flow/core'
import { normalizeStudyPathNode } from '@/features/study-path/studyPathNode'

export interface StoredFlow {
  nodes: Node[]
  edges: Edge[]
}

export function isRecord(v: unknown): v is Record<string, unknown> {
  return v !== null && typeof v === 'object'
}

export function parseStored(json: unknown): StoredFlow | null {
  if (!isRecord(json)) return null
  const n = json.nodes
  const e = json.edges
  if (!Array.isArray(n) || !Array.isArray(e)) return null
  return { nodes: n as Node[], edges: e as Edge[] }
}

export function loadFromStorage(
  technologyId: string,
  defaultNodes: Node[],
  defaultEdges: Edge[]
): StoredFlow {
  const key = `studytrack.study-flow.v1.${technologyId}`
  try {
    const raw = localStorage.getItem(key)
    if (!raw) return { nodes: defaultNodes, edges: defaultEdges }
    const parsed = parseStored(JSON.parse(raw))
    if (parsed?.nodes?.length) {
      return {
        nodes: parsed.nodes.map((n) => normalizeStudyPathNode(n)),
        edges: parsed.edges ?? [],
      }
    }
  } catch {
    // fall through to defaults
  }
  return { nodes: defaultNodes, edges: defaultEdges }
}

export function saveToStorage(technologyId: string, nodes: Node[], edges: Edge[]) {
  if (!technologyId) return
  try {
    const payload: StoredFlow = {
      nodes: nodes.map((n) => ({ ...n })),
      edges: edges.map((e) => ({ ...e })),
    }
    localStorage.setItem(`studytrack.study-flow.v1.${technologyId}`, JSON.stringify(payload))
  } catch {
    // ignore quota / private mode
  }
}

export function exportAsJson(technologyId: string, nodes: Node[], edges: Edge[]) {
  const text = JSON.stringify({ nodes, edges }, null, 2)
  const blob = new Blob([text], { type: 'application/json;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `studytrack-mapa-${technologyId}.json`
  a.click()
  URL.revokeObjectURL(url)
}
