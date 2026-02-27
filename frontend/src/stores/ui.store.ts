import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useUiStore = defineStore('ui', () => {
  const theme = ref<'light' | 'dark'>('light')
  const sidebarCollapsed = ref(false)
  const modalStack = ref<string[]>([])

  const isDarkMode = computed(() => theme.value === 'dark')

  function toggleTheme() {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
  }

  function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
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
    sidebarCollapsed,
    isDarkMode,
    hasOpenModals,
    toggleTheme,
    toggleSidebar,
    openModal,
    closeModal,
  }
})
