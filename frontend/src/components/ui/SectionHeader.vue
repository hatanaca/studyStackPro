<script setup lang="ts">
withDefaults(
  defineProps<{
    /** Título da seção (usa tag semântica h2) */
    title: string
    /** Descrição opcional abaixo do título */
    description?: string
  }>(),
  { description: '' }
)
</script>

<template>
  <header class="section-header">
    <div class="section-header__text">
      <h2 class="section-header__title">
        {{ title }}
      </h2>
      <p v-if="description || $slots.description" class="section-header__description">
        <slot name="description">
          {{ description }}
        </slot>
      </p>
    </div>
    <div v-if="$slots.actions" class="section-header__actions">
      <slot name="actions" />
    </div>
  </header>
</template>

<style scoped>
.section-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-lg);
}
.section-header__text {
  flex: 1;
  min-width: 0;
}
.section-header__title {
  font-size: var(--page-section-title-size, var(--text-base));
  font-weight: 600;
  color: var(--color-text);
  margin: var(--page-section-title-margin, 0 0 var(--spacing-xs));
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-snug);
}
.section-header__description {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
  line-height: var(--leading-snug);
}
.section-header__actions {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}
</style>
