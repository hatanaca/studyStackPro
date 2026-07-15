<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import type { TerminalEntry } from '../types/code-terminal.types'

const props = defineProps<{
  entries: TerminalEntry[]
  isExecuting: boolean
}>()

const emit = defineEmits<{
  clear: []
}>()

const outputContainer = ref<HTMLDivElement>()

function formatTime(ms: number): string {
  if (ms < 1) return '<1ms'
  if (ms < 1000) return `${ms}ms`
  return `${(ms / 1000).toFixed(2)}s`
}

function formatTimestamp(ts: number): string {
  return new Date(ts).toLocaleTimeString('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  })
}

watch(() => props.entries.length, async () => {
  await nextTick()
  if (outputContainer.value) {
    outputContainer.value.scrollTop = 0
  }
})
</script>

<template>
  <div class="terminal-output">
    <div class="terminal-output__header">
      <span class="terminal-output__title">
        <i class="pi pi-terminal" aria-hidden="true" />
        Output
      </span>
      <button
        v-if="entries.length > 0"
        type="button"
        class="terminal-output__clear"
        aria-label="Limpar output"
        @click="emit('clear')"
      >
        <i class="pi pi-trash" aria-hidden="true" />
      </button>
    </div>

    <div ref="outputContainer" class="terminal-output__body">
      <div v-if="isExecuting" class="terminal-output__loading">
        <span class="terminal-output__spinner" />
        Executando...
      </div>

      <div v-if="entries.length === 0 && !isExecuting" class="terminal-output__empty">
        <i class="pi pi-code" aria-hidden="true" />
        <p>Escreva código e clique em Run para ver o resultado.</p>
      </div>

      <div
        v-for="entry in entries"
        :key="entry.id"
        class="terminal-output__entry"
        :class="{ 'terminal-output__entry--error': !entry.result.success }"
      >
        <div class="terminal-output__entry-header">
          <span class="terminal-output__entry-lang">{{ entry.language }}</span>
          <span class="terminal-output__entry-time">{{ formatTimestamp(entry.timestamp) }}</span>
        </div>

        <div class="terminal-output__entry-code">
          <code>{{ entry.code.split('\n').slice(0, 3).join('\n') }}{{ entry.code.split('\n').length > 3 ? '\n...' : '' }}</code>
        </div>

        <div class="terminal-output__entry-result">
          <div v-if="entry.result.output" class="terminal-output__stdout">
            <span class="terminal-output__label">stdout:</span>
            <pre>{{ entry.result.output }}</pre>
          </div>

          <div v-if="entry.result.error" class="terminal-output__stderr">
            <span class="terminal-output__label">stderr:</span>
            <pre>{{ entry.result.error }}</pre>
          </div>

          <div class="terminal-output__meta">
            <span
              class="terminal-output__status"
              :class="entry.result.success ? 'terminal-output__status--ok' : 'terminal-output__status--fail'"
            >
              {{ entry.result.success ? 'OK' : 'ERRO' }}
            </span>
            <span class="terminal-output__duration">{{ formatTime(entry.result.executionTime) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.terminal-output {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #1a1a2e;
  border-radius: var(--radius-md);
  border: 1px solid #333;
  overflow: hidden;
}

.terminal-output__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-sm) var(--spacing-md);
  background: #16213e;
  border-bottom: 1px solid #333;
}

.terminal-output__title {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--text-sm);
  font-weight: 600;
  color: #a0a0a0;
}

.terminal-output__clear {
  background: none;
  border: none;
  color: #666;
  cursor: pointer;
  padding: var(--spacing-xs);
  border-radius: var(--radius-sm);
  transition: color var(--duration-fast) ease;
}

.terminal-output__clear:hover {
  color: #ff6b6b;
}

.terminal-output__body {
  flex: 1;
  overflow-y: auto;
  padding: var(--spacing-md);
  font-family: var(--font-mono, 'Fira Code', monospace);
  font-size: var(--text-sm);
  color: #e0e0e0;
}

.terminal-output__loading {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  color: #64d2ff;
  padding: var(--spacing-sm) 0;
}

.terminal-output__spinner {
  width: 14px;
  height: 14px;
  border: 2px solid #333;
  border-top-color: #64d2ff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.terminal-output__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #666;
  text-align: center;
  gap: var(--spacing-sm);
}

.terminal-output__empty i {
  font-size: 2rem;
  opacity: 0.5;
}

.terminal-output__empty p {
  font-size: var(--text-sm);
  margin: 0;
}

.terminal-output__entry {
  margin-bottom: var(--spacing-md);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid #2a2a3e;
}

.terminal-output__entry:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.terminal-output__entry--error {
  border-left: 3px solid #ff6b6b;
  padding-left: var(--spacing-sm);
}

.terminal-output__entry-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-xs);
}

.terminal-output__entry-lang {
  font-size: var(--text-xs);
  font-weight: 600;
  color: #64d2ff;
  text-transform: uppercase;
}

.terminal-output__entry-time {
  font-size: var(--text-xs);
  color: #666;
}

.terminal-output__entry-code {
  background: #0d1117;
  border-radius: var(--radius-sm);
  padding: var(--spacing-sm);
  margin-bottom: var(--spacing-sm);
  overflow-x: auto;
}

.terminal-output__entry-code code {
  font-size: var(--text-xs);
  color: #8b949e;
  white-space: pre;
}

.terminal-output__entry-result {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.terminal-output__stdout {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.terminal-output__stderr {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.terminal-output__label {
  font-size: var(--text-xs);
  color: #666;
  font-weight: 600;
}

.terminal-output__stdout pre {
  margin: 0;
  color: #7ee787;
  white-space: pre-wrap;
  word-break: break-word;
}

.terminal-output__stderr pre {
  margin: 0;
  color: #ff6b6b;
  white-space: pre-wrap;
  word-break: break-word;
}

.terminal-output__meta {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-xs);
}

.terminal-output__status {
  font-size: var(--text-xs);
  font-weight: 700;
  padding: 2px 6px;
  border-radius: var(--radius-sm);
}

.terminal-output__status--ok {
  color: #7ee787;
  background: rgba(126, 231, 135, 0.1);
}

.terminal-output__status--fail {
  color: #ff6b6b;
  background: rgba(255, 107, 107, 0.1);
}

.terminal-output__duration {
  font-size: var(--text-xs);
  color: #666;
}
</style>
