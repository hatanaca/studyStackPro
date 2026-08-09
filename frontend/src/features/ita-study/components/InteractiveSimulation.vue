<script setup lang="ts">
import { computed, onMounted, reactive } from 'vue'
import type { SimulationConfig } from '../types/study-content.types'
import FunctionExplorer from './simulations/FunctionExplorer.vue'
import PhysicsCanvas from './simulations/PhysicsCanvas.vue'
import BiologySvgDiagram from './simulations/BiologySvgDiagram.vue'
import GeometryExplorer from './simulations/GeometryExplorer.vue'

const props = defineProps<{
  config: SimulationConfig
}>()

const params = reactive<Record<string, number>>({})

onMounted(() => {
  for (const slider of props.config.sliders ?? []) {
    params[slider.name] = slider.default
  }
})

const isFunctionPlot = computed(() => props.config.type === 'function_plot')
const isPhysics = computed(() => props.config.type === 'physics_sim')
const isBiology = computed(() => props.config.type === 'biology_svg')
const isGeometry = computed(() => props.config.type === 'geometry')
</script>

<template>
  <div class="interactive-simulation">
    <FunctionExplorer
      v-if="isFunctionPlot"
      :config="config"
      :params="params"
    />

    <PhysicsCanvas
      v-else-if="isPhysics"
      :simulation="config.simulation ?? 'projectile'"
      :params="params"
      :sliders="config.sliders ?? []"
    />

    <BiologySvgDiagram
      v-else-if="isBiology"
      :hotspots="config.hotspots ?? []"
    />

    <GeometryExplorer
      v-else-if="isGeometry"
      :shape="config.shape ?? 'triangle'"
      :interactive="config.interactive !== false"
      :measurements="config.measurements ?? ['angles']"
    />

    <div v-else class="sim-empty">
      <i class="pi pi-wave-pulse"></i>
      <span>Este tópico ainda não possui exploração interativa.</span>
    </div>

    <div v-if="config.sliders?.length" class="simulation-controls">
      <div
        v-for="slider in config.sliders"
        :key="slider.name"
        class="slider-group"
      >
        <div class="slider-label">
          <span>{{ slider.label }}</span>
          <strong>{{ Number(params[slider.name] ?? slider.default).toFixed(slider.step && slider.step < 1 ? 1 : 0) }}</strong>
        </div>
        <Slider
          v-model="params[slider.name]"
          :min="slider.min"
          :max="slider.max"
          :step="slider.step ?? 1"
          class="slider-input"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.interactive-simulation {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.sim-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 3rem;
  color: var(--p-text-muted-color);
  border: 1px dashed var(--p-border-color);
  border-radius: 0.5rem;
}

.simulation-controls {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  padding: 1rem;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  background: var(--p-surface-50);
}

.slider-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.slider-label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.8125rem;
}

.slider-label strong {
  font-size: 0.875rem;
}

.slider-input {
  width: 100%;
}
</style>
