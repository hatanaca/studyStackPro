<script setup lang="ts">
/**
 * Renderiza LaTeX com KaTeX e sanitiza o HTML gerado com DOMPurify.
 * Segurança: trust=false desabilita macros perigosas (\href, \htmlClass...);
 * DOMPurify remove qualquer markup residual antes de injetar no DOM.
 * Obs.: o DOMPurify "desempacota" o elemento raiz (<span class="katex">) do
 * HTML do KaTeX — por isso a classe `katex` é aplicada no próprio container.
 */
import { computed } from 'vue'
import katex from 'katex'
import DOMPurify from 'dompurify'
import 'katex/dist/katex.min.css'

const props = withDefaults(
  defineProps<{
    latex: string
    display?: boolean
    strict?: boolean
  }>(),
  { display: false, strict: false }
)

const renderedHtml = computed(() => {
  try {
    const html = katex.renderToString(props.latex, {
      displayMode: props.display,
      throwOnError: false,
      strict: props.strict ? 'error' : 'warn',
      trust: false,
    })
    return DOMPurify.sanitize(html)
  } catch {
    return ''
  }
})
</script>

<template>
  <span
    :class="['formula-text', 'katex', { 'katex-display': display }]"
    v-html="renderedHtml"
  />
</template>

<style scoped>
.formula-text {
  display: inline-block;
  vertical-align: middle;
  color: inherit;
  overflow-x: auto;
  max-width: 100%;
}
.katex-display {
  display: block;
  text-align: center;
  margin: var(--spacing-sm) 0;
}
</style>
