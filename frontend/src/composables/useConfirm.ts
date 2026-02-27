import { ref } from 'vue'

export function useConfirm() {
  const show = ref(false)
  const message = ref('')
  const resolveRef = ref<((value: boolean) => void) | null>(null)

  function confirm(msg: string): Promise<boolean> {
    message.value = msg
    show.value = true
    return new Promise<boolean>((resolve) => {
      resolveRef.value = resolve
    })
  }

  function accept() {
    resolveRef.value?.(true)
    show.value = false
    resolveRef.value = null
  }

  function cancel() {
    resolveRef.value?.(false)
    show.value = false
    resolveRef.value = null
  }

  return { show, message, confirm, accept, cancel }
}
