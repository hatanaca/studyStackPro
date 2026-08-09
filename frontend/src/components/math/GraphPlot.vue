<script setup lang="ts">
/**
 * Gráfico de funções 2D via function-plot (d3). Re-renderiza ao mudar as
 * expressões ou o domínio. Em caso de expressão inválida, emite `error`.
 */
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import functionPlot from 'function-plot'

const props = withDefaults(
  defineProps<{
    fns: string[] | string
    xDomain?: [number, number]
    yDomain?: [number, number]
    height?: number
  }>(),
  {
    xDomain: () => [-10, 10],
    yDomain: () => [-10, 10],
    height: 320,
  }
)

const emit = defineEmits<{ (e: 'error', fn: string): void }>()

const container = ref<HTMLElement | null>(null)

const COLORS = ['var(--color-primary)', 'var(--color-accent)', 'var(--color-success)', 'var(--color-warning)']

function render() {
  if (!container.value) return
  container.value.innerHTML = ''

  const fns = (Array.isArray(props.fns) ? props.fns : [props.fns])
    .map((fn) => fn.trim())
    .filter(Boolean)

  try {
    functionPlot({
      target: container.value,
      width: container.value.clientWidth || 560,
      height: props.height,
      xAxis: { domain: props.xDomain, label: 'x' },
      yAxis: { domain: props.yDomain, label: 'y' },
      grid: true,
      tip: { xLine: true, yLine: true },
      data: fns.map((fn, i) => ({ fn, color: COLORS[i % COLORS.length] })),
    })
  } catch {
    emit('error', fns.join(', '))
  }
}

onMounted(render)
watch(() => [props.fns, props.xDomain, props.yDomain], render, { deep: true })
onBeforeUnmount(() => {
  if (container.value) container.value.innerHTML = ''
})
</script>

<template>
  <div ref="container" class="graph-plot" :style="{ minHeight: `${height}px` }" />
</template>

<style scoped>
.graph-plot {
  width: 100%;
  overflow-x: auto;
}
.graph-plot :deep(svg) {
  display: block;
  margin: 0 auto;
}
</style>
