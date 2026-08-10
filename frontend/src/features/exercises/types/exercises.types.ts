export type ExerciseKind = 'numeric' | 'symbolic'

export interface ParameterSpec {
  type: 'int' | 'float' | 'choice'
  min?: number
  max?: number
  step?: number
  choices?: number[]
}

export interface ExerciseTemplate {
  id: string
  title: string
  kind: ExerciseKind
  prompt: string
  parameters_spec: Record<string, ParameterSpec>
  answer_expression: string
  solution_latex: string | null
  variables: string[] | null
  difficulty: number
  is_global: boolean
  created_at: string
  updated_at: string
}

export interface ExerciseVariant {
  id: string
  template_id: string
  parameters: Record<string, number>
  prompt_latex: string
  answer_expr: string
  solution_latex: string | null
  created_at: string
}

export interface ExerciseAttempt {
  id: string
  variant_id: string
  template_title: string | null
  answer: string
  is_correct: boolean
  feedback_latex: string | null
  expected_latex: string | null
  submitted_at: string
  created_at: string
}
