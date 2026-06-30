import { mount } from '@vue/test-utils'
import ThemeToggle from '../ThemeToggle.vue'

vi.mock('@/stores/ui.store', () => ({
  useUiStore: vi.fn(() => ({
    theme: 'light',
    isDarkMode: false,
    toggleTheme: vi.fn(),
  })),
}))

describe('ThemeToggle', () => {
  it('renders toggle button', () => {
    const wrapper = mount(ThemeToggle)
    expect(wrapper.find('button').exists()).toBe(true)
  })

  it('calls toggleTheme on click', async () => {
    const toggleTheme = vi.fn()
    const { useUiStore } = await import('@/stores/ui.store')
    vi.mocked(useUiStore).mockReturnValue({
      theme: 'light',
      isDarkMode: false,
      toggleTheme,
    } as never)

    const wrapper = mount(ThemeToggle)
    await wrapper.find('button').trigger('click')

    expect(toggleTheme).toHaveBeenCalledOnce()
  })

  it('has accessible aria-label', () => {
    const wrapper = mount(ThemeToggle)
    const button = wrapper.find('button')
    expect(button.attributes('aria-label')).toBeDefined()
  })
})
