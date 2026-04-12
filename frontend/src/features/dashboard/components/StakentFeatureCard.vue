<script setup lang="ts">
</script>

<template>
  <article class="stakent-feature-card">
    <div class="stakent-feature-card__badge">
      Novo
    </div>
    <div class="stakent-feature-card__brand">
      <span class="stakent-feature-card__logo">StudyTrack Pro</span>
    </div>
    <h3 class="stakent-feature-card__title">
      Resumo de Estudos
    </h3>
    <p class="stakent-feature-card__desc">
      Um resumo completo que ajuda você a acompanhar investimento em conhecimento, métricas por tecnologia e progresso semanal.
    </p>
    <div class="stakent-feature-card__actions">
      <router-link
        to="/"
        class="stakent-feature-card__btn stakent-feature-card__btn--primary"
      >
        <span class="stakent-feature-card__btn-icon">📊</span>
        Ver Dashboard
      </router-link>
      <router-link
        to="/technologies"
        class="stakent-feature-card__btn stakent-feature-card__btn--secondary"
      >
        Ver tecnologias
      </router-link>
    </div>
  </article>
</template>

<style scoped>
.stakent-feature-card {
  position: relative;
  padding: var(--spacing-xl);
  border-radius: var(--radius-card, var(--radius-lg));
  background: linear-gradient(135deg, color-mix(in srgb, var(--color-primary) 35%, transparent), color-mix(in srgb, var(--color-primary) 20%, transparent));
  border: 1px solid color-mix(in srgb, var(--color-primary) 40%, transparent);
  box-shadow: var(--shadow-card, var(--shadow-md)), 0 0 60px color-mix(in srgb, var(--color-primary) 10%, transparent);
  overflow: hidden;
}
.stakent-feature-card__badge {
  position: absolute;
  top: var(--spacing-sm);
  right: var(--spacing-sm);
  font-size: var(--text-xs);
  font-weight: 700;
  padding: var(--spacing-2xs) var(--spacing-sm);
  border-radius: var(--radius-sm);
  background: var(--color-primary);
  color: var(--color-primary-contrast);
  text-transform: uppercase;
  letter-spacing: var(--tracking-wide);
}
.stakent-feature-card__brand {
  margin-bottom: var(--spacing-sm);
}
.stakent-feature-card__logo {
  font-size: var(--text-xs);
  font-weight: 700;
  color: color-mix(in srgb, var(--color-primary-contrast) 90%, transparent);
  letter-spacing: var(--tracking-tight);
}
.stakent-feature-card__title {
  margin: 0 0 var(--spacing-xs);
  font-size: var(--text-lg);
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: var(--tracking-tight);
}
.stakent-feature-card__desc {
  margin: 0 0 var(--spacing-lg);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: var(--leading-normal);
  max-width: 36ch;
}
.stakent-feature-card__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-sm);
}
.stakent-feature-card__btn {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-xs);
  padding: var(--spacing-sm) var(--spacing-lg);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-weight: 600;
  text-decoration: none;
  transition: opacity var(--duration-fast) ease, transform var(--duration-fast) ease;
}
.stakent-feature-card__btn:hover {
  opacity: 0.95;
  transform: translateY(-1px);
}
.stakent-feature-card__btn:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.stakent-feature-card__btn--primary {
  background: var(--color-primary);
  color: var(--color-primary-contrast);
  border: 1px solid var(--color-primary);
}
.stakent-feature-card__btn--secondary {
  background: color-mix(in srgb, var(--color-bg-soft) 60%, transparent);
  color: var(--color-text);
  border: 1px solid var(--color-border);
}
.stakent-feature-card__btn-icon {
  font-size: var(--text-base);
}
</style>
