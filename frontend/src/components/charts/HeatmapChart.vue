<script setup lang="ts">
defineProps<{
  data?: { date: string; total_minutes: number }[]
  title?: string
}>()
</script>

<template>
  <div class="heatmap-chart">
    <h3
      v-if="title"
      class="chart-title"
    >
      {{ title }}
    </h3>
    <div class="chart-placeholder">
      <template v-if="data?.length">
        Calendário de atividade (estilo GitHub)
      </template>
      <template v-else>
        Sem dados
      </template>
    </div>
  </div>
</template>

<style scoped>
.heatmap-chart {
  background: #fff;
  border-radius: 0.5rem;
  padding: 1rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.chart-title {
  font-size: 0.875rem;
  margin-bottom: 0.75rem;
  color: #64748b;
}
.chart-placeholder {
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  font-size: 0.875rem;
}
</style>
