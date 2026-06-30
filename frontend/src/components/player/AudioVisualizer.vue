<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps<{ isPlaying: boolean }>()

const canvasRef = ref<HTMLCanvasElement | null>(null)
let ctx: CanvasRenderingContext2D | null = null
let animId = 0
const BARS = 5
const barValues = new Array(BARS).fill(0)
const barTargets = new Array(BARS).fill(0)
const barSpeeds = new Array(BARS).fill(0)

function randomizeTargets() {
  for (let i = 0; i < BARS; i++) {
    barTargets[i] = 0.15 + Math.random() * 0.85
    barSpeeds[i] = 0.08 + Math.random() * 0.12
  }
}

function animate() {
  if (!ctx || !canvasRef.value) return
  const w = canvasRef.value.width
  const h = canvasRef.value.height
  ctx.clearRect(0, 0, w, h)

  const gap = 2
  const barW = (w - gap * (BARS - 1)) / BARS

  for (let i = 0; i < BARS; i++) {
    if (props.isPlaying) {
      barValues[i] += (barTargets[i] - barValues[i]) * barSpeeds[i]
      if (Math.abs(barValues[i] - barTargets[i]) < 0.02) {
        barTargets[i] = 0.15 + Math.random() * 0.85
        barSpeeds[i] = 0.08 + Math.random() * 0.12
      }
    } else {
      barValues[i] += (0.08 - barValues[i]) * 0.06
    }

    const barH = Math.max(2, barValues[i] * h)
    const x = i * (barW + gap)
    const y = h - barH

    const grad = ctx.createLinearGradient(x, y, x, h)
    grad.addColorStop(0, '#c084fc')
    grad.addColorStop(0.5, '#818cf8')
    grad.addColorStop(1, '#6366f1')
    ctx.fillStyle = grad
    ctx.beginPath()
    ctx.roundRect(x, y, barW, barH, 1.5)
    ctx.fill()
  }

  animId = requestAnimationFrame(animate)
}

onMounted(() => {
  const canvas = canvasRef.value
  if (!canvas) return
  ctx = canvas.getContext('2d')
  randomizeTargets()
  animate()
})

onUnmounted(() => cancelAnimationFrame(animId))

watch(() => props.isPlaying, (v) => { if (v) randomizeTargets() })
</script>

<template>
  <canvas ref="canvasRef" class="audio-visualizer" width="40" height="18" @mousedown.stop @touchstart.stop />
</template>

<style scoped>
.audio-visualizer {
  flex-shrink: 0;
  width: 40px;
  height: 18px;
  cursor: default;
}
</style>
