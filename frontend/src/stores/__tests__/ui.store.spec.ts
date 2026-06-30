import { setActivePinia, createPinia } from 'pinia'
import { useUiStore } from '../ui.store'

vi.stubGlobal('document', {
  documentElement: { style: { setProperty: vi.fn(), removeProperty: vi.fn() } },
})

describe('ui.store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('initializes with light theme', () => {
    const store = useUiStore()
    expect(store.theme).toBe('light')
    expect(store.isDarkMode).toBe(false)
  })

  it('toggleTheme switches between light and dark', () => {
    const store = useUiStore()
    store.toggleTheme()
    expect(store.theme).toBe('dark')
    expect(store.isDarkMode).toBe(true)

    store.toggleTheme()
    expect(store.theme).toBe('light')
    expect(store.isDarkMode).toBe(false)
  })

  it('initializes with sidebar collapsed (default true)', () => {
    const store = useUiStore()
    expect(store.sidebarCollapsed).toBe(true)
  })

  it('toggleSidebar flips collapsed state', () => {
    const store = useUiStore()
    store.toggleSidebar()
    expect(store.sidebarCollapsed).toBe(false)

    store.toggleSidebar()
    expect(store.sidebarCollapsed).toBe(true)
  })

  it('mobileSidebarOpen defaults to false', () => {
    const store = useUiStore()
    expect(store.mobileSidebarOpen).toBe(false)
  })

  it('openMobileSidebar and closeMobileSidebar toggle state', () => {
    const store = useUiStore()
    store.openMobileSidebar()
    expect(store.mobileSidebarOpen).toBe(true)

    store.closeMobileSidebar()
    expect(store.mobileSidebarOpen).toBe(false)
  })

  it('toggleMobileSidebar flips state', () => {
    const store = useUiStore()
    store.toggleMobileSidebar()
    expect(store.mobileSidebarOpen).toBe(true)

    store.toggleMobileSidebar()
    expect(store.mobileSidebarOpen).toBe(false)
  })

  it('openModal adds to stack, closeModal removes', () => {
    const store = useUiStore()
    expect(store.hasOpenModals).toBe(false)

    store.openModal('dialog-1')
    expect(store.hasOpenModals).toBe(true)

    store.closeModal('dialog-1')
    expect(store.hasOpenModals).toBe(false)
  })

  it('openModal does not add duplicates', () => {
    const store = useUiStore()
    store.openModal('dialog-1')
    store.openModal('dialog-1')

    expect(store.hasOpenModals).toBe(true)
  })

  it('multiple modals stack correctly', () => {
    const store = useUiStore()
    store.openModal('dialog-1')
    store.openModal('dialog-2')

    expect(store.hasOpenModals).toBe(true)

    store.closeModal('dialog-1')
    expect(store.hasOpenModals).toBe(true)

    store.closeModal('dialog-2')
    expect(store.hasOpenModals).toBe(false)
  })

  it('setCustomTheme merges options', () => {
    const store = useUiStore()
    store.setCustomTheme({ primary: '#ff0000' })

    expect(store.customTheme.primary).toBe('#ff0000')
  })

  it('resetCustomTheme clears custom theme', () => {
    const store = useUiStore()
    store.setCustomTheme({ primary: '#ff0000', bg: '#000' })
    store.resetCustomTheme()

    expect(store.customTheme).toEqual({})
  })

  it('persists theme to localStorage', () => {
    const store = useUiStore()
    store.toggleTheme()

    expect(localStorage.getItem('studytrack.theme')).toBe('dark')
  })

  it('persists sidebar state to localStorage', () => {
    const store = useUiStore()
    store.toggleSidebar()

    expect(localStorage.getItem('studytrack.sidebar.collapsed')).toBe('false')
  })
})
