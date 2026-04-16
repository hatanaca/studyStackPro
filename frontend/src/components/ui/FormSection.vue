<script setup lang="ts">
withDefaults(
  defineProps<{
    title: string
    description?: string
    /** Agrupa visualmente (borda/card) */
    grouped?: boolean
  }>(),
  { description: '', grouped: false }
)
</script>

<template>
  <section class="form-section" :class="{ 'form-section--grouped': grouped }">
    <header class="form-section__header">
      <h3 class="form-section__title">
        {{ title }}
      </h3>
      <p v-if="description || $slots.description" class="form-section__desc">
        <slot name="description">
          {{ description }}
        </slot>
      </p>
    </header>
    <div class="form-section__body">
      <slot />
    </div>
  </section>
</template>

<style scoped>
.form-section {
  margin-bottom: var(--spacing-xl);
}
.form-section:last-child {
  margin-bottom: 0;
}
.form-section--grouped {
  padding: var(--widget-padding);
  background: color-mix(in srgb, var(--color-bg-soft) 60%, var(--color-bg-card));
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}
.form-section__header {
  margin-bottom: var(--spacing-lg);
}
.form-section__title {
  font-size: var(--text-base);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--spacing-xs);
  letter-spacing: var(--tracking-tight);
}
.form-section__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
  line-height: var(--leading-snug);
}
.form-section__body {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}
</style>
