import { mount } from '@vue/test-utils'
import ErrorCard from '../ErrorCard.vue'

describe('ErrorCard', () => {
  it('renders error message', () => {
    const wrapper = mount(ErrorCard, {
      props: {
        message: 'Something went wrong',
      },
    })

    expect(wrapper.text()).toContain('Something went wrong')
  })

  it('renders retry button when onRetry is provided', () => {
    const wrapper = mount(ErrorCard, {
      props: {
        message: 'Error occurred',
        onRetry: vi.fn(),
      },
    })

    const retryBtn = wrapper.find('button')
    expect(retryBtn.exists()).toBe(true)
  })

  it('emits retry when retry button is clicked', async () => {
    const onRetry = vi.fn()
    const wrapper = mount(ErrorCard, {
      props: {
        message: 'Error occurred',
        onRetry,
      },
    })

    const retryBtn = wrapper.find('button')
    await retryBtn.trigger('click')

    expect(onRetry).toHaveBeenCalledOnce()
  })
})
