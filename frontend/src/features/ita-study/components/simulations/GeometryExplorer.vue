<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

interface Point {
  x: number
  y: number
}

const props = withDefaults(
  defineProps<{
    shape: string
    interactive?: boolean
    measurements?: string[]
  }>(),
  { interactive: true, measurements: () => ['angles', 'sides'] }
)

const canvasRef = ref<HTMLCanvasElement | null>(null)
const vertices = ref<Point[]>([])

const WIDTH = 600
const HEIGHT = 380

const shapeLabel = computed(() => {
  const labels: Record<string, string> = {
    triangle: 'Triângulo',
    quadrilateral: 'Quadrilátero',
  }
  return labels[props.shape] ?? props.shape
})

onMounted(() => {
  initializeShape()
  if (props.interactive) enableDragging()
})

onBeforeUnmount(() => {
  removeListeners()
})

function initializeShape() {
  switch (props.shape) {
    case 'triangle':
      vertices.value = [
        { x: 300, y: 60 },
        { x: 150, y: 330 },
        { x: 450, y: 330 },
      ]
      break
    case 'quadrilateral':
      vertices.value = [
        { x: 200, y: 90 },
        { x: 400, y: 90 },
        { x: 400, y: 290 },
        { x: 200, y: 290 },
      ]
      break
    default:
      vertices.value = [
        { x: 200, y: 100 },
        { x: 400, y: 100 },
        { x: 400, y: 280 },
        { x: 200, y: 280 },
      ]
  }
  draw()
}

function draw() {
  const canvas = canvasRef.value
  const ctx = canvas?.getContext('2d')
  if (!ctx) return

  ctx.clearRect(0, 0, WIDTH, HEIGHT)

  const pts = vertices.value
  if (pts.length < 3) return

  // Preenche o polígono
  ctx.fillStyle = 'rgba(59, 130, 246, 0.15)'
  ctx.beginPath()
  pts.forEach((p, i) => {
    if (i === 0) ctx.moveTo(p.x, p.y)
    else ctx.lineTo(p.x, p.y)
  })
  ctx.closePath()
  ctx.fill()

  // Borda
  ctx.strokeStyle = '#3B82F6'
  ctx.lineWidth = 2
  ctx.stroke()

  // Desenha os ângulos internos
  if (props.measurements.includes('angles')) {
    const angles = computedAngles.value
    pts.forEach((p, i) => {
      const prev = pts[(i - 1 + pts.length) % pts.length]
      const next = pts[(i + 1) % pts.length]
      const a1 = Math.atan2(prev.y - p.y, prev.x - p.x)
      const a2 = Math.atan2(next.y - p.y, next.x - p.x)
      ctx.strokeStyle = '#F59E0B'
      ctx.lineWidth = 1.5
      ctx.beginPath()
      ctx.arc(p.x, p.y, 18, Math.min(a1, a2), Math.max(a1, a2))
      ctx.stroke()
      ctx.fillStyle = '#B45309'
      ctx.font = '11px sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText(`${angles[i]?.toFixed(0)}°`, p.x + 26, p.y - 10)
    })
  }

  // Vértices arrastáveis
  pts.forEach((p, i) => {
    ctx.fillStyle = '#2563EB'
    ctx.beginPath()
    ctx.arc(p.x, p.y, 7, 0, Math.PI * 2)
    ctx.fill()
    ctx.strokeStyle = '#fff'
    ctx.lineWidth = 2
    ctx.stroke()
    ctx.fillStyle = '#111827'
    ctx.font = 'bold 12px sans-serif'
    ctx.fillText(String.fromCharCode(65 + i), p.x - 10, p.y - 12)
  })
}

let dragIndex: number | null = null

function getMousePos(e: MouseEvent): Point {
  const canvas = canvasRef.value!
  const rect = canvas.getBoundingClientRect()
  const scaleX = WIDTH / rect.width
  const scaleY = HEIGHT / rect.height
  return {
    x: (e.clientX - rect.left) * scaleX,
    y: (e.clientY - rect.top) * scaleY,
  }
}

function onMouseDown(e: MouseEvent) {
  const pos = getMousePos(e)
  dragIndex = vertices.value.findIndex((v) => Math.hypot(v.x - pos.x, v.y - pos.y) < 16)
}

function onMouseMove(e: MouseEvent) {
  if (dragIndex === null) return
  vertices.value[dragIndex] = getMousePos(e)
  draw()
}

function onMouseUp() {
  dragIndex = null
}

function enableDragging() {
  const canvas = canvasRef.value
  canvas?.addEventListener('mousedown', onMouseDown)
  window.addEventListener('mousemove', onMouseMove)
  window.addEventListener('mouseup', onMouseUp)
}

function removeListeners() {
  const canvas = canvasRef.value
  canvas?.removeEventListener('mousedown', onMouseDown)
  window.removeEventListener('mousemove', onMouseMove)
  window.removeEventListener('mouseup', onMouseUp)
}

const computedAngles = computed(() => {
  const pts = vertices.value
  const n = pts.length
  if (n < 3) return []
  const angles: number[] = []
  for (let i = 0; i < n; i++) {
    const prev = pts[(i - 1 + n) % n]
    const curr = pts[i]
    const next = pts[(i + 1) % n]
    const v1 = { x: prev.x - curr.x, y: prev.y - curr.y }
    const v2 = { x: next.x - curr.x, y: next.y - curr.y }
    const dot = v1.x * v2.x + v1.y * v2.y
    const cross = v1.x * v2.y - v1.y * v2.x
    angles.push(Number((Math.abs(Math.atan2(cross, dot)) * 180 / Math.PI).toFixed(1)))
  }
  return angles
})

const computedSides = computed(() => {
  const pts = vertices.value
  const n = pts.length
  if (n < 2) return []
  const sides: number[] = []
  for (let i = 0; i < n; i++) {
    const a = pts[i]
    const b = pts[(i + 1) % n]
    sides.push(Number(Math.hypot(b.x - a.x, b.y - a.y).toFixed(1)))
  }
  return sides
})

const perimeter = computed(() => {
  if (props.measurements.includes('sides')) {
    return computedSides.value.reduce((acc, s) => acc + s, 0).toFixed(1)
  }
  return null
})
</script>

<template>
  <div class="geometry-explorer">
    <div class="geom-header">
      <strong>{{ shapeLabel }}</strong>
      <span v-if="interactive" class="drag-hint">
        <i class="pi pi-arrows-alt"></i>
        Arraste os vértices
      </span>
    </div>

    <div class="canvas-wrap">
      <canvas ref="canvasRef" :width="WIDTH" :height="HEIGHT"></canvas>
    </div>

    <div class="geom-info">
      <div v-if="measurements.includes('angles')" class="geom-metric">
        <strong>Ângulos internos:</strong>
        <span>{{ computedAngles.join('°, ') }}°</span>
        <span class="metric-check">Soma: {{ computedAngles.reduce((a, b) => a + b, 0).toFixed(1) }}°</span>
      </div>
      <div v-if="measurements.includes('sides')" class="geom-metric">
        <strong>Lados:</strong>
        <span>{{ computedSides.join(', ') }}</span>
        <span class="metric-check">Perímetro: {{ perimeter }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.geometry-explorer {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.geom-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  font-size: 0.9375rem;
}

.drag-hint {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.8125rem;
  color: var(--p-text-muted-color);
}

.canvas-wrap {
  width: 100%;
  overflow-x: auto;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  background: var(--p-surface-0);
}

.canvas-wrap canvas {
  display: block;
  max-width: none;
  cursor: grab;
}
.canvas-wrap canvas:active {
  cursor: grabbing;
}

.geom-info {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 0.75rem 1rem;
  border: 1px solid var(--p-border-color);
  border-radius: 0.5rem;
  background: var(--p-surface-50);
  font-size: 0.875rem;
}

.geom-metric {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.metric-check {
  color: var(--p-primary-color);
  font-weight: 600;
}
</style>
