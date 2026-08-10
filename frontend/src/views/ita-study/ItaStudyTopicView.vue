<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import { useSubTopicsQuery } from '@/features/ita-study/composables/useItaStudyQuery'
import SubTopicCard from '@/features/ita-study/components/SubTopicCard.vue'

const route = useRoute()
const router = useRouter()
const subjectId = computed(() => route.params.subjectId as string)
const topicId = computed(() => route.params.topicId as string)

const { data: subTopics, isLoading } = useSubTopicsQuery(topicId)
</script>

<template>
  <div class="ita-study-topic">
    <div class="page-header">
      <Button
        icon="pi pi-arrow-left"
        text
        rounded
        @click="router.push(`/ita-study/${subjectId}`)"
      />
      <div>
        <h1>Sub-tópicos</h1>
        <p class="subtitle">Selecione um sub-tópico para praticar</p>
      </div>
    </div>

    <div v-if="isLoading" class="loading-list">
      <div v-for="i in 8" :key="i" class="skeleton-item"></div>
    </div>

    <div v-else class="subtopics-list">
      <SubTopicCard
        v-for="subTopic in subTopics"
        :key="subTopic.id"
        :sub-topic="subTopic"
        :subject-id="subjectId"
        :topic-id="topicId"
      />
    </div>
  </div>
</template>

<style scoped>
.ita-study-topic {
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

.subtopics-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.loading-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.skeleton-item {
  height: 60px;
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
