import { ref } from 'vue'

export interface ConfirmOptions {
  title?: string
  message: string
  confirmLabel?: string
  cancelLabel?: string
  variant?: 'danger' | 'primary'
}

const isOpen = ref(false)
const options = ref<ConfirmOptions>({ message: '' })
let resolveFn: ((value: boolean) => void) | null = null

export function useConfirm() {
  function open(opts: ConfirmOptions | string): Promise<boolean> {
    options.value = typeof opts === 'string' ? { message: opts } : opts
    isOpen.value = true
    return new Promise<boolean>((resolve) => {
      resolveFn = resolve
    })
  }

  function confirm() {
    if (resolveFn) resolveFn(true)
    resolveFn = null
    isOpen.value = false
  }

  function cancel() {
    if (resolveFn) resolveFn(false)
    resolveFn = null
    isOpen.value = false
  }

  return {
    isOpen,
    options,
    open,
    confirm,
    cancel,
  }
}
