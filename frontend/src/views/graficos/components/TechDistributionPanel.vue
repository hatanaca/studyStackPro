<script setup lang="ts">
import { ref } from 'vue'
import ChartSkeleton from '@/components/ui/ChartSkeleton.vue'
import DonutChart from '@/components/charts/DonutChart.vue'
import TreemapChart from '@/components/charts/TreemapChart.vue'
import ChartPanel from './ChartPanel.vue'
import type { TreemapDataPoint } from '@/types/chart.types'

defineProps<{
  data: { series: number[]; labels: string[]; colors: string[] }
  treemapData: TreemapDataPoint[]
  loading?: boolean
}>()

const viewMode = ref<'donut' | 'treemap'>('donut')
</script>

<template>
  <ChartPanel title="Distribuição por tecnologia" :loading="loading">
    <template #actions>
      <div class="toggle">
        <button
          class="toggle__btn"
          :class="{ 'toggle__btn--active': viewMode === 'donut' }"
          @click="viewMode = 'donut'"
        >Donut</button>
        <button
          class="toggle__btn"
          :class="{ 'toggle__btn--active': viewMode === 'treemap' }"
          @click="viewMode = 'treemap'"
        >Treemap</button>
      </div>
    </template>
    <ChartSkeleton v-if="loading" height="280px" />
    <template v-else>
      <DonutChart
        v-if="viewMode === 'donut'"
        :series="data.series"
        :labels="data.labels"
        :colors="data.colors"
      />
      <TreemapChart
        v-else
        :series="treemapData"
        :colors="data.colors"
      />
    </template>
  </ChartPanel>
</template>

<style scoped>
.toggle {
  display: flex;
  gap: 4px;
  background: rgba(0, 0, 0, 0.4);
  border-radius: var(--radius-lg);
  padding: 3px;
  border: 1px solid rgba(255, 255, 255, 0.03);
}
.toggle__btn {
  padding: 4px 12px;
  border: none;
  border-radius: var(--radius-md);
  background: transparent;
  color: var(--color-text-muted);
  font-size: var(--text-2xs);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}
.toggle__btn:hover {
  color: var(--color-text);
  background: rgba(255, 255, 255, 0.05);
}
.toggle__btn--active {
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
  color: var(--color-text);
  box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
}
</style>
