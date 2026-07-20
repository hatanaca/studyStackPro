<script setup lang="ts">
import DOMPurify from 'dompurify'
defineProps<{ content: string }>()
function sanitize(raw: string): string {
  return DOMPurify.sanitize(raw, { USE_PROFILES: { svg: true } })
}
</script>
<template>
  <!-- eslint-disable-next-line vue/no-v-html -->
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
