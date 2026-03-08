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
        to="/sessions"
        class="stakent-feature-card__btn stakent-feature-card__btn--secondary"
      >
        Registrar sessão
      </router-link>
    </div>
  </article>
</template>

<style scoped>
.stakent-feature-card {
  position: relative;
  padding: var(--spacing-lg);
  border-radius: var(--radius-card, var(--radius-lg));
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.35), rgba(124, 58, 237, 0.2));
  border: 1px solid rgba(139, 92, 246, 0.4);
  box-shadow: var(--shadow-card), 0 0 60px rgba(139, 92, 246, 0.1);
  overflow: hidden;
}
.stakent-feature-card__badge {
  position: absolute;
  top: var(--spacing-sm);
  right: var(--spacing-sm);
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  background: var(--color-primary);
  color: #fff;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.stakent-feature-card__brand {
  margin-bottom: var(--spacing-sm);
}
.stakent-feature-card__logo {
  font-size: var(--text-xs);
  font-weight: 700;
  color: rgba(255, 255, 255, 0.9);
  letter-spacing: -0.02em;
}
.stakent-feature-card__title {
  margin: 0 0 var(--spacing-xs);
  font-size: var(--text-lg);
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: -0.02em;
}
.stakent-feature-card__desc {
  margin: 0 0 var(--spacing-md);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: 1.5;
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
  padding: 0.5rem 1rem;
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
.stakent-feature-card__btn--primary {
  background: var(--color-primary);
  color: #fff;
  border: 1px solid var(--color-primary);
}
.stakent-feature-card__btn--secondary {
  background: rgba(30, 41, 59, 0.6);
  color: var(--color-text);
  border: 1px solid var(--color-border);
}
.stakent-feature-card__btn-icon {
  font-size: 1rem;
}
</style>
