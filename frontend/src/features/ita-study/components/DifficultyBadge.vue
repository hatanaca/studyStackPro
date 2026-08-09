<script setup lang="ts">
import { computed } from 'vue'
import type { DifficultyLevel } from '../types/ita-study.types'
import { DIFFICULTY_LABELS, DIFFICULTY_COLORS } from '../types/ita-study.types'

const props = defineProps<{
  level: DifficultyLevel
}>()

const label = computed(() => DIFFICULTY_LABELS[props.level])
const color = computed(() => DIFFICULTY_COLORS[props.level])
</script>

<template>
  <span
    class="difficulty-badge"
    :style="{ backgroundColor: color + '20', color: color, borderColor: color + '40' }"
  >
    {{ label }}
  </span>
</template>

<style scoped>
.difficulty-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.625rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border: 1px solid;
}
</style>
