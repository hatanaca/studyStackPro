import { describe, expect, it } from 'vitest'
import { splitLatex } from '@/features/exercises/utils/latex'

describe('splitLatex', () => {
  it('retorna segmento de texto quando não há LaTeX', () => {
    expect(splitLatex('apenas texto')).toEqual([{ type: 'text', content: 'apenas texto' }])
  })

  it('separa texto e LaTeX delimitado por $...$', () => {
    expect(splitLatex('Resolva $2x + 4 = 0$ agora')).toEqual([
      { type: 'text', content: 'Resolva ' },
      { type: 'latex', content: '2x + 4 = 0' },
      { type: 'text', content: ' agora' },
    ])
  })

  it('lida com múltiplos trechos LaTeX', () => {
    const segments = splitLatex('$a$ e $b$')
    expect(segments.filter((s) => s.type === 'latex')).toHaveLength(2)
  })

  it('retorna vazio para entrada vazia', () => {
    expect(splitLatex('')).toEqual([])
  })
})
