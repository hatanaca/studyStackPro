<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { Technology } from '@/types/domain.types'

defineProps<{
  technology: Technology
}>()

const emit = defineEmits<{
  edit: [Technology]
  delete: [Technology]
}>()
</script>

<template>
  <div
    class="technology-card"
    :style="{ '--tech-color': technology.color }"
  >
    <div class="technology-card__bar" />
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
          class="btn btn--primary"
        >
          Ver detalhes
        </RouterLink>
        <button
          type="button"
          class="btn btn--ghost"
          @click="emit('edit', technology)"
        >
          Editar
        </button>
        <button
          type="button"
          class="btn btn--ghost btn--danger"
          @click="emit('delete', technology)"
        >
          Excluir
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.technology-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  transition: box-shadow var(--duration-normal) var(--ease-in-out), border-color var(--duration-fast) ease;
}
.technology-card:hover {
  box-shadow: var(--shadow-md);
  border-color: color-mix(in srgb, var(--tech-color, var(--color-primary)) 40%, var(--color-border));
}
.technology-card__bar {
  height: 3px;
  background: var(--tech-color, var(--color-primary));
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
  line-height: 1.45;
}
.technology-card__actions {
  display: flex;
  gap: var(--spacing-xs);
  margin-top: var(--spacing-md);
  flex-wrap: wrap;
}
.btn {
  padding: 0.3rem 0.6rem;
  font-size: var(--text-xs);
  font-weight: 500;
  border: 1px solid transparent;
  border-radius: var(--radius-sm);
  cursor: pointer;
  background: transparent;
  color: var(--color-text-muted);
  text-decoration: none;
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease, border-color var(--duration-fast) ease, transform var(--duration-fast) ease;
}
.btn--ghost:hover {
  background: var(--color-bg-soft);
  color: var(--color-text);
}
.btn--danger:hover {
  background: var(--color-error-soft);
  color: var(--color-error);
}
.btn--primary {
  padding: 0.35rem 0.65rem;
  font-size: var(--text-xs);
  font-weight: 600;
  border-radius: var(--radius-sm);
  text-decoration: none;
  background: var(--tech-color, var(--color-primary));
  color: #fff;
  border: 1px solid transparent;
  cursor: pointer;
  transition: opacity var(--duration-fast) ease, transform var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.btn--primary:hover {
  opacity: 0.95;
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}
</style>
