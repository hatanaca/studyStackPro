import type { Ref } from 'vue'
let fabricCanvas: Ref<any> | null = null
export function setFabricCanvas(c: Ref<any> | null) {
  fabricCanvas = c
  if (import.meta.env.DEV) {
    ;(window as any).__fabricCanvas = c?.value ?? null
  }
}
export function getFabricCanvas() {
  return fabricCanvas
}
