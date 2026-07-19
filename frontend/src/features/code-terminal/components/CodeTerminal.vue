<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useCodeTerminalStore } from '../store/code-terminal.store'
import { useCodeExecution } from '../composables/useCodeExecution'
import { useLanguageDetection } from '../composables/useLanguageDetection'
import { renderInSandbox } from '../utils/sandbox-html'
import CodeEditor from './CodeEditor.vue'
import TerminalOutput from './TerminalOutput.vue'
import TerminalToolbar from './TerminalToolbar.vue'
import type { ProgrammingLanguage } from '../types/code-terminal.types'

const store = useCodeTerminalStore()
const { execute, clearHistory, isExecuting } = useCodeExecution()
const { detect: _detect } = useLanguageDetection()

const previewContainer = ref<HTMLDivElement>()
const showPreview = ref(false)
const currentPreviewLanguage = ref<'html' | 'css' | null>(null)

const isPreviewLanguage = computed(() =>
  store.language === 'html' || store.language === 'css'
)

async function handleRun() {
  if (!store.code.trim()) return

  // Auto-detect language if desired
  // const detected = detect(store.code)
  // if (detected !== store.language) store.setLanguage(detected)

  // HTML/CSS: render in sandbox iframe
  if (isPreviewLanguage.value && previewContainer.value) {
    showPreview.value = true
    currentPreviewLanguage.value = store.language as 'html' | 'css'
    const result = renderInSandbox(previewContainer.value, store.code, store.language as 'html' | 'css')

    store.addEntry({
      id: `entry-${Date.now()}`,
      code: store.code,
      language: store.language,
      result: {
        success: result.success,
        output: `[${store.language.toUpperCase()}] Renderizado no preview`,
        error: result.error,
        executionTime: 0,
        language: store.language,
      },
      timestamp: Date.now(),
    })
    return
  }

  // Other languages: execute
  showPreview.value = false
  const entry = await execute(store.code, store.language)
  store.addEntry(entry)
}

function handleClear() {
  clearHistory()
  store.clearHistory()
  showPreview.value = false
}

function handleToggleTheme() {
  store.toggleDarkMode()
}

function handleLanguageChange(lang: ProgrammingLanguage) {
  store.setLanguage(lang)
  showPreview.value = false
}

// Re-render preview when code changes for HTML/CSS
watch(
  () => store.code,
  (newCode) => {
    if (isPreviewLanguage.value && showPreview.value && previewContainer.value) {
      renderInSandbox(previewContainer.value, newCode, store.language as 'html' | 'css')
    }
  }
)
</script>

<template>
  <div class="code-terminal">
    <TerminalToolbar
      :language="store.language"
      :is-executing="isExecuting"
      :dark-mode="store.darkMode"
      @update:language="handleLanguageChange"
      @run="handleRun"
      @clear="handleClear"
      @toggle-theme="handleToggleTheme"
    />

    <div class="code-terminal__workspace">
      <div class="code-terminal__editor-panel">
        <CodeEditor
          v-model="store.code"
          :language="store.language"
          :dark-mode="store.darkMode"
          @run="handleRun"
        />
      </div>

      <div class="code-terminal__output-panel">
        <!-- Preview para HTML/CSS -->
        <div
          v-if="showPreview && isPreviewLanguage"
          class="code-terminal__preview"
        >
          <div class="code-terminal__preview-header">
            <span class="code-terminal__preview-title">Preview</span>
          </div>
          <div ref="previewContainer" class="code-terminal__preview-body" />
        </div>

        <!-- Terminal output -->
        <TerminalOutput
          :entries="store.history"
          :is-executing="isExecuting"
          @clear="handleClear"
        />
      </div>
    </div>

    <div class="code-terminal__statusbar">
      <span class="code-terminal__status-lang">{{ store.language }}</span>
      <span class="code-terminal__status-lines">{{ store.code.split('\n').length }} linhas</span>
      <span class="code-terminal__status-hint">Ctrl+Enter para executar</span>
    </div>
  </div>
</template>

<style scoped>
.code-terminal {
  display: flex;
  flex-direction: column;
  height: calc(100vh - 180px);
  min-height: 400px;
  gap: var(--spacing-sm);
}

.code-terminal__workspace {
  display: flex;
  flex: 1;
  gap: var(--spacing-sm);
  min-height: 0;
}

.code-terminal__editor-panel {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.code-terminal__output-panel {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.code-terminal__preview {
  flex: 1;
  display: flex;
  flex-direction: column;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
  min-height: 200px;
}

.code-terminal__preview-header {
  padding: var(--spacing-xs) var(--spacing-sm);
  background: var(--color-bg-soft);
  border-bottom: 1px solid var(--color-border);
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
}

.code-terminal__preview-body {
  flex: 1;
  background: white;
  overflow: auto;
}

.code-terminal__statusbar {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-xs) var(--spacing-md);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}

.code-terminal__status-lang {
  font-weight: 600;
  color: var(--color-primary);
}

.code-terminal__status-hint {
  margin-left: auto;
  opacity: 0.6;
}

/* Mobile: stack vertically */
@media (max-width: 768px) {
  .code-terminal__workspace {
    flex-direction: column;
  }

  .code-terminal {
    height: calc(100vh - 140px);
  }
}
</style>
