<script setup lang="ts">
import { ref } from 'vue'
import FormulaText from '@/components/math/FormulaText.vue'
import type { ContentBlock } from '../types/content-blocks'

const props = defineProps<{
  blocks: ContentBlock[]
}>()

const calloutMeta = {
  info: { icon: 'pi-info-circle', className: 'info' },
  warning: { icon: 'pi-exclamation-triangle', className: 'warning' },
  tip: { icon: 'pi-lightbulb', className: 'tip' },
  important: { icon: 'pi-exclamation-circle', className: 'important' },
} as const

const selectedQuiz = ref<number | null>(null)
const selectedOption = ref<number | null>(null)

function selectQuiz(index: number, option: number) {
  selectedQuiz.value = index
  selectedOption.value = option
}

function youtubeEmbedUrl(src: string): string {
  const match = src.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([\w-]+)/)
  return match ? `https://www.youtube.com/embed/${match[1]}` : src
}
</script>

<template>
  <div class="structured-content">
    <template v-for="(block, index) in props.blocks" :key="index">
      <h1
        v-if="block.type === 'heading' && block.level === 1"
        class="content-heading h1"
      >{{ block.text }}</h1>
      <h2
        v-else-if="block.type === 'heading' && block.level === 2"
        class="content-heading h2"
      >{{ block.text }}</h2>
      <h3
        v-else-if="block.type === 'heading' && block.level === 3"
        class="content-heading h3"
      >{{ block.text }}</h3>
      <h4
        v-else-if="block.type === 'heading'"
        class="content-heading h4"
      >{{ block.text }}</h4>

      <p
        v-else-if="block.type === 'paragraph'"
        class="content-paragraph"
        :class="block.alignment ?? 'left'"
      >{{ block.text }}</p>

      <div v-else-if="block.type === 'formula'" class="formula-block">
        <span v-if="block.label" class="formula-label">{{ block.label }}</span>
        <FormulaText :latex="block.latex" :display="block.display !== false" />
      </div>

      <div
        v-else-if="block.type === 'callout'"
        class="callout"
        :class="calloutMeta[block.variant]?.className ?? 'info'"
      >
        <div class="callout-header">
          <i :class="calloutMeta[block.variant]?.icon ?? 'pi-info-circle'"></i>
          <strong v-if="block.title">{{ block.title }}</strong>
          <strong v-else>Info</strong>
        </div>
        <p class="callout-text">{{ block.text }}</p>
      </div>

      <figure v-else-if="block.type === 'image'" class="content-image">
        <img
          :src="block.src"
          :alt="block.alt"
          :style="block.width ? { maxWidth: block.width + 'px' } : undefined"
        />
        <figcaption v-if="block.caption">{{ block.caption }}</figcaption>
      </figure>

      <ul
        v-else-if="block.type === 'list' && block.style !== 'ordered'"
        class="content-list"
      >
        <li
          v-for="(item, i) in block.items"
          :key="i"
          :class="{ 'checklist-item': block.style === 'checklist' }"
        >
          <i
            v-if="block.style === 'checklist'"
            class="pi pi-check-circle checklist-icon"
          ></i>
          {{ item }}
        </li>
      </ul>
      <ol v-else-if="block.type === 'list'" class="content-list ordered">
        <li v-for="(item, i) in block.items" :key="i">{{ item }}</li>
      </ol>

      <div v-else-if="block.type === 'table'" class="table-wrap">
        <table class="content-table" :class="{ striped: block.striped }">
          <thead>
            <tr>
              <th v-for="(header, i) in block.headers" :key="i">{{ header }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, i) in block.rows" :key="i">
              <td v-for="(cell, j) in row" :key="j">{{ cell }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else-if="block.type === 'code'" class="code-block">
        <div v-if="block.filename" class="code-filename">{{ block.filename }}</div>
        <pre><code>{{ block.code }}</code></pre>
      </div>

      <div v-else-if="block.type === 'video'" class="video-block">
        <iframe
          v-if="block.provider === 'youtube'"
          :src="youtubeEmbedUrl(block.src)"
          :title="block.caption ?? 'Vídeo'"
          allowfullscreen
        ></iframe>
        <video v-else-if="block.provider === 'local'" :src="block.src" controls></video>
        <p v-if="block.caption" class="video-caption">{{ block.caption }}</p>
      </div>

      <div
        v-else-if="block.type === 'quiz_preview'"
        class="quiz-preview"
      >
        <p class="quiz-question">{{ block.question }}</p>
        <div class="quiz-options">
          <div
            v-for="(option, i) in block.options"
            :key="i"
            class="quiz-option"
            :class="{ selected: selectedQuiz === index }"
            @click="selectQuiz(index, i)"
          >
            <i
              v-if="selectedQuiz === index && i === block.correctIndex"
              class="pi pi-check-circle correct"
            ></i>
            <i
              v-else-if="selectedQuiz === index && selectedOption === i"
              class="pi pi-times-circle wrong"
            ></i>
            {{ option }}
          </div>
        </div>
      </div>

      <hr v-else-if="block.type === 'divider'" class="content-divider" />

      <div
        v-else-if="block.type === 'space'"
        :class="`content-space ${block.size ?? 'md'}`"
      ></div>
    </template>
  </div>
</template>

<style scoped>
.structured-content {
  line-height: 1.7;
  color: var(--p-text-color);
}

.content-heading {
  margin: 1.75rem 0 0.75rem;
  font-weight: 700;
  line-height: 1.3;
}
.content-heading.h1 { font-size: 1.5rem; }
.content-heading.h2 { font-size: 1.3rem; }
.content-heading.h3 { font-size: 1.125rem; }
.content-heading.h4 { font-size: 1rem; }

.content-paragraph {
  margin: 0.75rem 0;
}
.content-paragraph.left { text-align: left; }
.content-paragraph.center { text-align: center; }
.content-paragraph.right { text-align: right; }

.formula-block {
  margin: 1rem 0;
  padding: 1rem;
  background: var(--p-surface-50);
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  text-align: center;
  overflow-x: auto;
}
.formula-label {
  display: block;
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
  margin-bottom: 0.5rem;
}

.callout {
  margin: 1rem 0;
  padding: 0.875rem 1rem;
  border-radius: 0.5rem;
  border-left: 4px solid;
  font-size: 0.875rem;
}
.callout.info { background: var(--p-blue-50); border-color: #3B82F6; }
.callout.warning { background: var(--p-yellow-50); border-color: #F59E0B; }
.callout.tip { background: var(--p-green-50); border-color: #10B981; }
.callout.important { background: var(--p-red-50); border-color: #EF4444; }

.callout-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.375rem;
}
.callout-text {
  margin: 0;
}

.content-image {
  margin: 1rem auto;
  text-align: center;
}
.content-image img {
  max-width: 100%;
  border-radius: 0.5rem;
}
.content-image figcaption {
  margin-top: 0.375rem;
  font-size: 0.8125rem;
  color: var(--p-text-muted-color);
}

.content-list {
  margin: 0.75rem 0;
  padding-left: 1.5rem;
}
.content-list li { margin: 0.375rem 0; }
.checklist-item {
  list-style: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-left: -1.5rem;
}
.checklist-icon {
  color: var(--p-green-500);
  font-size: 0.875rem;
}

.table-wrap {
  margin: 1rem 0;
  overflow-x: auto;
}
.content-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}
.content-table th,
.content-table td {
  padding: 0.625rem 0.75rem;
  border: 1px solid var(--p-border-color);
  text-align: left;
}
.content-table th {
  background: var(--p-surface-100);
  font-weight: 600;
}
.content-table.striped tbody tr:nth-child(even) {
  background: var(--p-surface-50);
}

.code-block {
  margin: 1rem 0;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  overflow: hidden;
}
.code-filename {
  padding: 0.375rem 0.75rem;
  background: var(--p-surface-100);
  font-size: 0.75rem;
  font-family: monospace;
  border-bottom: 1px solid var(--p-border-color);
}
.code-block pre {
  margin: 0;
  padding: 1rem;
  overflow-x: auto;
  background: var(--p-surface-900);
  color: var(--p-surface-0);
  font-size: 0.8125rem;
}

.video-block {
  margin: 1rem 0;
}
.video-block iframe {
  width: 100%;
  aspect-ratio: 16 / 9;
  border: none;
  border-radius: 0.5rem;
}
.video-block video {
  width: 100%;
  border-radius: 0.5rem;
}
.video-caption {
  margin-top: 0.375rem;
  font-size: 0.8125rem;
  color: var(--p-text-muted-color);
  text-align: center;
}

.quiz-preview {
  margin: 1rem 0;
  padding: 1rem;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  background: var(--p-surface-50);
}
.quiz-question {
  font-weight: 600;
  margin: 0 0 0.75rem;
}
.quiz-options {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.quiz-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--p-border-color);
  border-radius: 0.375rem;
  background: var(--p-surface-0);
  cursor: pointer;
  font-size: 0.875rem;
  transition: all 0.2s;
}
.quiz-option:hover {
  border-color: var(--p-primary-color);
}
.quiz-option.selected {
  border-color: var(--p-primary-color);
}
.quiz-option .correct { color: var(--p-green-600); }
.quiz-option .wrong { color: var(--p-red-500); }

.content-divider {
  border: none;
  border-top: 1px solid var(--p-border-color);
  margin: 1.5rem 0;
}
.content-space.sm { height: 0.5rem; }
.content-space.md { height: 1rem; }
.content-space.lg { height: 2rem; }
</style>
