import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import FormulaText from '@/components/math/FormulaText.vue'

describe('FormulaText', () => {
  it('renderiza LaTeX como HTML do KaTeX', () => {
    const wrapper = mount(FormulaText, { props: { latex: 'E = mc^2' } })
    expect(wrapper.find('.katex').exists()).toBe(true)
    expect(wrapper.find('.katex-mathml').exists()).toBe(true)
    expect(wrapper.find('.katex-html').exists()).toBe(true)
  })

  it('aplica katex-display quando display=true', () => {
    const wrapper = mount(FormulaText, { props: { latex: 'x^2', display: true } })
    expect(wrapper.classes()).toContain('katex-display')
  })

  it('não gera links a partir de macros perigosas (XSS via \\href)', () => {
    const wrapper = mount(FormulaText, {
      props: { latex: '\\href{javascript:alert(1)}{clique}' },
    })
    expect(wrapper.find('a').exists()).toBe(false)
    expect(wrapper.find('a[href^="javascript:"]').exists()).toBe(false)
  })

  it('sanitiza atributos de eventos maliciosos', () => {
    const wrapper = mount(FormulaText, { props: { latex: 'x' } })
    expect(wrapper.find('[onerror]').exists()).toBe(false)
  })

  it('não quebra com LaTeX inválido', () => {
    const wrapper = mount(FormulaText, { props: { latex: '\\notarealmacro{' } })
    expect(wrapper.text()).toBeDefined()
  })
})
