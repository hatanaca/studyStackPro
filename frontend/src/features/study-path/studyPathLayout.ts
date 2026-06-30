import type { Node } from '@vue-flow/core'
import { normalizeStudyPathNode } from '@/features/study-path/studyPathNode'

export function layoutHorizontal(nodes: Node[]): Node[] {
  const sorted = [...nodes].sort((a, b) => String(a.id).localeCompare(String(b.id)))
  return sorted.map((n, i) =>
    normalizeStudyPathNode({ ...n, position: { x: 40 + i * 260, y: 160 } })
  )
}

export function layoutVertical(nodes: Node[]): Node[] {
  const sorted = [...nodes].sort((a, b) => String(a.id).localeCompare(String(b.id)))
  return sorted.map((n, i) =>
    normalizeStudyPathNode({ ...n, position: { x: 200, y: 40 + i * 140 } })
  )
}

export function layoutGrid(nodes: Node[]): Node[] {
  const sorted = [...nodes].sort((a, b) => String(a.id).localeCompare(String(b.id)))
  const n = sorted.length
  const cols = Math.max(1, Math.ceil(Math.sqrt(n)))
  return sorted.map((node, i) => {
    const row = Math.floor(i / cols)
    const col = i % cols
    return normalizeStudyPathNode({
      ...node,
      position: { x: 40 + col * 260, y: 40 + row * 160 },
    })
  })
}
