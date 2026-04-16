import { onMounted, onUnmounted, watch, type Ref } from 'vue'

const FOCUSABLE_SELECTOR =
  'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'

/**
 * Mantém o foco dentro do elemento (para modais e drawers).
 * Ao ativar, foca o primeiro elemento focável; ao desativar, restaura o foco anterior.
 */
export function useFocusTrap(container: Ref<HTMLElement | null>, active: Ref<boolean>) {
  let previousActiveElement: HTMLElement | null = null

  function getFocusables(): HTMLElement[] {
    if (!container.value) return []
    return Array.from(container.value.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR))
  }

  function trapFocus(e: KeyboardEvent) {
    if (!active.value || !container.value) return
    if (e.key !== 'Tab') return

    const focusables = getFocusables()
    if (focusables.length === 0) return

    const first = focusables[0]
    const last = focusables[focusables.length - 1]

    if (e.shiftKey) {
      if (document.activeElement === first) {
        e.preventDefault()
        last.focus()
      }
    } else {
      if (document.activeElement === last) {
        e.preventDefault()
        first.focus()
      }
    }
  }

  function activate() {
    previousActiveElement = document.activeElement as HTMLElement | null
    const focusables = getFocusables()
    if (focusables.length > 0) {
      focusables[0].focus()
    }
    document.addEventListener('keydown', trapFocus)
  }

  function deactivate() {
    document.removeEventListener('keydown', trapFocus)
    if (previousActiveElement?.focus) {
      previousActiveElement.focus()
    }
  }

  watch(
    active,
    (isActive) => {
      if (isActive) activate()
      else deactivate()
    },
    { flush: 'post' }
  )

  onMounted(() => {
    if (active.value) activate()
  })

  onUnmounted(() => {
    deactivate()
  })
}
