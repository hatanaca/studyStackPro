<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import type { StudySubject } from '../types/ita-study.types'

const props = defineProps<{
  subject: StudySubject
}>()

const router = useRouter()

const progressPercentage = computed(() => props.subject.progress?.percentage ?? 0)
const progressColor = computed(() => {
  if (progressPercentage.value >= 80) return '#10B981'
  if (progressPercentage.value >= 50) return '#3B82F6'
  if (progressPercentage.value >= 20) return '#F59E0B'
  return '#6B7280'
})
</script>

<template>
  <div
    class="subject-card"
    @click="router.push(`/ita-study/${subject.id}`)"
  >
    <div class="subject-icon" :style="{ backgroundColor: subject.color + '20', color: subject.color }">
      <i :class="subject.icon"></i>
    </div>
    <div class="subject-info">
      <h3 class="subject-name">{{ subject.name }}</h3>
      <div class="subject-progress">
        <div class="progress-bar">
          <div
            class="progress-fill"
            :style="{ width: progressPercentage + '%', backgroundColor: progressColor }"
          ></div>
        </div>
        <span class="progress-text">{{ progressPercentage }}%</span>
      </div>
      <div class="subject-stats">
        <span>{{ subject.progress?.mastered ?? 0 }} / {{ subject.progress?.total ?? 0 }} dominados</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.subject-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.subject-card:hover {
  border-color: var(--p-primary-color);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.subject-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 3rem;
  height: 3rem;
  border-radius: 0.5rem;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.subject-info {
  flex: 1;
  min-width: 0;
}

.subject-name {
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.subject-progress {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
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
  border-radius: 3px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--p-text-color);
  min-width: 3rem;
  text-align: right;
}

.subject-stats {
  font-size: 0.75rem;
  color: var(--p-text-muted-color);
}
</style>
