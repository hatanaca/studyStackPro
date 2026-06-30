<script setup lang="ts">
/**
 * @module SafeSvg
 * @description Renderiza conteúdo SVG interno de forma segura.
 * Filtra apenas elementos SVG válidos para prevenir XSS via v-html.
 */

defineProps<{
  /** Conteúdo SVG interno (path, rect, circle, etc.) */
  content: string
}>()

const SVG_ALLOWED = /^<(path|rect|circle|line|polyline|polygon|ellipse|g|defs|clipPath|use|stop|animate|set)(\s|>|\/)/i

function sanitize(raw: string): string {
  if (!SVG_ALLOWED.test(raw.trim())) return ''
  return raw
}
</script>

<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    v-html="sanitize(content)"
  />
</template>
