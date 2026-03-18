<script setup lang="ts">
import { RouterLink } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import type { Technology } from '@/types/domain.types'

defineProps<{
  technology: Technology
}>()

const emit = defineEmits<{
  edit: [Technology]
  delete: [Technology]
}>()

// #region agent log
fetch('http://127.0.0.1:7573/ingest/086e8d00-457e-4a30-82b0-abf450d19c28', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-Debug-Session-Id': '7879a4',
  },
  body: JSON.stringify({
    sessionId: '7879a4',
    runId: 'pre-fix',
    hypothesisId: 'H-tech-card-standardization',
    location: 'TechnologyCard.vue:16',
    message: 'TechnologyCard rendered using BaseCard/BaseButton',
    data: {},
    timestamp: Date.now(),
  }),
}).catch(() => {})
// #endregion agent log
</script>

<template>
  <BaseCard
    class="technology-card"
    :style="{ '--tech-color': technology.color }"
  >
    <template #default>
      <div class="technology-card__content">
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
          <RouterLink
            :to="{ name: 'technology-detail', params: { id: technology.id } }"
            class="technology-card__primary-link"
          >
            <BaseButton
              variant="primary"
              size="md"
              class="technology-card__primary-button"
            >
              Ver detalhes
            </BaseButton>
          </RouterLink>
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
  font-weight: 600;
  font-size: var(--text-base);
  color: var(--color-text);
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
.technology-card__primary-link {
  display: inline-flex;
  flex: 1;
  min-width: 0;
  text-decoration: none;
}
.technology-card__primary-button {
  width: 100%;
  justify-content: center;
}
</style>
