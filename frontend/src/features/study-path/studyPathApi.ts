/**
 * @module studyPathApi
 * @description API de persistência de study paths via backend Laravel.
 *
 * Fornece save/load que usam a API REST em vez de localStorage.
 * Mantém localStorage como fallback offline.
 */
import { studyPathsApi, type StudyPath } from '@/api/modules/study-paths.api'
import type { Node, Edge } from '@vue-flow/core'

const STORAGE_PREFIX = 'studytrack.study-flow.v1.'

export async function loadStudyPath(
  technologyId: string,
  defaultNodes: Node[],
  defaultEdges: Edge[]
): Promise<{ nodes: Node[]; edges: Edge[] }> {
  try {
    const path = await studyPathsApi.getByTechnology(technologyId)
    if (path?.nodes?.length) {
      return { nodes: path.nodes as Node[], edges: (path.edges as Edge[]) ?? [] }
    }
  } catch {
    // fallback to localStorage
  }

  // localStorage fallback
  try {
    const raw = localStorage.getItem(`${STORAGE_PREFIX}${technologyId}`)
    if (raw) {
      const parsed = JSON.parse(raw)
      if (parsed?.nodes?.length) {
        return { nodes: parsed.nodes, edges: parsed.edges ?? [] }
      }
    }
  } catch {
    // ignore
  }

  return { nodes: defaultNodes, edges: defaultEdges }
}

export async function saveStudyPath(
  technologyId: string,
  nodes: Node[],
  edges: Edge[],
  existingPathId?: string
): Promise<StudyPath | null> {
  const payload = { technology_id: technologyId, nodes, edges }

  try {
    if (existingPathId) {
      return await studyPathsApi.update(existingPathId, payload)
    }
    return await studyPathsApi.create(payload)
  } catch {
    // fallback to localStorage
    try {
      localStorage.setItem(`${STORAGE_PREFIX}${technologyId}`, JSON.stringify({ nodes, edges }))
    } catch {
      // ignore quota errors
    }
    return null
  }
}
