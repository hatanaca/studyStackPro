<script setup lang="ts">
import { useSubjectsQuery } from '@/features/ita-study/composables/useItaStudyQuery'
import SubjectCard from '@/features/ita-study/components/SubjectCard.vue'
import ProgressDashboard from '@/features/ita-study/components/ProgressDashboard.vue'

const { data: subjects, isLoading } = useSubjectsQuery()
</script>

<template>
  <div class="ita-study-home">
    <div class="page-header">
      <h1>Estudo ITA</h1>
      <p class="subtitle">Checklist completo para gabaritar o ITA</p>
    </div>

    <div class="home-layout">
      <div class="main-content">
        <div v-if="isLoading" class="loading-grid">
          <div v-for="i in 6" :key="i" class="skeleton-card"></div>
        </div>

        <div v-else class="subjects-grid">
          <SubjectCard
            v-for="subject in subjects"
            :key="subject.id"
            :subject="subject"
          />
        </div>
      </div>

      <aside class="sidebar-content">
        <ProgressDashboard />
      </aside>
    </div>
  </div>
</template>

<style scoped>
.ita-study-home {
  padding: 1.5rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
}

.subtitle {
  color: var(--p-text-muted-color);
  margin: 0;
}

.home-layout {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 1.5rem;
}

@media (max-width: 900px) {
  .home-layout {
    grid-template-columns: 1fr;
  }
}

.main-content {
  min-width: 0;
}

.subjects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}

.loading-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}

.skeleton-card {
  height: 100px;
  background: var(--p-surface-100);
  border-radius: 0.5rem;
  animation: pulse 1.5s infinite;
}

.sidebar-content {
  position: sticky;
  top: 1.5rem;
  align-self: start;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>
