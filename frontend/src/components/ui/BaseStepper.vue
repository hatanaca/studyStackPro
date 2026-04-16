<script setup lang="ts">
import { computed } from 'vue'

export interface StepperStep {
  id: string
  label: string
  description?: string
  optional?: boolean
}

const props = withDefaults(
  defineProps<{
    steps: StepperStep[]
    currentStepId: string
    /** Permitir navegação por clique em steps anteriores */
    allowStepClick?: boolean
    orientation?: 'horizontal' | 'vertical'
  }>(),
  { allowStepClick: false, orientation: 'horizontal' }
)

const emit = defineEmits<{
  'step-click': [step: StepperStep]
}>()

const currentIndex = computed(() => props.steps.findIndex((s) => s.id === props.currentStepId))

function stepStatus(stepIndex: number): 'completed' | 'current' | 'upcoming' {
  if (stepIndex < currentIndex.value) return 'completed'
  if (stepIndex === currentIndex.value) return 'current'
  return 'upcoming'
}

function onStepClick(step: StepperStep, index: number) {
  if (!props.allowStepClick) return
  if (index > currentIndex.value) return
  emit('step-click', step)
}
</script>

<template>
  <nav class="base-stepper" :class="[`base-stepper--${orientation}`]" aria-label="Progresso">
    <ol class="base-stepper__list">
      <li
        v-for="(step, index) in steps"
        :key="step.id"
        class="base-stepper__item"
        :class="[
          `base-stepper__item--${stepStatus(index)}`,
          { 'base-stepper__item--clickable': allowStepClick && index <= currentIndex },
        ]"
        @click="onStepClick(step, index)"
      >
        <div class="base-stepper__indicator">
          <span
            v-if="stepStatus(index) === 'completed'"
            class="base-stepper__check"
            aria-hidden="true"
          >
            ✓
          </span>
          <span
            v-else
            class="base-stepper__number"
            :aria-current="stepStatus(index) === 'current' ? 'step' : undefined"
          >
            {{ index + 1 }}
          </span>
        </div>
        <div class="base-stepper__content">
          <span class="base-stepper__label">{{ step.label }}</span>
          <span v-if="step.description" class="base-stepper__desc">{{ step.description }}</span>
        </div>
        <div
          v-if="orientation === 'horizontal' && index < steps.length - 1"
          class="base-stepper__connector"
          aria-hidden="true"
        />
      </li>
    </ol>
  </nav>
</template>

<style scoped>
.base-stepper {
  width: 100%;
}
.base-stepper__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 0;
}
.base-stepper--vertical .base-stepper__list {
  flex-direction: column;
  gap: 0;
}
.base-stepper__item {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-sm);
  position: relative;
  flex: 1;
  min-width: 0;
}
.base-stepper--vertical .base-stepper__item {
  flex: none;
  width: 100%;
}
.base-stepper__item--clickable {
  cursor: pointer;
}
.base-stepper__item--clickable:hover .base-stepper__label {
  color: var(--color-primary);
}
.base-stepper__indicator {
  flex-shrink: 0;
  width: var(--icon-size-lg);
  height: var(--icon-size-lg);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: var(--text-sm);
  font-weight: 600;
  background: var(--color-bg-soft);
  color: var(--color-text-muted);
  border: 2px solid var(--color-border);
  transition:
    background var(--duration-fast) ease,
    border-color var(--duration-fast) ease,
    color var(--duration-fast) ease;
}
.base-stepper__item--current .base-stepper__indicator {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-primary-contrast);
}
.base-stepper__item--completed .base-stepper__indicator {
  background: var(--color-success);
  border-color: var(--color-success);
  color: var(--color-primary-contrast);
}
.base-stepper__check {
  font-size: var(--text-sm);
  line-height: 1;
}
.base-stepper__number {
  line-height: 1;
}
.base-stepper__content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2xs);
  min-width: 0;
  padding-bottom: var(--spacing-xl);
}
.base-stepper--vertical .base-stepper__content {
  padding-bottom: var(--spacing-lg);
}
.base-stepper__item:last-child .base-stepper__content {
  padding-bottom: 0;
}
.base-stepper__label {
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-text);
}
.base-stepper__item--upcoming .base-stepper__label {
  color: var(--color-text-muted);
}
.base-stepper__desc {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
.base-stepper__connector {
  position: absolute;
  top: var(--spacing-lg);
  left: calc(var(--spacing-lg) + var(--spacing-sm) + var(--spacing-xs));
  right: calc(-1 * var(--spacing-xs));
  height: 2px;
  background: var(--color-border);
}
.base-stepper__item--completed + .base-stepper__item .base-stepper__connector,
.base-stepper__item--completed .base-stepper__connector {
  background: var(--color-success);
}
</style>
