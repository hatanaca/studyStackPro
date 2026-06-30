import { mount } from '@vue/test-utils'
import EmptyState from '../EmptyState.vue'

describe('EmptyState', () => {
  it('renders title and description', () => {
    const wrapper = mount(EmptyState, {
      props: {
        title: 'No data found',
        description: 'Start by creating something',
      },
    })

    expect(wrapper.text()).toContain('No data found')
    expect(wrapper.text()).toContain('Start by creating something')
  })

  it('renders icon when provided', () => {
    const wrapper = mount(EmptyState, {
      props: {
        title: 'Empty',
        icon: 'pi pi-inbox',
      },
    })

    expect(wrapper.find('.pi-inbox, [class*="icon"]').exists()).toBe(true)
  })

  it('renders action slot', () => {
    const wrapper = mount(EmptyState, {
      props: { title: 'Empty' },
      slots: {
        action: '<button>Create</button>',
      },
    })

    expect(wrapper.text()).toContain('Create')
  })
})
