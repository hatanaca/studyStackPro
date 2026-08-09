import type { ContentBlock } from './content-blocks'

export interface StudySubTopicDetail {
  id: string
  topic_id: string
  name: string
  slug: string
  description: string | null
  content: { blocks: ContentBlock[] } | null
  faqs: FaqItem[] | null
  learning_objectives: string[] | null
  simulation_config: SimulationConfig | null
  is_favorited: boolean
}

export interface FaqItem {
  question: string
  answer: string
}

export type SimulationType = 'function_plot' | 'physics_sim' | 'biology_svg' | 'geometry'

export interface SimulationSlider {
  name: string
  min: number
  max: number
  default: number
  step?: number
  label: string
}

export interface SimulationConfig {
  type: SimulationType
  functions?: string[]
  xDomain?: [number, number]
  yDomain?: [number, number]
  sliders?: SimulationSlider[]
  simulation?: string
  initialVelocity?: number
  gravity?: number
  angle?: number
  length?: number
  amplitude?: number
  hotspots?: BiologyHotspot[]
  shape?: string
  interactive?: boolean
  measurements?: string[]
  [key: string]: unknown
}

export interface BiologyHotspot {
  id: string
  x: number
  y: number
  label: string
  description: string
}

export interface StudyFavorite {
  id: string
  sub_topic_id: string
  sub_topic_name: string | null
  created_at: string
}

export interface StudyNote {
  id: string
  sub_topic_id: string
  content: string
  updated_at: string | null
}

export interface ReadingProgress {
  sub_topic_id: string
  progress: number
  last_read_at: string | null
}
