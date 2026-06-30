/**
 * @module canvasInstance
 * @description Singleton que armazena a referência global da instância do canvas Fabric.js.
 *
 * Permite acesso à instância do canvas de qualquer componente ou composable
 * da aplicação, sem necessidade de prop drilling. A referência também é
 * exposta em `window.__fabricCanvas` para depuração.
 */
import type { Ref } from 'vue'

/** Referência reativa global do canvas Fabric.js */
let fabricCanvas: Ref<any> | null = null

/**
 * @description Armazena a referência global do canvas Fabric.js.
 *
 * Torna acessível a instância do canvas para qualquer parte da aplicação
 * através de `getFabricCanvas()`. Também expõe a referência em
 * `window.__fabricCanvas` para facilitar a depuração via console do navegador.
 *
 * @param c - Referência reativa Vue que contém a instância do canvas Fabric.js
 */
export function setFabricCanvas(c: Ref<any>) {
  fabricCanvas = c
  ;(window as any).__fabricCanvas = c.value
}

/**
 * @description Recupera a referência reativa global do canvas Fabric.js.
 *
 * @returns A referência reativa do canvas, ou `null` caso ainda não tenha sido inicializado
 */
export function getFabricCanvas() {
  return fabricCanvas
}
