<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Button from 'primevue/button'

const emit = defineEmits<{
  dismiss: []
}>()

const visible = ref(false)
const STORAGE_KEY = 'studytrack.onboarding.dismissed'

onMounted(() => {
  try {
    visible.value = !localStorage.getItem(STORAGE_KEY)
  } catch {
    visible.value = true
  }
})

function dismiss() {
  visible.value = false
  try {
    localStorage.setItem(STORAGE_KEY, '1')
  } catch {
    // ignore
  }
  emit('dismiss')
}
</script>

<template>
  <Transition name="slide">
    <div
      v-if="visible"
      class="onboarding-banner"
      role="banner"
    >
      <div class="onboarding-banner__content">
        <span class="onboarding-banner__icon">👋</span>
        <div class="onboarding-banner__text">
          <strong>Bem-vindo ao StudyTrack Pro.</strong>
          Registre suas sessões de estudo acima e acompanhe seu progresso no dashboard.
        </div>
        <Button
          label="Entendi"
          variant="text"
          size="small"
          severity="secondary"
          class="onboarding-banner__dismiss"
          @click="dismiss"
        />
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.onboarding-banner {
  margin-bottom: var(--widget-gap);
  padding: var(--widget-padding-sm) var(--widget-padding);
  background: var(--color-primary-soft);
  border: 1px solid color-mix(in srgb, var(--color-primary) 45%, transparent);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}
.onboarding-banner__content {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  flex-wrap: wrap;
}
.onboarding-banner__icon {
  font-size: var(--text-2xl);
  line-height: var(--leading-tight);
  flex-shrink: 0;
}
.onboarding-banner__text {
  flex: 1;
  min-width: 0;
  font-size: var(--text-sm);
  color: var(--color-text);
  line-height: var(--leading-normal);
  letter-spacing: var(--tracking-tight);
}
.onboarding-banner__text strong {
  font-weight: 600;
}
.onboarding-banner__dismiss {
  flex-shrink: 0;
  min-height: 2.75rem;
}
.onboarding-banner__dismiss:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}
</style>
