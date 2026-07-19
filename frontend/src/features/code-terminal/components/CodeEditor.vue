<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount, shallowRef } from 'vue'
import {
  EditorView,
  keymap,
  lineNumbers,
  highlightActiveLineGutter,
  highlightSpecialChars,
  KeyBinding,
} from '@codemirror/view'
import { EditorState } from '@codemirror/state'
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands'
import {
  bracketMatching,
  indentOnInput,
  syntaxHighlighting,
  defaultHighlightStyle,
} from '@codemirror/language'
import { javascript } from '@codemirror/lang-javascript'
import { php } from '@codemirror/lang-php'
import { sql } from '@codemirror/lang-sql'
import { css } from '@codemirror/lang-css'
import { html } from '@codemirror/lang-html'
import { oneDark } from '@codemirror/theme-one-dark'
import type { ProgrammingLanguage } from '../types/code-terminal.types'

const props = defineProps<{
  modelValue: string
  language: ProgrammingLanguage
  darkMode?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
  run: []
}>()

const editorContainer = ref<HTMLDivElement>()
const editorView = shallowRef<EditorView>()

function getLanguageExtension(lang: ProgrammingLanguage) {
  switch (lang) {
    case 'javascript':
      return javascript()
    case 'php':
    case 'laravel':
      return php()
    case 'sql':
      return sql()
    case 'css':
      return css()
    case 'html':
      return html()
    case 'lua':
    case 'bash':
      return javascript() // fallback for syntax highlighting
    default:
      return javascript()
  }
}

function createEditorState(doc: string, lang: ProgrammingLanguage, dark: boolean) {
  const extensions = [
    lineNumbers(),
    highlightActiveLineGutter(),
    highlightSpecialChars(),
    history(),
    bracketMatching(),
    indentOnInput(),
    syntaxHighlighting(defaultHighlightStyle),
    keymap.of([
      ...defaultKeymap,
      ...historyKeymap,
      {
        key: 'Mod-Enter',
        run: () => {
          emit('run')
          return true
        },
      } satisfies KeyBinding,
    ]),
    getLanguageExtension(lang),
    EditorView.updateListener.of((update) => {
      if (update.docChanged) {
        emit('update:modelValue', update.state.doc.toString())
      }
    }),
    EditorView.theme({
      '&': { height: '100%', fontSize: '14px' },
      '.cm-scroller': { overflow: 'auto', fontFamily: 'var(--font-mono, "Fira Code", monospace)' },
      '.cm-content': { padding: '8px 0' },
      '.cm-gutters': {
        backgroundColor: dark ? '#1e1e1e' : '#f5f5f5',
        borderRight: `1px solid ${dark ? '#333' : '#e0e0e0'}`,
      },
    }),
  ]

  if (dark) {
    extensions.push(oneDark)
  }

  return EditorState.create({
    doc,
    extensions,
  })
}

onMounted(() => {
  if (!editorContainer.value) return

  editorView.value = new EditorView({
    state: createEditorState(props.modelValue, props.language, props.darkMode ?? false),
    parent: editorContainer.value,
  })
})

watch(
  () => props.language,
  (newLang) => {
    if (!editorView.value) return
    const doc = editorView.value.state.doc.toString()
    editorView.value.setState(createEditorState(doc, newLang, props.darkMode ?? false))
  }
)

watch(
  () => props.darkMode,
  (dark) => {
    if (!editorView.value) return
    const doc = editorView.value.state.doc.toString()
    editorView.value.setState(createEditorState(doc, props.language, dark ?? false))
  }
)

watch(
  () => props.modelValue,
  (newVal) => {
    if (!editorView.value) return
    const currentDoc = editorView.value.state.doc.toString()
    if (currentDoc !== newVal) {
      editorView.value.dispatch({
        changes: { from: 0, to: currentDoc.length, insert: newVal },
      })
    }
  }
)

onBeforeUnmount(() => {
  editorView.value?.destroy()
})
</script>

<template>
  <div ref="editorContainer" class="code-editor" />
</template>

<style scoped>
.code-editor {
  height: 100%;
  overflow: hidden;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.code-editor :deep(.cm-editor) {
  height: 100%;
}

.code-editor :deep(.cm-focused) {
  outline: none;
}
</style>
