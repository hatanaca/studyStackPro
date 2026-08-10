<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import type { StudySubTopic } from '../types/ita-study.types'

const props = defineProps<{
  subTopic: StudySubTopic
  subjectId?: string
  topicId?: string
}>()

const router = useRouter()

const statusIcon = computed(() => {
  if (props.subTopic.mastered) return 'pi-check-circle'
  if (props.subTopic.attempted > 0) return 'pi-refresh'
  return 'pi-circle'
})

const statusColor = computed(() => {
  if (props.subTopic.mastered) return '#10B981'
  if (props.subTopic.attempted > 0) return '#F59E0B'
  return '#6B7280'
})

const accuracy = computed(() => {
  if (props.subTopic.attempted === 0) return 0
  return Math.round((props.subTopic.correct / props.subTopic.attempted) * 100)
})

function openSubTopic() {
  if (props.subjectId && props.topicId) {
    router.push(`/ita-study/${props.subjectId}/${props.topicId}/${props.subTopic.id}`)
  } else {
    router.push(`/ita-study/session/${props.subTopic.id}`)
  }
}
</script>

<template>
  <div
    class="subtopic-card"
    @click="openSubTopic"
  >
    <div class="subtopic-status" :style="{ color: statusColor }">
      <i :class="statusIcon"></i>
    </div>
    <div class="subtopic-info">
      <h4 class="subtopic-name">{{ subTopic.name }}</h4>
      <div class="subtopic-stats" v-if="subTopic.attempted > 0">
        <span>{{ subTopic.correct }}/{{ subTopic.attempted }} acertos</span>
        <span class="separator">·</span>
        <span>{{ accuracy }}%</span>
      </div>
      <div class="subtopic-stats" v-else>
        <span>Nunca estudado</span>
      </div>
    </div>
    <div class="subtopic-action">
      <i class="pi pi-arrow-right"></i>
    </div>
  </div>
</template>

<style scoped>
.subtopic-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.subtopic-card:hover {
  border-color: var(--p-primary-color);
  background: var(--p-primary-50);
}

.subtopic-status {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.subtopic-info {
  flex: 1;
  min-width: 0;
}

.subtopic-name {
  font-size: 0.875rem;
  font-weight: 500;
  margin: 0 0 0.25rem 0;
}

.subtopic-stats {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}

.separator {
  margin: 0 0.25rem;
}

.subtopic-action {
  color: var(--p-text-muted-color);
  font-size: 0.75rem;
}
</style>
