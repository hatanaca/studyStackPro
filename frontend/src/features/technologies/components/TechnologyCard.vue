<script setup lang="ts">
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
  background: #fff;
  border-radius: 0.5rem;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}
.technology-card__bar {
  height: 4px;
  background: var(--tech-color, #3498db);
}
.technology-card__content {
  padding: 1rem;
}
.technology-card__main {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}
.technology-card__name {
  font-weight: 600;
  color: #1e293b;
}
.technology-card__slug {
  font-size: 0.875rem;
  color: #64748b;
}
.technology-card__desc {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0.5rem 0 0;
  line-height: 1.4;
}
.technology-card__actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.75rem;
}
.btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.8125rem;
  border: none;
  border-radius: 0.25rem;
  cursor: pointer;
  background: transparent;
  color: #64748b;
}
.btn--ghost:hover {
  background: #f1f5f9;
  color: #475569;
}
.btn--danger:hover {
  background: #fef2f2;
  color: #dc2626;
}
</style>
