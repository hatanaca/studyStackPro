<script setup lang="ts">
import { computed, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import QuestionPanel from '@/features/ita-study/components/QuestionPanel.vue'
import type { StudyAttemptResult } from '@/features/ita-study/types/ita-study.types'

const route = useRoute()
const router = useRouter()
const subTopicId = computed(() => route.params.subTopicId as string)

const stats = reactive({
  correct: 0,
  total: 0,
})

function handleComplete(result: StudyAttemptResult) {
  stats.total++
  if (result.is_correct) {
    stats.correct++
  }
}

const accuracy = computed(() => {
  if (stats.total === 0) return 0
  return Math.round((stats.correct / stats.total) * 100)
})
</script>

<template>
  <div class="ita-study-session">
    <div class="session-header">
      <Button icon="pi pi-arrow-left" text rounded @click="router.back()" />
      <div class="session-info">
        <h1>Sessão de Estudo</h1>
        <div class="session-stats">
          <span class="stat">
            <i class="pi pi-check"></i>
            {{ stats.correct }}/{{ stats.total }}
          </span>
          <span v-if="stats.total > 0" class="stat"> {{ accuracy }}% acerto </span>
        </div>
      </div>
    </div>

    <QuestionPanel :sub-topic-id="subTopicId" @complete="handleComplete" />
  </div>
</template>

<style scoped>
.ita-study-session {
  padding: 1.5rem;
  max-width: 800px;
  margin: 0 auto;
}

.session-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.session-info h1 {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
}

.session-stats {
  display: flex;
  gap: 1rem;
  margin-top: 0.25rem;
}

.stat {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
}
</style>
