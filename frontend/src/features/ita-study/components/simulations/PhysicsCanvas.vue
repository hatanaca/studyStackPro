<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import type { SimulationSlider } from '../../types/study-content.types'

interface SimInfo {
  t: number
  x: number
  y: number
  v: number
  h: number
}

interface SimState {
  px: number
  py: number
  pivotX?: number
  pivotY?: number
  theta?: number
  info: SimInfo
}

const props = defineProps<{
  simulation: string
  params: Record<string, number>
  sliders: SimulationSlider[]
}>()

const canvasRef = ref<HTMLCanvasElement | null>(null)
const isRunning = ref(false)

let animationId: number | null = null
let ctx: CanvasRenderingContext2D | null = null
let simState: Record<string, number> = {}

const WIDTH = 800
const HEIGHT = 420

const simTitle = computed(() => {
  const titles: Record<string, string> = {
    projectile: 'Lançamento de Projétil',
    pendulum: 'Pêndulo Simples (MHS)',
    circuit: 'Circuito RC',
  }
  return titles[props.simulation] ?? 'Simulação'
})

const hasControls = computed(() => props.sliders.length > 0)

function step(t: number): SimState | null {
  switch (props.simulation) {
    case 'projectile': return stepProjectile(t)
    case 'pendulum': return stepPendulum(t)
    case 'circuit': return stepCircuit(t)
    default: return null
  }
}

function stepProjectile(t: number): SimState | null {
  const v0 = props.params.initialVelocity ?? 20
  const g = props.params.gravity ?? 9.8
  const deg = props.params.angle ?? 45

  const vx = v0 * Math.cos((deg * Math.PI) / 180)
  const vy = v0 * Math.sin((deg * Math.PI) / 180)

  const x = vx * t
  const y = vy * t - 0.5 * g * t * t

  const y0 = HEIGHT - 40
  const scale = 4
  const px = 60 + x * scale
  const py = y0 - y * scale

  if (y < 0) return null

  return {
    px,
    py,
    info: { t, x, y, v: Math.hypot(vx, vy - g * t), h: vy * vy / (2 * g) },
  }
}

function stepPendulum(t: number): SimState {
  const length = props.params.length ?? 1
  const g = props.params.gravity ?? 9.8
  const amplitude = ((props.params.amplitude ?? 30) * Math.PI) / 180

  const omega = Math.sqrt(g / length)
  const theta = amplitude * Math.cos(omega * t)
  const omegaNow = -amplitude * omega * Math.sin(omega * t)

  const pivotX = WIDTH / 2
  const pivotY = 40
  const len = 260
  const bobX = pivotX + len * Math.sin(theta)
  const bobY = pivotY + len * Math.cos(theta)

  return {
    px: bobX,
    py: bobY,
    theta,
    pivotX,
    pivotY,
    info: {
      t,
      x: (theta * 180) / Math.PI,
      y: omegaNow,
      v: len * Math.abs(omegaNow),
      h: length * (1 - Math.cos(theta)),
    },
  }
}

function stepCircuit(t: number): SimState {
  // Descarga de capacitor RC: V(t) = V0 * e^(-t/τ)
  const tau = props.params.tau ?? 1
  const v0 = props.params.voltage ?? 5
  const v = v0 * Math.exp(-t / tau)

  return {
    px: 0,
    py: 0,
    info: { t, x: v, y: v0 - v, v: 0, h: 0 },
  }
}

function draw(state: SimState, ctx2d: CanvasRenderingContext2D) {
  if (!state) return
  ctx2d.clearRect(0, 0, WIDTH, HEIGHT)

  const { info } = state

  if (props.simulation === 'projectile') {
    // Chão
    ctx2d.fillStyle = '#8B4513'
    ctx2d.fillRect(0, HEIGHT - 40, WIDTH, 40)
    ctx2d.fillStyle = '#3B82F6'
    ctx2d.beginPath()
    ctx2d.arc(state.px, state.py, 9, 0, Math.PI * 2)
    ctx2d.fill()
    ctx2d.fillStyle = '#1F2937'
    ctx2d.font = '13px sans-serif'
    ctx2d.fillText(`t = ${info.t.toFixed(2)}s`, 16, 24)
    ctx2d.fillText(`x = ${info.x.toFixed(1)} m`, 16, 42)
    ctx2d.fillText(`y = ${info.y.toFixed(1)} m`, 16, 60)
    ctx2d.fillText(`v = ${info.v.toFixed(1)} m/s`, 16, 78)
    ctx2d.fillText(`altura máx = ${info.h.toFixed(1)} m`, 16, 96)
  } else if (props.simulation === 'pendulum') {
    // Pêndulo
    ctx2d.strokeStyle = '#9CA3AF'
    ctx2d.lineWidth = 2
    ctx2d.beginPath()
    ctx2d.moveTo(state.pivotX ?? WIDTH / 2, state.pivotY ?? 40)
    ctx2d.lineTo(state.px, state.py)
    ctx2d.stroke()

    ctx2d.fillStyle = '#374151'
    ctx2d.beginPath()
    ctx2d.arc(state.pivotX ?? WIDTH / 2, state.pivotY ?? 40, 6, 0, Math.PI * 2)
    ctx2d.fill()

    ctx2d.fillStyle = '#EF4444'
    ctx2d.beginPath()
    ctx2d.arc(state.px, state.py, 14, 0, Math.PI * 2)
    ctx2d.fill()

    ctx2d.fillStyle = '#1F2937'
    ctx2d.font = '13px sans-serif'
    ctx2d.fillText(`t = ${info.t.toFixed(2)}s`, 16, 24)
    ctx2d.fillText(`θ = ${info.x.toFixed(1)}°`, 16, 42)
    ctx2d.fillText(`ω = ${info.y.toFixed(2)} rad/s`, 16, 60)
    ctx2d.fillText(`v = ${info.v.toFixed(2)} m/s`, 16, 78)
  } else if (props.simulation === 'circuit') {
    // Gráfico de descarga RC
    ctx2d.strokeStyle = '#D1D5DB'
    ctx2d.lineWidth = 1
    for (let i = 0; i <= 8; i++) {
      const x = 40 + (i * (WIDTH - 80)) / 8
      ctx2d.beginPath()
      ctx2d.moveTo(x, 20)
      ctx2d.lineTo(x, HEIGHT - 40)
      ctx2d.stroke()
    }
    ctx2d.strokeStyle = '#3B82F6'
    ctx2d.lineWidth = 3
    ctx2d.beginPath()
    for (let s = 0; s <= 200; s++) {
      const tt = (s / 200) * 5
      const v = (props.params.voltage ?? 5) * Math.exp(-tt / (props.params.tau ?? 1))
      const x = 40 + (s / 200) * (WIDTH - 80)
      const y = HEIGHT - 40 - (v / (props.params.voltage ?? 5)) * (HEIGHT - 80)
      if (s === 0) ctx2d.moveTo(x, y)
      else ctx2d.lineTo(x, y)
    }
    ctx2d.stroke()
    ctx2d.fillStyle = '#1F2937'
    ctx2d.font = '13px sans-serif'
    ctx2d.fillText(`V(t) = V₀·e^(-t/τ)`, 16, 24)
    ctx2d.fillText(`V = ${info.x.toFixed(2)} V (τ = ${(props.params.tau ?? 1).toFixed(1)}s)`, 16, 42)
  }
}

function animate() {
  if (!ctx) return

  const t = (simState.t ?? 0) + 0.02
  simState.t = t
  const state = step(t)

  if (state === null) {
    stopSimulation()
    return
  }
  simState = { ...simState, ...state.info }

  draw(state, ctx)
  animationId = requestAnimationFrame(animate)
}

function startSimulation() {
  const canvas = canvasRef.value
  if (!canvas) return
  const context = canvas.getContext('2d')
  if (!context) return
  ctx = context
  simState = { t: 0 }
  isRunning.value = true
  ctx.clearRect(0, 0, WIDTH, HEIGHT)
  animationId = requestAnimationFrame(animate)
}

function stopSimulation() {
  if (animationId) {
    cancelAnimationFrame(animationId)
    animationId = null
  }
  isRunning.value = false
}

function resetSimulation() {
  stopSimulation()
  if (ctx) {
    ctx.clearRect(0, 0, WIDTH, HEIGHT)
  }
  simState = { t: 0 }
}

watch(() => props.params, () => {
  if (!isRunning.value) return
  // Reinicia a simulação ao mudar parâmetros
  stopSimulation()
  startSimulation()
}, { deep: true })

onBeforeUnmount(() => {
  stopSimulation()
})
</script>

<template>
  <div class="physics-canvas">
    <div class="sim-header">
      <strong>{{ simTitle }}</strong>
      <div class="sim-actions">
        <Button
          :label="isRunning ? 'Pausar' : 'Iniciar'"
          size="small"
          :icon="isRunning ? 'pi pi-pause' : 'pi pi-play'"
          @click="isRunning ? stopSimulation() : startSimulation()"
        />
        <Button
          label="Resetar"
          size="small"
          icon="pi pi-refresh"
          text
          @click="resetSimulation"
        />
      </div>
    </div>

    <div class="canvas-wrap">
      <canvas ref="canvasRef" :width="WIDTH" :height="HEIGHT"></canvas>
    </div>

    <p v-if="!hasControls" class="sim-note">
      Use os controles abaixo para variar os parâmetros e observe o comportamento.
    </p>
  </div>
</template>

<style scoped>
.physics-canvas {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.sim-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  font-size: 0.9375rem;
}

.sim-actions {
  display: flex;
  gap: 0.5rem;
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
}

.sim-note {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--p-text-muted-color);
}
</style>
