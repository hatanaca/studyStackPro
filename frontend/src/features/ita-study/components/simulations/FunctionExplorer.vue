<script setup lang="ts">
import { computed, ref } from 'vue'
import GraphPlot from '@/components/math/GraphPlot.vue'
import type { SimulationConfig } from '../../types/study-content.types'

const props = defineProps<{
  config: SimulationConfig
  params: Record<string, number>
}>()

const SAFE_FN = /^[0-9xXa-zA-Z+\-*/().\s^,]+$/

/** Substitui os parâmetros (sliders) nas expressões das funções. */
function resolveFunctions(fns: string[]): string[] {
  return fns.map((fn) => {
    let out = fn
    for (const [key, value] of Object.entries(props.params)) {
      out = out.replace(new RegExp(`\\b${key}\\b`, 'g'), String(value))
    }
    return out
  })
}

const plotFunctions = computed(() => resolveFunctions(props.config.functions ?? ['x^2']))
const plotError = ref<string | null>(null)

function onPlotError(fn: string) {
  plotError.value = `Não foi possível desenhar: ${fn}`
}

const xDomain = computed<[number, number]>(() => props.config.xDomain ?? [-10, 10])
const yDomain = computed<[number, number]>(() => props.config.yDomain ?? [-10, 10])

/** Avalia uma expressão numérica simples para a tabela de valores. */
function evaluateExpression(fn: string, x: number): number | null {
  const substituted = resolveFunctions([fn])[0]
  if (!SAFE_FN.test(substituted)) return null
  const js = substituted
    .replace(/\^/g, '**')
    .replace(/\b(sin|cos|tan|sqrt|abs|log)\b/g, 'Math.$1')
  try {
    const value = new Function('x', `return (${js})`)(
      typeof x === 'number' ? x : parseFloat(x)
    )
    return typeof value === 'number' && Number.isFinite(value) ? Number(value.toFixed(3)) : null
  } catch {
    return null
  }
}

const samplePoints = computed(() => {
  const [min, max] = xDomain.value
  const step = (max - min) / 10
  return Array.from({ length: 11 }, (_, i) => Number((min + i * step).toFixed(3)))
})
</script>

<template>
  <div class="function-explorer">
    <div v-if="plotError" class="plot-error">
      <i class="pi pi-exclamation-triangle"></i>
      <span>{{ plotError }}</span>
    </div>

    <GraphPlot
      :fns="plotFunctions"
      :x-domain="xDomain"
      :y-domain="yDomain"
      :height="340"
      @error="onPlotError"
    />

    <div v-if="config.functions?.length" class="values-table-wrap">
      <table class="values-table">
        <thead>
          <tr>
            <th>x</th>
            <th v-for="(fn, i) in config.functions" :key="i">{{ fn }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="x in samplePoints" :key="x">
            <td>{{ x }}</td>
            <td v-for="(fn, i) in config.functions" :key="i">
              {{ evaluateExpression(fn, x) ?? '—' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.function-explorer {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.plot-error {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: var(--p-red-50);
  color: var(--p-red-700);
  border-radius: 0.5rem;
  font-size: 0.875rem;
}

.values-table-wrap {
  overflow-x: auto;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
}

.values-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8125rem;
}

.values-table th,
.values-table td {
  padding: 0.5rem 0.75rem;
  border-bottom: 1px solid var(--p-border-color);
  text-align: center;
}

.values-table th {
  background: var(--p-surface-100);
  font-weight: 600;
}

.values-table tbody tr:last-child td {
  border-bottom: none;
}
</style>
