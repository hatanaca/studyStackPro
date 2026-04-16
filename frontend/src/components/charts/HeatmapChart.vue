<script setup lang="ts">
defineProps<{
  data?: { date: string; total_minutes: number }[]
  title?: string
}>()
</script>

<template>
  <div class="heatmap-chart">
    <h3 v-if="title" class="chart-title">
      {{ title }}
    </h3>
    <div class="chart-placeholder">
      <template v-if="data?.length"> Calendário de atividade (estilo GitHub) </template>
      <template v-else> Sem dados </template>
    </div>
  </div>
</template>

<style scoped>
.heatmap-chart {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-sm);
}
.chart-title {
  font-size: var(--widget-title-size);
  font-weight: var(--widget-title-weight);
  margin-bottom: var(--spacing-sm);
  color: var(--widget-title-color);
}
.chart-placeholder {
  min-height: var(--widget-chart-min-height-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-muted);
  font-size: var(--text-sm);
}
</style>
