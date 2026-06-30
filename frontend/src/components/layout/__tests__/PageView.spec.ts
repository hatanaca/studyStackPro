import { mount } from '@vue/test-utils'
import PageView from '../PageView.vue'

describe('PageView', () => {
  it('renders title when provided', () => {
    const wrapper = mount(PageView, {
      props: { title: 'My Page' },
    })

    expect(wrapper.text()).toContain('My Page')
  })

  it('renders subtitle when provided', () => {
    const wrapper = mount(PageView, {
      props: { title: 'Page', subtitle: 'A subtitle' },
    })

    expect(wrapper.text()).toContain('A subtitle')
  })

  it('renders default slot content', () => {
    const wrapper = mount(PageView, {
      slots: { default: '<div>Page content here</div>' },
    })

    expect(wrapper.text()).toContain('Page content here')
  })

  it('renders actions slot', () => {
    const wrapper = mount(PageView, {
      props: { title: 'Page' },
      slots: { actions: '<button>Action</button>' },
    })

    expect(wrapper.text()).toContain('Action')
  })

  it('does not render header when no title', () => {
    const wrapper = mount(PageView)

    expect(wrapper.find('.page-header').exists()).toBe(false)
  })

  it('applies narrow class when narrow prop is true', () => {
    const wrapper = mount(PageView, {
      props: { narrow: true },
    })

    expect(wrapper.find('.page-view--narrow').exists()).toBe(true)
  })

  it('renders hint slot', () => {
    const wrapper = mount(PageView, {
      props: { title: 'Page' },
      slots: { hint: '<span>Hint text</span>' },
    })

    expect(wrapper.text()).toContain('Hint text')
  })
})
