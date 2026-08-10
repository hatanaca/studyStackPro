<script setup lang="ts">
import { computed, ref } from 'vue'
import type { BiologyHotspot } from '../../types/study-content.types'

const props = defineProps<{
  hotspots: BiologyHotspot[]
}>()

const activeId = ref<string | null>(null)

const activeHotspot = computed(() =>
  props.hotspots.find((h) => h.id === activeId.value) ?? null
)

function select(hotspotId: string) {
  activeId.value = activeId.value === hotspotId ? null : hotspotId
}
</script>

<template>
  <div class="biology-svg">
    <div class="diagram-layout">
      <svg viewBox="0 0 480 360" class="cell-svg" role="img" aria-label="Diagrama de célula">
        <!-- Membrana plasmática -->
        <ellipse cx="240" cy="180" rx="220" ry="155" class="cell-membrane" />
        <ellipse cx="240" cy="180" rx="210" ry="145" class="cell-cytoplasm" />

        <!-- Núcleo -->
        <circle cx="145" cy="130" r="52" class="organelle nucleus" />
        <circle cx="145" cy="130" r="16" class="nucleolus" />

        <!-- Mitocôndria -->
        <ellipse cx="315" cy="120" rx="40" ry="20" class="organelle mitochondria" />
        <path d="M 280 118 q 15 6 35 0 M 280 122 q 15 6 35 0" class="mito-cristae" />

        <!-- Complexo de Golgi -->
        <g class="organelle golgi" transform="translate(320, 235)">
          <path d="M -35 -12 q 35 -16 70 0 q -17 12 -35 10 q -17 2 -35 -10 Z" />
          <path d="M -30 -4 q 30 -12 60 0 q -15 10 -30 8 q -15 -2 -30 -8 Z" />
        </g>

        <!-- Lisossomo -->
        <circle cx="178" cy="258" r="18" class="organelle lysosome" />

        <!-- Ribossomos -->
        <g class="organelle ribosomes">
          <circle cx="215" cy="72" r="4" />
          <circle cx="230" cy="62" r="4" />
          <circle cx="248" cy="75" r="4" />
          <circle cx="262" cy="60" r="4" />
        </g>

        <!-- Hotspots -->
        <g v-for="hotspot in hotspots" :key="hotspot.id" class="hotspot-group">
          <circle
            :cx="hotspot.x"
            :cy="hotspot.y"
            r="14"
            class="hotspot-ring"
            :class="{ active: activeId === hotspot.id }"
            @click="select(hotspot.id)"
          />
          <text
            :x="hotspot.x"
            :y="hotspot.y + 30"
            text-anchor="middle"
            class="hotspot-label"
          >{{ hotspot.label }}</text>
        </g>
      </svg>

      <transition name="slide">
        <div v-if="activeHotspot" class="hotspot-info">
          <h4>{{ activeHotspot.label }}</h4>
          <p>{{ activeHotspot.description }}</p>
        </div>
      </transition>
    </div>

    <p v-if="!activeHotspot" class="diagram-hint">
      <i class="pi pi-hand-pointer"></i>
      Clique nos pontos numerados para conhecer cada organela.
    </p>
  </div>
</template>

<style scoped>
.biology-svg {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.diagram-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  position: relative;
}

@media (min-width: 720px) {
  .diagram-layout {
    grid-template-columns: 1fr 280px;
    align-items: start;
  }
}

.cell-svg {
  width: 100%;
  height: auto;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  background: var(--p-surface-0);
}

.cell-membrane {
  fill: rgba(59, 130, 246, 0.12);
  stroke: #3B82F6;
  stroke-width: 3;
}

.cell-cytoplasm {
  fill: rgba(147, 197, 253, 0.25);
  stroke: none;
}

.organelle {
  stroke-width: 2;
}
.nucleus {
  fill: rgba(139, 92, 246, 0.25);
  stroke: #8B5CF6;
}
.nucleolus {
  fill: rgba(139, 92, 246, 0.55);
  stroke: none;
}
.mitochondria {
  fill: rgba(16, 185, 129, 0.25);
  stroke: #10B981;
}
.mito-cristae {
  fill: none;
  stroke: #10B981;
  stroke-width: 1.5;
}
.golgi path {
  fill: rgba(245, 158, 11, 0.3);
  stroke: #F59E0B;
  stroke-width: 1.5;
}
.lysosome {
  fill: rgba(239, 68, 68, 0.3);
  stroke: #EF4444;
}
.ribosomes circle {
  fill: #6366F1;
}

.hotspot-ring {
  fill: rgba(59, 130, 246, 0.35);
  stroke: #2563EB;
  stroke-width: 2.5;
  cursor: pointer;
  transition: all 0.2s;
}
.hotspot-ring:hover,
.hotspot-ring.active {
  fill: rgba(59, 130, 246, 0.7);
  stroke-width: 3.5;
}

.hotspot-label {
  font-size: 11px;
  fill: var(--p-text-muted-color);
  pointer-events: none;
  font-weight: 600;
}

.hotspot-info {
  padding: 1rem;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  background: var(--p-surface-50);
  position: sticky;
  top: 1rem;
}
.hotspot-info h4 {
  margin: 0 0 0.5rem;
  color: var(--p-primary-color);
  font-size: 1rem;
}
.hotspot-info p {
  margin: 0;
  font-size: 0.875rem;
  color: var(--p-text-color);
  line-height: 1.6;
}

.diagram-hint {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: var(--p-text-muted-color);
}

.slide-enter-active,
.slide-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.slide-enter-from,
.slide-leave-to {
  opacity: 0;
  transform: translateY(6px);
}
</style>
