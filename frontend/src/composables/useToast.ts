import { ref } from 'vue'

export type ToastType = 'success' | 'error' | 'info'

export interface Toast {
  id: number
  message: string
  type: ToastType
  duration?: number
}

const toasts = ref<Toast[]>([])
let idCounter = 0
let hideTimer: ReturnType<typeof setTimeout> | null = null

export function useToast() {
  function add(message: string, type: ToastType = 'success', duration = 4000) {
    const id = ++idCounter
    toasts.value = [...toasts.value, { id, message, type, duration }]

    if (hideTimer) clearTimeout(hideTimer)
    hideTimer = setTimeout(() => {
      remove(id)
      hideTimer = null
    }, duration)
  }

  function remove(id: number) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  function success(message: string, duration?: number) {
    add(message, 'success', duration)
  }

  function error(message: string, duration?: number) {
    add(message, 'error', duration ?? 6000)
  }

  function info(message: string, duration?: number) {
    add(message, 'info', duration)
  }

  return { toasts, success, error, info, remove }
}
