export type ContentBlock =
  | HeadingBlock
  | ParagraphBlock
  | FormulaBlock
  | CalloutBlock
  | ImageBlock
  | ListBlock
  | TableBlock
  | CodeBlock
  | VideoBlock
  | QuizPreviewBlock
  | DividerBlock
  | SpaceBlock

export interface HeadingBlock {
  type: 'heading'
  level: 1 | 2 | 3 | 4
  text: string
}

export interface ParagraphBlock {
  type: 'paragraph'
  text: string
  alignment?: 'left' | 'center' | 'right'
}

export interface FormulaBlock {
  type: 'formula'
  latex: string
  display?: boolean
  label?: string
}

export interface CalloutBlock {
  type: 'callout'
  variant: 'info' | 'warning' | 'tip' | 'important'
  title?: string
  text: string
}

export interface ImageBlock {
  type: 'image'
  src: string
  alt: string
  caption?: string
  width?: number
}

export interface ListBlock {
  type: 'list'
  style: 'bullet' | 'ordered' | 'checklist'
  items: string[]
}

export interface TableBlock {
  type: 'table'
  headers: string[]
  rows: (string | number)[][]
  striped?: boolean
}

export interface CodeBlock {
  type: 'code'
  language: string
  code: string
  filename?: string
}

export interface VideoBlock {
  type: 'video'
  src: string
  provider: 'youtube' | 'vimeo' | 'local'
  caption?: string
}

export interface QuizPreviewBlock {
  type: 'quiz_preview'
  question: string
  options: string[]
  correctIndex: number
}

export interface DividerBlock {
  type: 'divider'
}

export interface SpaceBlock {
  type: 'space'
  size: 'sm' | 'md' | 'lg'
}
