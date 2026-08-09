<script setup lang="ts">
import { computed } from 'vue'
import { useProgressQuery } from '../composables/useItaStudyQuery'

const { data: progress, isLoading } = useProgressQuery()

const overallPercentage = computed(() => progress.value?.overall?.percentage ?? 0)
const overallMastered = computed(() => progress.value?.overall?.mastered ?? 0)
const overallTotal = computed(() => progress.value?.overall?.total ?? 0)
</script>

<template>
  <div class="progress-dashboard">
    <div v-if="isLoading" class="loading-state">
      <i class="pi pi-spin pi-spinner"></i>
    </div>

    <template v-else>
      <div class="overall-card">
        <div class="overall-header">
          <h3>Progresso Geral</h3>
          <span class="overall-percentage">{{ overallPercentage }}%</span>
        </div>
        <div class="overall-bar">
          <div
            class="overall-fill"
            :style="{ width: overallPercentage + '%' }"
          ></div>
        </div>
        <p class="overall-text">{{ overallMastered }} de {{ overallTotal }} sub-tópicos dominados</p>
      </div>

      <div class="subjects-list">
        <div
          v-for="subject in progress?.subjects"
          :key="subject.id"
          class="subject-row"
        >
          <div class="subject-color" :style="{ backgroundColor: subject.color }"></div>
          <span class="subject-name">{{ subject.name }}</span>
          <div class="subject-bar">
            <div
              class="subject-fill"
              :style="{ width: subject.progress.percentage + '%', backgroundColor: subject.color }"
            ></div>
          </div>
          <span class="subject-percentage">{{ subject.progress.percentage }}%</span>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.progress-dashboard {
  padding: 1.5rem;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 2rem;
  color: var(--p-text-muted-color);
}

.overall-card {
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--p-border-color);
}

.overall-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.overall-header h3 {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
}

.overall-percentage {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--p-primary-color);
}

.overall-bar {
  height: 8px;
  background: var(--p-content-200);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.overall-fill {
  height: 100%;
  background: var(--p-primary-color);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.overall-text {
  font-size: 0.875rem;
  color: var(--p-text-muted-color);
  margin: 0;
}

.subjects-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.subject-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.subject-color {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.subject-name {
  font-size: 0.875rem;
  min-width: 100px;
}

.subject-bar {
  flex: 1;
  height: 6px;
  background: var(--p-content-200);
  border-radius: 3px;
  overflow: hidden;
}

.subject-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s ease;
}

.subject-percentage {
  font-size: 0.75rem;
  font-weight: 600;
  min-width: 3rem;
  text-align: right;
}
</style>
