export interface StudySubject {
  id: string
  name: string
  slug: string
  icon: string
  color: string
  sort_order: number
  progress: ProgressData
}

export interface StudyTopic {
  id: string
  subject_id: string
  name: string
  slug: string
  difficulty: DifficultyLevel
  sort_order: number
  sub_topics_count: number
  progress: ProgressData
}

export interface StudySubTopic {
  id: string
  topic_id: string
  name: string
  slug: string
  sort_order: number
  attempted: number
  correct: number
  mastered: boolean
}

export interface StudyQuestion {
  variant_id: string
  question_id: string
  kind: QuestionKind
  prompt: string
  choices: string[] | null
  has_graph: boolean
  graph_config: GraphConfig | null
  visual_type: VisualType
  visual_config: VisualConfig | null
  difficulty: number
  hint: string | null
}

export interface StudyAttemptResult {
  attempt_id: string
  is_correct: boolean
  answer: string
  expected: string
  solution_latex: string | null
  explanation: string | null
  time_spent_seconds: number | null
}

export interface ProgressData {
  attempted: number
  mastered: number
  total: number
  percentage: number
}

export interface OverallProgress {
  subjects: Array<{
    id: string
    name: string
    slug: string
    icon: string
    color: string
    progress: ProgressData
  }>
  overall: ProgressData
}

export type DifficultyLevel = 'fundamental' | 'intermediário' | 'avançado' | 'eliminatório'

export type QuestionKind = 'numeric' | 'symbolic' | 'multiple_choice' | 'true_false'

export type VisualType = 'none' | 'function_plot' | 'geometric' | 'diagram' | 'table'

export interface GraphConfig {
  type: 'function_plot' | 'venn_diagram' | 'geometric' | 'table' | 'timeline'
  fn?: string
  domain?: [number, number]
  range?: [number, number]
  shape?: string
  params?: Record<string, number>
  headers?: string[]
  rows?: (string | number)[][]
}

export interface VisualConfig {
  type?: string
  [key: string]: unknown
}

export const DIFFICULTY_LABELS: Record<DifficultyLevel, string> = {
  fundamental: 'Fundamental',
  intermediário: 'Intermediário',
  avançado: 'Avançado',
  eliminatório: 'Eliminatório',
}

export const DIFFICULTY_COLORS: Record<DifficultyLevel, string> = {
  fundamental: '#10B981',
  intermediário: '#3B82F6',
  avançado: '#F59E0B',
  eliminatório: '#EF4444',
}
