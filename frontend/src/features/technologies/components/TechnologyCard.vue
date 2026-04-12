<script setup lang="ts">
import { useRouter } from 'vue-router'
import { prefetchTechnologyDetailView } from '@/router/prefetch'
import BaseButton from '@/components/ui/BaseButton.vue'
// RouterLink removed — using programmatic navigation to avoid nesting interactive elements
import BaseCard from '@/components/ui/BaseCard.vue'
import type { Technology } from '@/types/domain.types'

const router = useRouter()

defineProps<{
  technology: Technology
}>()

const emit = defineEmits<{
  edit: [Technology]
  delete: [Technology]
}>()
</script>

<template>
  <BaseCard
    class="technology-card"
    :style="{ '--tech-color': technology.color }"
  >
    <template #default>
      <div
        class="technology-card__content"
        @mouseenter="prefetchTechnologyDetailView"
      >
        <div class="technology-card__main">
          <span class="technology-card__name">{{ technology.name }}</span>
          <span class="technology-card__slug">{{ technology.slug }}</span>
        </div>
        <p
          v-if="technology.description"
          class="technology-card__desc"
        >
          {{ technology.description }}
        </p>
        <div class="technology-card__actions">
          <BaseButton
            variant="primary"
            size="md"
            class="technology-card__primary-button"
            @click="router.push({ name: 'technology-detail', params: { id: technology.id } })"
          >
            Sessões &amp; Detalhes
          </BaseButton>
          <div class="technology-card__secondary">
            <BaseButton
              variant="secondary"
              size="sm"
              type="button"
              @click="emit('edit', technology)"
            >
              Editar
            </BaseButton>
            <BaseButton
              variant="danger"
              size="sm"
              type="button"
              @click="emit('delete', technology)"
            >
              Excluir
            </BaseButton>
          </div>
        </div>
      </div>
    </template>
  </BaseCard>
</template>

<style scoped>
.technology-card {
  border-color: color-mix(in srgb, var(--tech-color, var(--color-primary)) 22%, var(--color-border));
}
.technology-card__content {
  padding: var(--widget-padding);
}
.technology-card__main {
  display: flex;
  align-items: baseline;
  gap: var(--spacing-sm);
  min-width: 0;
}
.technology-card__name {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: var(--text-base);
  color: var(--color-text);
  letter-spacing: var(--tracking-tight);
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
}
.technology-card__slug {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 50%;
}
.technology-card__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: var(--spacing-sm) 0 0;
  line-height: var(--leading-snug);
}
.technology-card__actions {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-lg);
}
.technology-card__secondary {
  display: flex;
  gap: var(--spacing-sm);
}
.technology-card__primary-button {
  width: 100%;
  justify-content: center;
}
</style>
