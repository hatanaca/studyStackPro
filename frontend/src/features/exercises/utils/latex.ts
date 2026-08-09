export interface LatexSegment {
  type: 'text' | 'latex'
  content: string
}

/**
 * Divide um texto misto em segmentos de texto puro e LaTeX delimitado por $...$.
 */
export function splitLatex(input: string): LatexSegment[] {
  const segments: LatexSegment[] = []
  const regex = /\$([^$]+)\$/g
  let lastIndex = 0
  let match: RegExpExecArray | null

  while ((match = regex.exec(input)) !== null) {
    if (match.index > lastIndex) {
      segments.push({ type: 'text', content: input.slice(lastIndex, match.index) })
    }
    segments.push({ type: 'latex', content: match[1] })
    lastIndex = match.index + match[0].length
  }

  if (lastIndex < input.length) {
    segments.push({ type: 'text', content: input.slice(lastIndex) })
  }

  return segments
}
