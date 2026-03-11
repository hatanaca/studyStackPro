import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const THEME_KEY = 'studytrack.theme'
const CUSTOM_THEME_KEY = 'studytrack.theme.custom'

/** Opções de tema customizado (mapeadas para variáveis CSS) */
export interface CustomThemeOptions {
  primary?: string
  bg?: string
  bgCard?: string
  text?: string
  textMuted?: string
  border?: string
  fontSans?: string
}

/** Carrega tema do localStorage (light/dark) */
function loadTheme(): 'light' | 'dark' {
  try {
    const saved = localStorage.getItem(THEME_KEY)
    if (saved === 'dark' || saved === 'light') return saved
  } catch {
    // ignore
  }
  return 'light'
}

/** Carrega tema customizado do localStorage */
function loadCustomTheme(): CustomThemeOptions {
  try {
    const raw = localStorage.getItem(CUSTOM_THEME_KEY)
    if (!raw) return {}
    const parsed = JSON.parse(raw) as CustomThemeOptions
    return typeof parsed === 'object' && parsed !== null ? parsed : {}
  } catch {
    return {}
  }
}

/** Store de UI: tema, sidebar, modais, tema customizado. Persiste em localStorage. */
export const useUiStore = defineStore('ui', () => {
  const theme = ref<'light' | 'dark'>(loadTheme())
  const customTheme = ref<CustomThemeOptions>(loadCustomTheme())
  const sidebarCollapsed = ref(false)
  const mobileSidebarOpen = ref(false)
  const modalStack = ref<string[]>([])

  const isDarkMode = computed(() => theme.value === 'dark')

  function toggleTheme() {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
    try {
      localStorage.setItem(THEME_KEY, theme.value)
    } catch {
      // ignore
    }
  }

  const customThemeVarMap: Record<keyof CustomThemeOptions, string> = {
    primary: '--color-primary',
    bg: '--color-bg',
    bgCard: '--color-bg-card',
    text: '--color-text',
    textMuted: '--color-text-muted',
    border: '--color-border',
    fontSans: '--font-sans',
  }

  function applyCustomTheme() {
    const root = document.documentElement
    const opts = customTheme.value
    ;(Object.keys(customThemeVarMap) as (keyof CustomThemeOptions)[]).forEach((key) => {
      const value = opts[key]
      const varName = customThemeVarMap[key]
      if (value && varName) {
        root.style.setProperty(varName, key === 'fontSans' ? value : value)
      } else {
        root.style.removeProperty(varName)
      }
    })
  }

  function setCustomTheme(opts: Partial<CustomThemeOptions>) {
    customTheme.value = { ...customTheme.value, ...opts }
    try {
      localStorage.setItem(CUSTOM_THEME_KEY, JSON.stringify(customTheme.value))
    } catch {
      // ignore
    }
    applyCustomTheme()
  }

  function resetCustomTheme() {
    customTheme.value = {}
    try {
      localStorage.removeItem(CUSTOM_THEME_KEY)
    } catch {
      // ignore
    }
    applyCustomTheme()
  }

  function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  function openMobileSidebar() {
    mobileSidebarOpen.value = true
  }

  function closeMobileSidebar() {
    mobileSidebarOpen.value = false
  }

  function toggleMobileSidebar() {
    mobileSidebarOpen.value = !mobileSidebarOpen.value
  }

  function openModal(id: string) {
    if (!modalStack.value.includes(id)) {
      modalStack.value = [...modalStack.value, id]
    }
  }

  function closeModal(id: string) {
    modalStack.value = modalStack.value.filter((m) => m !== id)
  }

  const hasOpenModals = computed(() => modalStack.value.length > 0)

  return {
    theme,
    customTheme,
    sidebarCollapsed,
    mobileSidebarOpen,
    isDarkMode,
    hasOpenModals,
    toggleTheme,
    setCustomTheme,
    resetCustomTheme,
    applyCustomTheme,
    toggleSidebar,
    openMobileSidebar,
    closeMobileSidebar,
    toggleMobileSidebar,
    openModal,
    closeModal,
  }
})
