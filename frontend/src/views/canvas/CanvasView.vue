<script setup lang="ts">
/**
 * @module CanvasView
 * @description View wrapper do editor de canvas (quadro branco/lousa digital).
 * Renderiza diretamente o componente CanvasEditor, que fornece funcionalidades
 * de desenho, anotações e manipulação visual.
 *
 * @route /canvas
 * @requires CanvasEditor - Componente principal do editor de canvas
 * @description Esta view é um wrapper simples que delega toda a lógica
 * para o componente CanvasEditor. Não possui props ou estado próprio.
 */
import CanvasEditor from '@/features/canvas/components/CanvasEditor.vue'
</script>

<template>
  <CanvasEditor />
</template>
