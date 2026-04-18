<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { prefetchTechnologyDetailView } from '@/router/prefetch'
import BaseButton from '@/components/ui/BaseButton.vue'
import type { Technology } from '@/types/domain.types'

const props = defineProps<{
  technology: Technology
}>()

const emit = defineEmits<{
  edit: [Technology]
  delete: [Technology]
}>()

const router = useRouter()

const subtitle = computed(() => {
  const d = props.technology.description?.trim()
  if (d) return d
  return 'Detalhes da tecnologia e sessões registadas.'
})
</script>

<template>
  <article
    class="tech-card-v2"
    :style="{ '--tech-color': technology.color }"
    @mouseenter="prefetchTechnologyDetailView"
  >
    <div class="tech-card-v2__accent" aria-hidden="true" />
    <div class="tech-card-v2__body">
      <div class="tech-card-v2__head">
        <h3 class="tech-card-v2__title">{{ technology.name }}</h3>
        <span class="tech-card-v2__slug" :title="technology.slug">{{ technology.slug }}</span>
      </div>
      <p class="tech-card-v2__desc">{{ subtitle }}</p>
      <div class="tech-card-v2__actions">
        <BaseButton
          variant="primary"
          size="md"
          class="tech-card-v2__primary"
          type="button"
          @click="router.push({ name: 'technology-detail', params: { id: technology.id } })"
        >
          Sessões &amp; Detalhes
        </BaseButton>
        <div class="tech-card-v2__secondary">
          <BaseButton variant="secondary" size="sm" type="button" @click="emit('edit', technology)">
            Editar
          </BaseButton>
          <BaseButton variant="danger" size="sm" type="button" @click="emit('delete', technology)">
            Excluir
          </BaseButton>
        </div>
      </div>
    </div>
  </article>
</template>

<style scoped>
.tech-card-v2 {
  --tech-card-accent: var(--tech-color, var(--color-primary));
  position: relative;
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.tech-card-v2:hover {
  border-color: color-mix(in srgb, var(--tech-card-accent) 38%, var(--color-border));
  box-shadow: var(--shadow-md);
}
.tech-card-v2__accent {
  height: 6px;
  width: 100%;
  background: var(--tech-card-accent);
}
.tech-card-v2__body {
  padding: var(--spacing-md) var(--spacing-lg) var(--spacing-lg);
}
.tech-card-v2__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: var(--spacing-md);
  min-width: 0;
}
.tech-card-v2__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: var(--text-base);
  font-weight: 700;
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
  color: var(--color-text);
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.tech-card-v2__slug {
  flex-shrink: 0;
  max-width: 42%;
  font-size: var(--text-xs);
  font-weight: 500;
  color: var(--color-text-muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.tech-card-v2__desc {
  margin: var(--spacing-sm) 0 0;
  font-size: var(--text-sm);
  line-height: var(--leading-snug);
  color: var(--color-text-muted);
}
.tech-card-v2__actions {
  margin-top: var(--spacing-lg);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}
.tech-card-v2__primary {
  width: 100%;
  justify-content: center;
}
.tech-card-v2__secondary {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-sm);
}

[data-theme='dark'] .tech-card-v2 {
  background: color-mix(in srgb, var(--color-bg-card) 92%, #000 8%);
  border-color: color-mix(in srgb, var(--color-border) 70%, var(--color-text-muted) 30%);
}

/* Botões integrados ao cartão (evita blocos sólidos azul/vermelho iguais em claro e escuro) */
.tech-card-v2 :deep(.base-button.base-button--primary) {
  background: color-mix(in srgb, var(--color-primary) 14%, var(--color-bg-card));
  color: var(--color-primary);
  border-color: color-mix(in srgb, var(--color-primary) 40%, var(--color-border));
  box-shadow: none;
}
.tech-card-v2 :deep(.base-button.base-button--primary:hover:not(:disabled)) {
  background: color-mix(in srgb, var(--color-primary) 24%, var(--color-bg-soft));
  border-color: color-mix(in srgb, var(--color-primary) 55%, var(--color-border));
  color: var(--color-primary-hover);
}
.tech-card-v2 :deep(.base-button.base-button--secondary) {
  background: color-mix(in srgb, var(--color-text) 6%, var(--color-bg-card));
  border-color: var(--color-border);
  color: var(--color-text);
  box-shadow: none;
}
.tech-card-v2 :deep(.base-button.base-button--secondary:hover:not(:disabled)) {
  background: color-mix(in srgb, var(--color-primary) 10%, var(--color-bg-soft));
  border-color: color-mix(in srgb, var(--color-primary) 32%, var(--color-border));
  color: var(--color-primary);
}
.tech-card-v2 :deep(.base-button.base-button--danger) {
  background: color-mix(in srgb, var(--color-error) 12%, var(--color-bg-card));
  color: color-mix(in srgb, var(--color-error) 88%, var(--color-text));
  border-color: color-mix(in srgb, var(--color-error) 32%, var(--color-border));
  box-shadow: none;
}
.tech-card-v2 :deep(.base-button.base-button--danger:hover:not(:disabled)) {
  background: var(--color-error);
  border-color: var(--color-error);
  color: var(--color-primary-contrast);
}
[data-theme='dark'] .tech-card-v2 :deep(.base-button.base-button--primary) {
  background: color-mix(in srgb, var(--color-primary) 20%, var(--color-bg-soft));
  color: var(--color-primary);
  border-color: color-mix(in srgb, var(--color-primary) 35%, var(--color-border));
}
[data-theme='dark'] .tech-card-v2 :deep(.base-button.base-button--danger) {
  background: color-mix(in srgb, var(--color-error) 18%, var(--color-bg-soft));
  color: color-mix(in srgb, var(--color-error) 90%, var(--color-text));
  border-color: color-mix(in srgb, var(--color-error) 38%, var(--color-border));
}
</style>
