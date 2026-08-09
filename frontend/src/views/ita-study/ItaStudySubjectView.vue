<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import { useTopicsQuery } from '@/features/ita-study/composables/useItaStudyQuery'
import DifficultyBadge from '@/features/ita-study/components/DifficultyBadge.vue'

const route = useRoute()
const router = useRouter()
const subjectId = computed(() => route.params.subjectId as string)

const { data: topics, isLoading } = useTopicsQuery(subjectId)

function goToTopic(topicId: string) {
  router.push(`/ita-study/${subjectId.value}/${topicId}`)
}
</script>

<template>
  <div class="ita-study-subject">
    <div class="page-header">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push('/ita-study')" />
      <div>
        <h1>Tópicos</h1>
        <p class="subtitle">Selecione um tópico para estudar</p>
      </div>
    </div>

    <div v-if="isLoading" class="loading-list">
      <div v-for="i in 10" :key="i" class="skeleton-item"></div>
    </div>

    <div v-else class="topics-list">
      <div v-for="topic in topics" :key="topic.id" class="topic-item" @click="goToTopic(topic.id)">
        <div class="topic-info">
          <h3 class="topic-name">{{ topic.name }}</h3>
          <div class="topic-meta">
            <DifficultyBadge :level="topic.difficulty" />
            <span class="topic-count">{{ topic.sub_topics_count }} sub-tópicos</span>
          </div>
        </div>
        <div class="topic-progress">
          <div class="progress-bar">
            <div
              class="progress-fill"
              :style="{ width: (topic.progress?.percentage ?? 0) + '%' }"
            ></div>
          </div>
          <span class="progress-text">{{ topic.progress?.percentage ?? 0 }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ita-study-subject {
  padding: 1.5rem;
  max-width: 900px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
}

.subtitle {
  color: var(--p-text-muted-color);
  margin: 0.25rem 0 0 0;
}

.topics-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.topic-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.topic-item:hover {
  border-color: var(--p-primary-color);
  background: var(--p-primary-50);
}

.topic-info {
  flex: 1;
  min-width: 0;
}

.topic-name {
  font-size: 0.9375rem;
  font-weight: 500;
  margin: 0 0 0.5rem 0;
}

.topic-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.topic-count {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}

.topic-progress {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 120px;
}

.progress-bar {
  flex: 1;
  height: 6px;
  background: var(--p-content-200);
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: var(--p-primary-color);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.75rem;
  font-weight: 600;
  min-width: 3rem;
  text-align: right;
}

.loading-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.skeleton-item {
  height: 70px;
  background: var(--p-surface-100);
  border-radius: 0.5rem;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}
</style>
