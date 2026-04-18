import type { Node } from '@vue-flow/core'

export type TopicShape = 'rect' | 'round' | 'circle'

export interface TopicNodeData {
  label: string
  shape?: TopicShape
}

/** Estilos no wrapper do nó (complementam as classes `.study-topic--*`). */
export function shapeStyle(shape: TopicShape): Record<string, string> {
  switch (shape) {
    case 'circle':
      return { minWidth: '96px', minHeight: '96px' }
    case 'round':
      return { minWidth: '140px', minHeight: '56px' }
    default:
      return { minWidth: '140px', minHeight: '44px' }
  }
}

export function topicShapeClass(shape: TopicShape): string {
  return `study-topic study-topic--${shape}`
}

/** Garante `data.label`, `data.shape`, `class` e `style` coerentes (forma sobrepõe estilo anterior). */
export function normalizeStudyPathNode(node: Node): Node {
  const raw = (node.data ?? {}) as Partial<TopicNodeData>
  const shape: TopicShape = raw.shape ?? 'rect'
  const label = String(raw.label ?? 'Tópico')
  const prevStyle =
    typeof node.style === 'object' && node.style !== null && !Array.isArray(node.style)
      ? (node.style as Record<string, string>)
      : {}
  return {
    ...node,
    data: { ...raw, label, shape },
    class: topicShapeClass(shape),
    // forma deve ganhar a `border-radius` etc.; entradas antigas em `prevStyle` não podem anular a forma
    style: { ...prevStyle, ...shapeStyle(shape) },
  }
}
