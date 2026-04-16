import { useToast as usePrimeToast } from 'primevue/usetoast'

/** Duração padrão do toast em ms */
const DEFAULT_DURATION = 4000

/**
 * Composables de Toast. Adaptador sobre PrimeVue useToast.
 * success, error, info com duração configurável. Erros: 6s por padrão.
 */
export function useToast() {
  const toast = usePrimeToast()

  function add(message: string, type: 'success' | 'error' | 'info', duration = DEFAULT_DURATION) {
    toast.add({
      severity: type === 'error' ? 'error' : type === 'success' ? 'success' : 'info',
      detail: message,
      life: duration,
    })
  }

  function show(
    message: string,
    type: 'success' | 'error' | 'info' = 'info',
    duration = DEFAULT_DURATION
  ) {
    add(message, type, duration)
  }

  function success(message: string, duration?: number) {
    add(message, 'success', duration ?? DEFAULT_DURATION)
  }

  function error(message: string, duration?: number) {
    add(message, 'error', duration ?? 6000)
  }

  function info(message: string, duration?: number) {
    add(message, 'info', duration ?? DEFAULT_DURATION)
  }

  return {
    show,
    success,
    error,
    info,
    /** @deprecated Use PrimeVue Toast; mantido para compatibilidade. */
    toasts: [] as { id: number; message: string; type: string }[],
    /** @deprecated Use PrimeVue Toast; mantido para compatibilidade. */
    dismiss: () => {},
  }
}
