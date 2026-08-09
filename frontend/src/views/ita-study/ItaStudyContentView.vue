<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import {
  useSubTopicDetailQuery,
  useAddFavoriteMutation,
  useRemoveFavoriteMutation,
  useUpdateReadingProgressMutation,
} from '@/features/ita-study/composables/useUserStudyQuery'
import InteractiveSimulation from '@/features/ita-study/components/InteractiveSimulation.vue'
import StructuredContent from '@/features/ita-study/components/StructuredContent.vue'
import QAPanel from '@/features/ita-study/components/QAPanel.vue'
import PersonalNotes from '@/features/ita-study/components/PersonalNotes.vue'
import ReadingProgressBar from '@/features/ita-study/components/ReadingProgressBar.vue'
import QuestionPanel from '@/features/ita-study/components/QuestionPanel.vue'

const route = useRoute()
const router = useRouter()

const subjectId = computed(() => route.params.subjectId as string)
const topicId = computed(() => route.params.topicId as string)
const subTopicId = computed(() => route.params.subTopicId as string)

const { data: subTopic, isLoading } = useSubTopicDetailQuery(subTopicId)
const updateReadingMutation = useUpdateReadingProgressMutation()

const isFavorited = ref(false)
watch(
  subTopic,
  (value) => {
    isFavorited.value = value?.is_favorited ?? false
  },
  { immediate: true }
)

const addFavoriteMutation = useAddFavoriteMutation()
const removeFavoriteMutation = useRemoveFavoriteMutation()

function toggleFavorite() {
  if (isFavorited.value) {
    removeFavoriteMutation.mutate(subTopicId.value, {
      onSuccess: () => {
        isFavorited.value = false
      },
    })
  } else {
    addFavoriteMutation.mutate(subTopicId.value, {
      onSuccess: () => {
        isFavorited.value = true
      },
    })
  }
}

// Progresso de leitura: calculado pela posição de rolagem no conteúdo
const contentRef = ref<HTMLElement | null>(null)
const scrollProgress = ref(0)
let lastSavedProgress = 0
let saveTimer: ReturnType<typeof setTimeout> | null = null

function computeScrollProgress() {
  const el = contentRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  const total = el.offsetHeight + rect.top - window.innerHeight
  if (total <= 0) return
  const passed = window.innerHeight - rect.top
  scrollProgress.value = Math.round(Math.min(Math.max(passed / total, 0), 1) * 100)
}

function persistProgress() {
  const value = scrollProgress.value
  if (Math.abs(value - lastSavedProgress) < 5) return
  lastSavedProgress = value
  updateReadingMutation.mutate({ subTopicId: subTopicId.value, progress: value })
}

function onScroll() {
  computeScrollProgress()
  if (saveTimer) clearTimeout(saveTimer)
  saveTimer = setTimeout(persistProgress, 800)
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  computeScrollProgress()
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  if (saveTimer) clearTimeout(saveTimer)
})

const hasInteractive = computed(() => !!subTopic.value?.simulation_config)
const hasContent = computed(() => !!subTopic.value?.content?.blocks?.length)
const hasFaqs = computed(() => !!subTopic.value?.faqs?.length)
const hasObjectives = computed(() => !!subTopic.value?.learning_objectives?.length)

function goBack() {
  router.push(`/ita-study/${subjectId.value}/${topicId.value}`)
}
</script>

<template>
  <div class="study-content-page">
    <ReadingProgressBar :progress="scrollProgress" />

    <div v-if="isLoading" class="loading-state">
      <div v-for="i in 3" :key="i" class="skeleton-block"></div>
    </div>

    <template v-else-if="subTopic">
      <!-- Cabeçalho -->
      <div class="content-header">
        <div class="header-main">
          <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
          <div class="header-text">
            <h1>{{ subTopic.name }}</h1>
            <p v-if="subTopic.description" class="subtitle">{{ subTopic.description }}</p>
          </div>
        </div>
        <Button
          :icon="isFavorited ? 'pi pi-heart-fill' : 'pi pi-heart'"
          :aria-label="isFavorited ? 'Remover dos favoritos' : 'Adicionar aos favoritos'"
          :class="['favorite-btn', { active: isFavorited }]"
          text
          rounded
          @click="toggleFavorite"
        />
      </div>

      <!-- Objetivos -->
      <div v-if="hasObjectives" class="objectives-box">
        <div class="objectives-title">
          <i class="pi pi-bullseye"></i>
          <strong>Objetivos de aprendizado</strong>
        </div>
        <ul>
          <li v-for="(objective, i) in subTopic.learning_objectives" :key="i">
            {{ objective }}
          </li>
        </ul>
      </div>

      <!-- 1. Exploração interativa -->
      <section v-if="hasInteractive" class="content-section">
        <div class="section-title">
          <i class="pi pi-sliders-h"></i>
          <h2>Exploração Interativa</h2>
        </div>
        <InteractiveSimulation :config="subTopic.simulation_config!" />
      </section>

      <!-- 2. Conteúdo explicativo -->
      <section v-if="hasContent" ref="contentRef" class="content-section">
        <div class="section-title">
          <i class="pi pi-book"></i>
          <h2>Conteúdo</h2>
        </div>
        <StructuredContent :blocks="subTopic.content!.blocks" />
      </section>

      <!-- 3. Notas pessoais -->
      <section class="content-section">
        <div class="section-title">
          <i class="pi pi-pencil"></i>
          <h2>Minhas Notas</h2>
        </div>
        <PersonalNotes :sub-topic-id="subTopic.id" />
      </section>

      <!-- 4. Perguntas frequentes -->
      <section v-if="hasFaqs" class="content-section">
        <div class="section-title">
          <i class="pi pi-question-circle"></i>
          <h2>Perguntas Frequentes</h2>
        </div>
        <QAPanel :questions="subTopic.faqs!" />
      </section>

      <!-- 5. Prática / gerador de exercícios -->
      <section class="content-section practice-section">
        <div class="section-title">
          <i class="pi pi-bolt"></i>
          <h2>Pratique</h2>
        </div>
        <QuestionPanel :sub-topic-id="subTopic.id" />
      </section>
    </template>

    <div v-else class="empty-state">
      <i class="pi pi-inbox"></i>
      <span>Sub-tópico não encontrado.</span>
      <Button label="Voltar" @click="goBack" />
    </div>
  </div>
</template>

<style scoped>
.study-content-page {
  padding: 1rem 1.5rem 3rem;
  max-width: 860px;
  margin: 0 auto;
}

.loading-state {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-top: 1.5rem;
}

.skeleton-block {
  height: 160px;
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

.content-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin: 1rem 0 1.5rem;
}

.header-main {
  display: flex;
  align-items: flex-start;
  gap: 0.875rem;
  min-width: 0;
}

.header-text h1 {
  font-size: 1.375rem;
  font-weight: 700;
  margin: 0 0 0.25rem;
  line-height: 1.3;
}

.subtitle {
  margin: 0;
  color: var(--p-text-muted-color);
  font-size: 0.875rem;
}

.favorite-btn.active {
  color: #ef4444;
}

.objectives-box {
  margin-bottom: 1.5rem;
  padding: 1rem 1.25rem;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  background: var(--p-surface-50);
}

.objectives-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  color: var(--p-primary-color);
  font-size: 0.9375rem;
}

.objectives-box ul {
  margin: 0;
  padding-left: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  font-size: 0.875rem;
}

.content-section {
  margin-bottom: 2.5rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--p-border-color);
}

.section-title i {
  color: var(--p-primary-color);
  font-size: 1rem;
}

.section-title h2 {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
}

.practice-section {
  border-top: 2px solid var(--p-border-color);
  padding-top: 1.5rem;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 4rem 1rem;
  color: var(--p-text-muted-color);
}
</style>
