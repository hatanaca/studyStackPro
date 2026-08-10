<script setup lang="ts">
import { ref } from 'vue'
import type { FaqItem } from '../types/study-content.types'

defineProps<{
  questions: FaqItem[]
}>()

const openIndex = ref<number | null>(0)

function toggle(index: number) {
  openIndex.value = openIndex.value === index ? null : index
}
</script>

<template>
  <div class="qa-panel">
    <div
      v-for="(faq, index) in questions"
      :key="index"
      class="qa-item"
      :class="{ open: openIndex === index }"
    >
      <button class="qa-question" type="button" @click="toggle(index)">
        <span class="qa-question-text">{{ faq.question }}</span>
        <i
          class="pi qa-icon"
          :class="openIndex === index ? 'pi-chevron-up' : 'pi-chevron-down'"
        ></i>
      </button>
      <transition name="qa">
        <div v-if="openIndex === index" class="qa-answer">
          <p>{{ faq.answer }}</p>
        </div>
      </transition>
    </div>
  </div>
</template>

<style scoped>
.qa-panel {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.qa-item {
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  overflow: hidden;
  background: var(--p-surface-0);
}

.qa-question {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  width: 100%;
  padding: 0.875rem 1rem;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--p-text-color);
}

.qa-question:hover {
  background: var(--p-surface-50);
}

.qa-question-text {
  flex: 1;
  min-width: 0;
}

.qa-icon {
  color: var(--p-text-muted-color);
  font-size: 0.75rem;
  flex-shrink: 0;
  transition: transform 0.2s;
}

.qa-answer {
  padding: 0 1rem 1rem;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
  line-height: 1.6;
}

.qa-answer p {
  margin: 0;
}

.qa-enter-active,
.qa-leave-active {
  transition: opacity 0.2s ease;
}
.qa-enter-from,
.qa-leave-to {
  opacity: 0;
}
</style>
