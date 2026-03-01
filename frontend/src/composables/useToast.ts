import { ref } from 'vue'

export interface ToastMessage {
  id: number
  message: string
  type: 'success' | 'error' | 'info'
  duration?: number
}

const toasts = ref<ToastMessage[]>([])
let nextId = 0
let dismissTimer: ReturnType<typeof setTimeout> | null = null
const DEFAULT_DURATION = 4000

export function useToast() {
  function show(message: string, type: ToastMessage['type'] = 'info', duration = DEFAULT_DURATION) {
    const id = ++nextId
    toasts.value = [...toasts.value, { id, message, type, duration }]

    if (dismissTimer) clearTimeout(dismissTimer)
    dismissTimer = setTimeout(() => {
      dismiss(id)
      dismissTimer = null
    }, duration)

    return id
  }

  function dismiss(id: number) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  function success(message: string, duration?: number) {
    return show(message, 'success', duration)
  }

  function error(message: string, duration?: number) {
    return show(message, 'error', duration ?? 6000)
  }

  function info(message: string, duration?: number) {
    return show(message, 'info', duration)
  }

  return {
    toasts,
    show,
    dismiss,
    success,
    error,
    info,
  }
}
