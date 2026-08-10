import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import functionPlot from 'function-plot'
import GraphPlot from '@/components/math/GraphPlot.vue'

vi.mock('function-plot', () => ({ default: vi.fn() }))

describe('GraphPlot', () => {
  beforeEach(() => {
    vi.mocked(functionPlot).mockClear()
  })

  it('chama function-plot com a expressão e o container', () => {
    const wrapper = mount(GraphPlot, { props: { fns: 'x^2' } })
    const options = vi.mocked(functionPlot).mock.calls[0][0]
    expect(options.target).toBe(wrapper.find('.graph-plot').element)
    expect(options.data).toEqual([{ fn: 'x^2', color: expect.any(String) }])
    expect(options.grid).toBe(true)
  })

  it('suporta múltiplas funções separadas', () => {
    mount(GraphPlot, { props: { fns: ['x^2', 'sin(x)'] } })
    const options = vi.mocked(functionPlot).mock.calls[0][0]
    expect(options.data).toHaveLength(2)
  })

  it('emite erro quando a expressão é inválida', () => {
    vi.mocked(functionPlot).mockImplementationOnce(() => {
      throw new Error('bad')
    })
    const wrapper = mount(GraphPlot, { props: { fns: 'x%%%' } })
    expect(wrapper.emitted('error')).toBeTruthy()
  })
})
