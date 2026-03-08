<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import SkeletonLoader from '@/components/ui/SkeletonLoader.vue'
import { useApexChartTheme } from '@/composables/useApexChartTheme'
import { truncate } from '@/utils/formatters'
import type { TechnologyMetric } from '@/types/domain.types'
import type { ApexOptions } from 'apexcharts'

/** Fatia do gráfico com dados para cards de informação */
export interface ChartSlice {
  label: string
  value: number
  color: string
  info1: { label: string; value: string }
  info2: { label: string; value: string }
}

const props = defineProps<{
  metrics?: TechnologyMetric[]
  loading?: boolean
}>()

const router = useRouter()
const showTechList = ref(true)
const currentType = ref<'pie' | 'doughnut' | 'bar'>('pie')

/** Índice da fatia selecionada (null = nenhuma) */
const selectedSlice = ref<number | null>(null)
/** Controla sequência de animação: explode → linhas → cards */
const phase = ref<'idle' | 'explode' | 'lines' | 'cards'>('idle')
/** Classe para animar a fatia explodida após mount */
const explodedAnimate = ref(false)
/** Linhas e cards visíveis (para animação de stroke-dashoffset e fade) */
const line1Visible = ref(false)
const line2Visible = ref(false)
const card1Visible = ref(false)
const card2Visible = ref(false)

const { theme: chartTheme } = useApexChartTheme()
/** Paleta do design system quando tecnologia não tem cor; com muitas fatias usa só 6 tons para evitar arco-íris */
const FALLBACK_COLORS = computed(() => {
  const p = chartTheme.value.palette
  return p.length > 6 ? p.slice(0, 6) : p
})

/** Dados fictícios quando não há metrics (pelo menos 5 fatias); cores do tema */
const MOCK_SLICES = computed<ChartSlice[]>(() => {
  const p = chartTheme.value.palette
  return [
    { label: 'JavaScript', value: 120, color: p[0], info1: { label: 'Horas totais', value: '120h' }, info2: { label: 'Sessões', value: '45' } },
    { label: 'Laravel', value: 85, color: p[1], info1: { label: 'Horas totais', value: '85h' }, info2: { label: 'Sessões', value: '32' } },
    { label: 'Vue.js', value: 72, color: p[2], info1: { label: 'Horas totais', value: '72h' }, info2: { label: 'Sessões', value: '28' } },
    { label: 'PostgreSQL', value: 48, color: p[3], info1: { label: 'Horas totais', value: '48h' }, info2: { label: 'Sessões', value: '18' } },
    { label: 'Docker', value: 35, color: p[4], info1: { label: 'Horas totais', value: '35h' }, info2: { label: 'Sessões', value: '12' } },
  ]
})

function toHours(minutes: number): number {
  const n = Number(minutes)
  if (!Number.isFinite(n) || n < 0) return 0
  return Math.round((n / 60) * 10) / 10
}

/** Converte metrics da API em ChartSlice[]; se vazio, usa mock */
const slices = computed<ChartSlice[]>(() => {
  const list = [...(props.metrics ?? [])].sort((a, b) => (b.total_minutes ?? 0) - (a.total_minutes ?? 0))
  const fallback = FALLBACK_COLORS.value
  if (!list.length) return MOCK_SLICES.value
  return list.map((m, i) => ({
    label: m.technology?.name ?? 'Sem nome',
    value: toHours(m.total_minutes ?? 0),
    color: m.technology?.color || fallback[i % fallback.length],
    info1: { label: 'Sessões', value: String(m.session_count ?? 0) },
    info2: { label: 'Última vez', value: m.last_studied_at ? new Date(m.last_studied_at).toLocaleDateString('pt-BR') : '—' },
  }))
})

const totalValue = computed(() => slices.value.reduce((s, sl) => s + sl.value, 0))
/** Percentual da fatia no total (para centro do donut) */
function slicePct(i: number): string {
  const total = totalValue.value || 1
  return ((slices.value[i].value / total) * 100).toFixed(1)
}
const totalHoursLabel = computed(() => {
  const v = totalValue.value
  if (!Number.isFinite(v) || v <= 0) return '0h'
  if (v >= 1000) return `${(v / 1000).toFixed(1)}k h`
  return `${v.toFixed(1)}h`
})

const isPieOrDoughnut = computed(() => currentType.value === 'pie' || currentType.value === 'doughnut')
const OUTER_R = 160
const INNER_R = 80

/** Cores das fatias para ApexCharts (usa cor da tech ou paleta do tema) */
const apexColors = computed(() => slices.value.map((s, i) => s.color || chartTheme.value.palette[i % chartTheme.value.palette.length]))

/** Série para ApexCharts pie/donut: array de valores */
const apexSeries = computed(() => slices.value.map(s => s.value))

/** Opções ApexCharts: compartilhadas + específicas por tipo (pie / donut) */
const apexChartOptions = computed<ApexOptions>(() => {
  const total = totalValue.value || 1
  const base = {
    chart: {
      type: currentType.value === 'doughnut' ? 'donut' : 'pie',
      background: 'transparent',
      events: {
        dataPointSelection: (_event: unknown, _chartContext: unknown, config: { dataPointIndex?: number }) => {
          if (config?.dataPointIndex != null) handleSliceClick(config.dataPointIndex)
        },
      },
    },
    colors: apexColors.value,
    labels: slices.value.map(s => s.label),
    stroke: { width: 5, colors: [chartTheme.value.background] },
    states: {
      hover: { filter: { type: 'lighten', value: 0.15 } as { type: string; value: number } },
      active: { filter: { type: 'darken', value: 0.35 } as { type: string; value: number } },
    },
    dropShadow: {
      enabled: true,
      top: 4,
      left: 0,
      blur: 20,
      opacity: 0.2,
      color: chartTheme.value.textColor,
    },
    fill: {
      type: 'solid',
      opacity: 1,
    },
    dataLabels: { enabled: false },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 900,
      animateGradually: { enabled: true, delay: 120 },
      dynamicAnimation: { enabled: true, speed: 400 },
    },
    tooltip: {
      theme: 'dark',
      style: { fontSize: chartTheme.value.fontSize, fontFamily: chartTheme.value.fontFamily },
      fillSeriesColor: true,
      y: {
        formatter: (val: number) => `${Number(val).toFixed(1)}h`,
      },
    },
    legend: { show: false },
    plotOptions: {
      pie: {
        expandOnClick: false,
        dataLabels: { offset: -10 },
        ...(currentType.value === 'doughnut'
          ? {
              donut: {
                size: '68%',
                background: 'transparent',
                labels: {
                  show: true,
                  name: {
                    show: true,
                    fontFamily: chartTheme.value.fontFamily,
                    fontSize: chartTheme.value.fontSize,
                    color: chartTheme.value.textMuted,
                    offsetY: -8,
                  },
                  value: {
                    show: true,
                    fontFamily: chartTheme.value.fontFamily,
                    fontSize: '1.25rem',
                    fontWeight: 700,
                    color: chartTheme.value.textColor,
                    offsetY: 8,
                    formatter: (val: string | number) => `${Number(val).toFixed(1)}h`,
                  },
                  total: {
                    show: true,
                    label: 'Total',
                    fontFamily: chartTheme.value.fontFamily,
                    fontSize: chartTheme.value.fontSize,
                    color: chartTheme.value.textMuted,
                    formatter: () => `${total.toFixed(1)}h`,
                  },
                },
              },
            }
          : {}),
      },
    },
  }
  return base as ApexOptions
})

/** Distância da borda da fatia ao card (90–110px) */
const CARD_OFFSET = 100
/** viewBox quando há seleção: estendido para caber cards 80–100px da fatia */
const chartViewBox = computed(() => {
  if (selectedSlice.value == null) return '-200 -200 400 400'
  const { mid } = getAngles(selectedSlice.value!)
  if (Math.cos(mid) > 0) return '-200 -220 460 440'
  return '-430 -220 460 440'
})

/** Ângulo inicial no topo (12h), sentido horário; radianos */
function getAngles(sliceIndex: number): { start: number; end: number; mid: number } {
  const total = totalValue.value || 1
  let acc = 0
  for (let i = 0; i < sliceIndex; i++) acc += slices.value[i].value
  const start = (-Math.PI / 2) + (2 * Math.PI * acc / total)
  const span = (2 * Math.PI * slices.value[sliceIndex].value) / total
  const end = start + span
  const mid = start + span / 2
  return { start, end, mid }
}

/** Path SVG para fatia: PIE (ir=0) ou DONUT (ir=INNER_R) conforme currentType */
function slicePath(sliceIndex: number): string {
  const { start, end } = getAngles(sliceIndex)
  const r = OUTER_R
  const ir = currentType.value === 'doughnut' ? INNER_R : 0
  const x1 = r * Math.cos(start)
  const y1 = r * Math.sin(start)
  const x2 = r * Math.cos(end)
  const y2 = r * Math.sin(end)
  const large = end - start > Math.PI ? 1 : 0
  if (ir <= 0) return `M 0 0 L ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2} Z`
  const xi1 = ir * Math.cos(start)
  const yi1 = ir * Math.sin(start)
  const xi2 = ir * Math.cos(end)
  const yi2 = ir * Math.sin(end)
  return `M ${xi1} ${yi1} L ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2} L ${xi2} ${yi2} A ${ir} ${ir} 0 ${large} 0 ${xi1} ${yi1} Z`
}

/** Ponto na borda externa da fatia (para início da linha) */
function sliceEdgePoint(sliceIndex: number): { x: number; y: number } {
  const { mid } = getAngles(sliceIndex)
  return {
    x: OUTER_R * Math.cos(mid),
    y: OUTER_R * Math.sin(mid),
  }
}

/** Valores de translate para a animação de explosão (CSS vars) */
const explosionStyle = computed(() => {
  if (selectedSlice.value == null) return {}
  const { mid } = getAngles(selectedSlice.value)
  const tx = -OUTER_R * 0.45 * Math.cos(mid)
  const ty = -OUTER_R * 0.45 * Math.sin(mid)
  return { '--explode-tx': `${tx}px`, '--explode-ty': `${ty}px` }
})

/** Lado do card: direita se midAngle aponta para direita (cos > 0), senão esquerda */
const cardSide = computed(() => {
  if (selectedSlice.value == null) return 'right'
  const { mid } = getAngles(selectedSlice.value)
  return Math.cos(mid) > 0 ? 'right' : 'left'
})

/** Borda do card em coordenadas SVG; gap 10px entre os dois cards (card altura 52, centro ±29 e 33) */
const cardEdgeX = computed(() => (cardSide.value === 'right' ? OUTER_R + CARD_OFFSET : -(OUTER_R + CARD_OFFSET)))
const card1Y = -29
const card2Y = 33

/** Pontos da linha em cotovelo: P0=borda fatia, P1=(cardEdgeX, midY), P2=(cardEdgeX, cardY) */
const line1Points = computed(() => {
  if (selectedSlice.value == null || !selectedEdge.value) return ''
  const P0 = selectedEdge.value
  const P1 = `${cardEdgeX.value},${P0.y}`
  const P2 = `${cardEdgeX.value},${card1Y}`
  return `${P0.x},${P0.y} ${P1} ${P2}`
})
const line2Points = computed(() => {
  if (selectedSlice.value == null || !selectedEdge.value) return ''
  const P0 = selectedEdge.value
  const P1 = `${cardEdgeX.value},${P0.y}`
  const P2 = `${cardEdgeX.value},${card2Y}`
  return `${P0.x},${P0.y} ${P1} ${P2}`
})

/** Comprimento total do path em 2 segmentos (para animação stroke-dashoffset) */
function polylineLength(points: string): number {
  const parts = points.split(' ')
  if (parts.length < 2) return 0
  let len = 0
  for (let i = 0; i < parts.length - 1; i++) {
    const [x1, y1] = parts[i].split(',').map(Number)
    const [x2, y2] = parts[i + 1].split(',').map(Number)
    len += Math.hypot(x2 - x1, y2 - y1)
  }
  return len
}
const line1Length = computed(() => polylineLength(line1Points.value))
const line2Length = computed(() => polylineLength(line2Points.value))

const selectedEdge = computed(() =>
  selectedSlice.value != null ? sliceEdgePoint(selectedSlice.value) : null
)

/** Gera gradiente SVG claro→escuro para uma cor hex */
function gradientId(i: number) { return `slice-grad-${i}` }
function colorToRgba(hex: string, alpha: number): string {
  const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  if (!m) return `rgba(100,100,100,${alpha})`
  const r = parseInt(m[1], 16)
  const g = parseInt(m[2], 16)
  const b = parseInt(m[3], 16)
  return `rgba(${r},${g},${b},${alpha})`
}
/** "r,g,b" para uso em variáveis CSS */
function colorToRgb(hex: string): string {
  const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  if (!m) return '99,102,241'
  return `${parseInt(m[1], 16)},${parseInt(m[2], 16)},${parseInt(m[3], 16)}`
}
/** Tom ~30% mais claro (para gradiente centro→borda) */
function colorLighter(hex: string): string {
  const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  if (!m) return hex
  let r = parseInt(m[1], 16)
  let g = parseInt(m[2], 16)
  let b = parseInt(m[3], 16)
  r = Math.min(255, Math.round(r + (255 - r) * 0.3))
  g = Math.min(255, Math.round(g + (255 - g) * 0.3))
  b = Math.min(255, Math.round(b + (255 - b) * 0.3))
  return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`
}
/** Cor com brightness +20% (para valor no card) */
function colorBrightness(hex: string, factor: number = 1.2): string {
  const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
  if (!m) return hex
  let r = parseInt(m[1], 16)
  let g = parseInt(m[2], 16)
  let b = parseInt(m[3], 16)
  r = Math.min(255, Math.round(r * factor))
  g = Math.min(255, Math.round(g * factor))
  b = Math.min(255, Math.round(b * factor))
  return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`
}

function handleSliceClick(index: number) {
  if (currentType.value === 'bar') return
  if (selectedSlice.value === index) {
    handleDismiss()
    return
  }
  if (selectedSlice.value != null) {
    handleDismiss()
    setTimeout(() => {
      selectSlice(index)
    }, 500)
    return
  }
  selectSlice(index)
}

function selectSlice(index: number) {
  selectedSlice.value = index
  phase.value = 'explode'
  line1Visible.value = false
  line2Visible.value = false
  card1Visible.value = false
  card2Visible.value = false
  explodedAnimate.value = false
  nextTick(() => {
    setTimeout(() => { explodedAnimate.value = true }, 50)
    setTimeout(() => {
      phase.value = 'lines'
      line1Visible.value = true
    }, 450)
    setTimeout(() => { line2Visible.value = true }, 650)
    setTimeout(() => { card1Visible.value = true }, 1050)
    setTimeout(() => { card2Visible.value = true }, 1150)
    setTimeout(() => { phase.value = 'cards' }, 1200)
  })
}

function handleDismiss() {
  phase.value = 'idle'
  line1Visible.value = false
  line2Visible.value = false
  card1Visible.value = false
  card2Visible.value = false
  explodedAnimate.value = false
  setTimeout(() => {
    selectedSlice.value = null
  }, 450)
}

watch(currentType, (t) => {
  selectedSlice.value = null
  phase.value = 'idle'
  if (t === 'bar') {
    barChartMounted.value = false
    nextTick(() => { setTimeout(() => { barChartMounted.value = true }, 80) })
  }
})

/** Lista ordenada para o painel lateral (compatível com metrics) */
const sorted = computed(() =>
  [...(props.metrics ?? [])].sort((a, b) => (b.total_minutes ?? 0) - (a.total_minutes ?? 0))
)
const hasMetrics = computed(() => sorted.value.length > 0)
/** Lista exibida no painel: metrics da API ou slices (mock) */
const listForPanel = computed(() => (hasMetrics.value ? sorted.value : slices.value))

function goToTech(metric: TechnologyMetric) {
  if (metric.technology?.id) router.push(`/sessions?technology_id=${metric.technology.id}`)
}

// --- Bar chart (SVG nativo): altura/gap por token; área rolável
const barBarHeight = 30
const barGap = 12
const barLabelWidth = 120
const barChartWidth = 320
const barAreaWidth = barChartWidth - barLabelWidth - 20

const barMax = computed(() => Math.max(...slices.value.map(s => s.value), 1))
const barChartHeight = computed(() => slices.value.length * (barBarHeight + barGap) - barGap)
const barChartHeightPx = computed(() => barChartHeight.value + 40)

const barChartMounted = ref(false)
onMounted(() => {
  nextTick(() => { setTimeout(() => { barChartMounted.value = true }, 50) })
})

function barLabelDisplay(label: string): string {
  return truncate(label, 18)
}
</script>

<template>
  <div class="tech-dist-widget">
    <div class="widget-header">
      <div class="widget-header__top">
        <h3 class="widget-title">
          Distribuição por tecnologia
        </h3>
        <span class="total-badge">{{ totalHoursLabel }}</span>
      </div>
      <div class="chart-type-toggle">
        <button
          type="button"
          class="chart-type-toggle__btn"
          :class="{ active: currentType === 'pie' }"
          @click="currentType = 'pie'"
        >
          Pizza
        </button>
        <button
          type="button"
          class="chart-type-toggle__btn"
          :class="{ active: currentType === 'doughnut' }"
          @click="currentType = 'doughnut'"
        >
          Rosquinha
        </button>
        <button
          type="button"
          class="chart-type-toggle__btn"
          :class="{ active: currentType === 'bar' }"
          @click="currentType = 'bar'"
        >
          Barras
        </button>
      </div>
    </div>

    <div
      v-if="loading"
      class="chart-skeleton"
    >
      <SkeletonLoader
        v-for="i in 6"
        :key="i"
        height="1.25rem"
        class="skeleton-item"
      />
    </div>

    <template v-else>
      <!-- Overlay escuro: blur + spotlight na fatia -->
      <Transition name="overlay">
        <div
          v-if="selectedSlice !== null"
          class="chart-overlay"
          aria-hidden="true"
          @click="handleDismiss"
        />
      </Transition>

      <div class="chart-and-list">
        <div class="chart-area">
          <Transition
            name="chart-switch"
            mode="out-in"
          >
            <!-- Gráfico Pizza (Pie) e Rosquinha (Donut) via ApexCharts + overlay/explosão/linhas/cards -->
            <div
              v-if="isPieOrDoughnut"
              key="pie"
              class="chart-wrap chart-wrap--svg"
              :class="{ 'chart-wrap--has-selection': selectedSlice !== null }"
            >
            <div class="apex-chart-container">
              <apexchart
                :type="currentType === 'doughnut' ? 'donut' : 'pie'"
                height="360"
                :series="apexSeries"
                :options="apexChartOptions"
                class="tech-pie-chart"
              />
            </div>
            <!-- Centro: total em repouso (só no pie); label + % quando fatia selecionada. Donut usa labels do ApexCharts. -->
            <div
              v-if="currentType === 'pie' || selectedSlice !== null"
              class="center-label-overlay"
            >
              <template v-if="selectedSlice === null">
                <span class="center-label__total">{{ totalHoursLabel }}</span>
              </template>
              <template v-else>
                <span class="center-label__name">{{ slices[selectedSlice].label }}</span>
                <span class="center-label__pct">{{ slicePct(selectedSlice) }}%</span>
              </template>
            </div>

            <!-- Fatia explodida (clone por cima, overflow visible) -->
            <div
              v-if="selectedSlice !== null"
              class="exploded-layer"
              :class="{ 'exploded-layer--animate': explodedAnimate }"
              :style="explosionStyle"
            >
              <svg
                class="pie-svg pie-svg--clone"
                viewBox="-200 -200 400 400"
              >
                <defs>
                  <radialGradient
                    :id="`clone-${gradientId(selectedSlice!)}`"
                    cx="50%"
                    cy="50%"
                    r="50%"
                    fx="50%"
                    fy="50%"
                  >
                    <stop
                      offset="0%"
                      :stop-color="colorLighter(slices[selectedSlice!].color)"
                      stop-opacity="1"
                    />
                    <stop
                      offset="100%"
                      :stop-color="slices[selectedSlice!].color"
                      stop-opacity="1"
                    />
                  </radialGradient>
                </defs>
                <path
                  :d="slicePath(selectedSlice!)"
                  :fill="`url(#clone-${gradientId(selectedSlice!)})`"
                  class="slice-path slice-path--exploded"
                  stroke="currentColor"
                  stroke-width="2.5"
                  stroke-linejoin="round"
                />
              </svg>
            </div>

            <!-- Anotações: linhas em cotovelo + dot + cards (viewBox estendido, coordenadas unificadas) -->
            <svg
              v-if="selectedSlice !== null && selectedEdge"
              class="annotation-lines"
              :viewBox="chartViewBox"
            >
              <!-- Dot no ponto inicial (borda da fatia) -->
              <circle
                :cx="selectedEdge.x"
                :cy="selectedEdge.y"
                r="5"
                class="annotation-dot"
                :fill="slices[selectedSlice].color"
              />
              <polyline
                class="annotation-line"
                :class="{ 'annotation-line--drawn': line1Visible }"
                :points="line1Points"
                :style="{ '--line-length': line1Length, '--line-color': colorToRgba(slices[selectedSlice].color, 0.6) }"
              />
              <polyline
                class="annotation-line"
                :class="{ 'annotation-line--drawn': line2Visible }"
                :points="line2Points"
                :style="{ '--line-length': line2Length, '--line-color': colorToRgba(slices[selectedSlice].color, 0.6) }"
              />
              <!-- Cards em foreignObject: x = borda do card onde a linha termina -->
              <foreignObject
                :x="cardSide === 'right' ? cardEdgeX : cardEdgeX - 155"
                :y="card1Y - 24"
                width="155"
                height="52"
                class="info-card-fo"
                :class="{ 'info-card-fo--visible': card1Visible }"
              >
                <div
                  xmlns="http://www.w3.org/1999/xhtml"
                  class="info-card"
                  :style="{ '--card-color': slices[selectedSlice].color, '--card-color-rgb': colorToRgb(slices[selectedSlice].color), borderColor: colorToRgba(slices[selectedSlice].color, 0.35) }"
                >
                  <span
                    class="info-card__accent"
                    :style="{ background: `linear-gradient(180deg, ${colorLighter(slices[selectedSlice].color)}, ${slices[selectedSlice].color})` }"
                  />
                  <span class="info-card__label">{{ slices[selectedSlice].info1.label }}</span>
                  <hr class="info-card__sep">
                  <span
                    class="info-card__value"
                    :style="{ color: colorBrightness(slices[selectedSlice].color) }"
                  >{{ slices[selectedSlice].info1.value }}</span>
                </div>
              </foreignObject>
              <foreignObject
                :x="cardSide === 'right' ? cardEdgeX : cardEdgeX - 155"
                :y="card2Y - 26"
                width="155"
                height="52"
                class="info-card-fo"
                :class="{ 'info-card-fo--visible': card2Visible }"
              >
                <div
                  xmlns="http://www.w3.org/1999/xhtml"
                  class="info-card"
                  :style="{ '--card-color': slices[selectedSlice].color, '--card-color-rgb': colorToRgb(slices[selectedSlice].color), borderColor: colorToRgba(slices[selectedSlice].color, 0.35) }"
                >
                  <span
                    class="info-card__accent"
                    :style="{ background: `linear-gradient(180deg, ${colorLighter(slices[selectedSlice].color)}, ${slices[selectedSlice].color})` }"
                  />
                  <span class="info-card__label">{{ slices[selectedSlice].info2.label }}</span>
                  <hr class="info-card__sep">
                  <span
                    class="info-card__value"
                    :style="{ color: colorBrightness(slices[selectedSlice].color) }"
                  >{{ slices[selectedSlice].info2.value }}</span>
                </div>
              </foreignObject>
            </svg>
            </div>

            <!-- Gráfico de barras: SVG nativo, área rolável, barras animadas -->
            <div
              v-else
              key="bar"
              class="chart-wrap chart-wrap--bar"
              :class="{ 'bar-chart--mounted': barChartMounted }"
            >
              <div class="bar-chart-scroll">
                <svg
                  class="bar-svg"
                  :viewBox="`0 0 ${barChartWidth} ${barChartHeightPx}`"
                  :height="barChartHeightPx"
                  preserveAspectRatio="xMinYMin meet"
                >
                  <g
                    v-for="(sl, i) in slices"
                    :key="i"
                    class="bar-row"
                  >
                    <title>{{ sl.label }}</title>
                    <text
                      class="bar-label"
                      :y="i * (barBarHeight + barGap) + barBarHeight / 2 + 4"
                      x="0"
                    >
                      {{ barLabelDisplay(sl.label) }}
                    </text>
                    <g
                      class="bar-rect-wrap"
                      :style="{ transform: `translate(${barLabelWidth}px, ${i * (barBarHeight + barGap)}px)` }"
                    >
                      <rect
                        class="bar-rect"
                        x="0"
                        y="0"
                        :width="(sl.value / barMax) * barAreaWidth"
                        :height="barBarHeight"
                        :fill="sl.color"
                        rx="4"
                        :style="{ transitionDelay: `${i * 40}ms` }"
                      />
                    </g>
                    <text
                      class="bar-value"
                      :y="i * (barBarHeight + barGap) + barBarHeight / 2 + 4"
                      :x="barLabelWidth + (sl.value / barMax) * barAreaWidth + 8"
                    >
                      {{ sl.value }}h
                    </text>
                  </g>
                </svg>
              </div>
            </div>
          </Transition>

          <p class="hint">
            Clique em uma fatia para destacá-la. Passe o mouse para detalhes. Use a lista para ver sessões.
          </p>
        </div>

        <aside
          class="tech-panel"
          :class="{ 'tech-panel--collapsed': !showTechList }"
        >
          <button
            type="button"
            class="tech-panel-toggle"
            :aria-expanded="showTechList"
            :aria-label="showTechList ? 'Recolher lista de tecnologias' : 'Expandir lista de tecnologias'"
            @click="showTechList = !showTechList"
          >
            <span>Tecnologias ({{ listForPanel.length }})</span>
            <span class="tech-panel-toggle__icon">{{ showTechList ? '▼' : '▶' }}</span>
          </button>
          <Transition name="slide">
            <div
              v-show="showTechList"
              class="tech-list"
            >
              <button
                v-for="(item, i) in listForPanel"
                :key="i"
                type="button"
                class="tech-list-item"
                @click="hasMetrics ? goToTech(item as TechnologyMetric) : handleSliceClick(i)"
              >
                <span
                  class="tech-list-item__dot"
                  :style="{
                    background:
                      (item as TechnologyMetric).technology?.color
                      ?? (item as ChartSlice).color
                      ?? FALLBACK_COLORS[i % FALLBACK_COLORS.length],
                  }"
                />
                <span class="tech-list-item__name">
                  {{ (item as TechnologyMetric).technology?.name ?? (item as ChartSlice).label ?? 'Sem nome' }}
                </span>
                <span class="tech-list-item__hours">
                  {{ hasMetrics ? toHours((item as TechnologyMetric).total_minutes ?? 0) + 'h' : (item as ChartSlice).value + 'h' }}
                </span>
              </button>
            </div>
          </Transition>
        </aside>
      </div>
    </template>
  </div>
</template>

<style scoped>
.tech-dist-widget {
  background: var(--color-bg-card);
  border-radius: var(--widget-radius);
  padding: var(--widget-padding);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  overflow: visible;
  min-height: var(--widget-chart-min-height);
  max-height: 520px;
  display: flex;
  flex-direction: column;
  position: relative;
}
.tech-dist-widget::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3CfeColorMatrix type='saturate' values='0'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
  pointer-events: none;
  opacity: 0.6;
}

.widget-header { margin-bottom: var(--spacing-md); flex-shrink: 0; }
.widget-header__top { display: flex; align-items: center; gap: var(--spacing-md); margin-bottom: var(--spacing-sm); flex-wrap: wrap; }
.widget-title {
  font-family: 'Syne', var(--font-sans), sans-serif;
  font-size: var(--widget-title-size);
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.01em;
  color: var(--color-text);
  background: linear-gradient(135deg, var(--color-text) 0%, var(--color-primary) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.total-badge {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  background: var(--color-bg-soft);
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.chart-type-toggle { display: inline-flex; padding: var(--spacing-2xs); border-radius: var(--radius-md); background: var(--color-bg-soft); border: 1px solid var(--color-border); gap: var(--spacing-2xs); }
.chart-type-toggle__btn {
  border: none;
  background: transparent;
  min-height: var(--input-height-sm);
  padding: 0.35rem 0.7rem;
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease;
}
.chart-type-toggle__btn:hover { color: var(--color-text); background: var(--color-bg-card); }
.chart-type-toggle__btn:focus-visible { outline: none; box-shadow: var(--shadow-focus); }
.chart-type-toggle__btn.active {
  background: var(--color-primary);
  color: var(--color-primary-contrast);
  box-shadow: var(--shadow-sm);
}
.chart-type-toggle__btn.active:focus-visible { box-shadow: var(--shadow-focus); }

.chart-skeleton { min-height: var(--widget-chart-min-height-sm); display: flex; flex-direction: column; gap: var(--spacing-sm); padding: var(--spacing-md) 0; }
.skeleton-item { width: 70%; }

.chart-and-list { display: flex; flex-direction: column; gap: var(--spacing-md); min-height: 0; flex: 1; overflow: visible; }
@media (min-width: 900px) {
  .chart-and-list { flex-direction: row; align-items: stretch; gap: var(--spacing-xl); height: calc(360px + 2.5rem); flex: 0 0 auto; }
  .chart-area { flex: 1; min-width: 0; min-height: 0; height: 100%; }
  .tech-panel { width: 260px; flex-shrink: 0; min-height: 0; display: flex; flex-direction: column; overflow: hidden; }
  .tech-panel:not(.tech-panel--collapsed) { height: 100%; max-height: 100%; }
  .tech-panel .tech-list { flex: 1; min-height: 0; max-height: none; overflow-y: auto; }
  .tech-panel--collapsed { height: auto; align-self: flex-start; }
}

.chart-area { display: flex; flex-direction: column; gap: 0.5rem; overflow: visible; }

/* Container do gráfico: wrapper premium (ApexCharts) */
.chart-wrap {
  position: relative;
  border-radius: var(--radius-lg);
  overflow: visible;
  background: var(--color-bg-soft);
  border: 1px solid var(--color-border);
  min-height: 320px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: var(--shadow-md);
}
.chart-wrap--svg { height: 360px; max-height: 360px; }
.chart-wrap--bar { min-height: 320px; display: flex; flex-direction: column; }
.bar-chart-scroll {
  max-height: 360px;
  overflow-y: auto;
  overflow-x: hidden;
  border-radius: var(--radius-md);
  flex: 1;
  min-height: 200px;
}
.bar-rect-wrap { transform-origin: 0 0; }
.bar-rect {
  transform-origin: 0 50%;
  transform: scaleX(0);
  transition: transform 0.35s var(--ease-out-expo);
}
.bar-chart--mounted .bar-rect {
  transform: scaleX(1);
}
@media (prefers-reduced-motion: reduce) {
  .bar-rect {
    transform: scaleX(1);
    transition-duration: 0.01ms;
    transition-delay: 0;
  }
}

/* Transição ao trocar tipo de gráfico (Pizza / Rosquinha / Barras) */
.chart-switch-enter-active,
.chart-switch-leave-active {
  transition: opacity var(--duration-normal) var(--ease-out-expo),
    transform var(--duration-normal) var(--ease-out-expo);
}
.chart-switch-enter-from,
.chart-switch-leave-to {
  opacity: 0;
  transform: scale(0.98);
}
@media (prefers-reduced-motion: reduce) {
  .chart-switch-enter-active,
  .chart-switch-leave-active {
    transition-duration: 0.01ms;
  }
}
.apex-chart-container {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  max-width: 400px;
  max-height: 400px;
  margin: auto;
}
.tech-pie-chart {
  width: 100% !important;
  height: 100% !important;
  max-width: 400px;
  max-height: 400px;
  cursor: pointer;
}
.pie-svg--clone { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 100%; height: 100%; max-width: 400px; max-height: 400px; pointer-events: none; color: var(--color-border); }

/* Overlay do centro (total ou label + % da fatia selecionada) */
.center-label-overlay {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  text-align: center;
}
.center-label__total {
  font-family: 'Syne', sans-serif;
  font-size: 20px;
  font-weight: 700;
  color: #fff;
}
.center-label__name {
  font-family: 'DM Sans', sans-serif;
  font-size: 11px;
  color: rgba(255,255,255,0.7);
  font-weight: 500;
}
.center-label__pct {
  font-family: 'Syne', sans-serif;
  font-size: 18px;
  color: #fff;
  font-weight: 700;
}

/* Camada da fatia explodida: acima do gráfico, animação de translate + scale */
.exploded-layer {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%) scale(1);
  width: 100%;
  height: 100%;
  max-width: 400px;
  max-height: 400px;
  pointer-events: none;
  z-index: 200;
  transition: transform 600ms cubic-bezier(0.34, 1.56, 0.64, 1);
}
.exploded-layer--animate {
  transform: translate(-50%, -50%) translate(var(--explode-tx, 0), var(--explode-ty, 0)) scale(1.7);
}

/* Overlay escuro com blur */
.chart-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;
  background: color-mix(in srgb, var(--color-bg) 25%, transparent);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  cursor: pointer;
}
.overlay-enter-active { transition: opacity 300ms ease-out; }
.overlay-leave-active { transition: opacity 400ms ease-in; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }
.overlay-enter-to, .overlay-leave-from { opacity: 1; }

/* Linhas de anotação (cotovelo) + dot + foreignObject cards */
.annotation-lines {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  max-width: 400px;
  max-height: 400px;
  pointer-events: none;
  z-index: 201;
}
.annotation-lines[viewBox] { overflow: visible; }
.annotation-dot {
  opacity: 0.95;
  transform-origin: center;
  animation: dot-pulse 1.5s ease-in-out infinite;
}
@keyframes dot-pulse {
  0%, 100% { transform: scale(1); opacity: 0.95; }
  50% { transform: scale(1.35); opacity: 1; }
}
.annotation-line {
  fill: none;
  stroke: var(--line-color, rgba(255,255,255,0.4));
  stroke-width: 1.5;
  stroke-dasharray: 4 4;
  stroke-dashoffset: var(--line-length);
  transition: stroke-dashoffset 400ms cubic-bezier(0.34, 1.56, 0.64, 1);
}
.annotation-line--drawn { stroke-dashoffset: 0; }

.info-card-fo { opacity: 0; transition: opacity 300ms ease-out; }
.info-card-fo--visible { opacity: 1; }

/* Cards: tokens para tema claro/escuro, barra de destaque da tech */
.info-card {
  box-sizing: border-box;
  width: 100%;
  height: 100%;
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0;
  max-width: 155px;
  width: 155px;
  box-shadow: var(--shadow-md);
}
.info-card__accent {
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  width: 3px;
  border-radius: 3px 0 0 3px;
  background: var(--card-color, var(--color-primary));
}
.info-card__label {
  display: block;
  font-family: 'DM Sans', var(--font-sans), sans-serif;
  font-size: var(--text-xs);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--color-text-muted);
}
.info-card__sep {
  border: none;
  height: 1px;
  background: var(--color-border);
  margin: var(--spacing-xs) 0;
}
.info-card__value {
  display: block;
  font-family: 'Syne', var(--font-sans), sans-serif;
  font-size: var(--text-xl);
  font-weight: 700;
  line-height: 1.2;
  color: var(--color-text);
}

/* Bar chart */
.bar-svg { display: block; min-width: 100%; }
.bar-row { }
.bar-label {
  font-size: var(--text-sm);
  fill: var(--color-text);
}
.bar-value {
  font-size: var(--text-xs);
  fill: var(--color-text-muted);
  font-variant-numeric: tabular-nums;
}

.hint {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: var(--spacing-xs) 0 0;
  line-height: 1.4;
}

.tech-panel { border: 1px solid var(--color-border); border-radius: var(--radius-md); overflow: hidden; background: var(--color-bg-soft); padding: var(--spacing-2xs); }
.tech-panel-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-sm);
  background: var(--color-bg-card);
  border: none;
  cursor: pointer;
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-text-muted);
  transition: background var(--duration-fast) ease, color var(--duration-fast) ease;
}
.tech-panel-toggle:hover { background: var(--color-bg-soft); color: var(--color-primary); }
.tech-panel-toggle:focus-visible { outline: none; box-shadow: var(--shadow-focus); }
.tech-panel-toggle__icon { font-size: 0.6rem; color: var(--color-text-muted); }
.tech-list {
  max-height: 280px;
  overflow-y: auto;
  overflow-x: hidden;
  padding: var(--spacing-md);
  background: var(--color-bg-card);
  scrollbar-width: thin;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
}
.tech-list::-webkit-scrollbar { width: 6px; }
.tech-list::-webkit-scrollbar-track { background: var(--color-bg-soft); border-radius: var(--radius-sm); }
.tech-list::-webkit-scrollbar-thumb { background: var(--color-border); border-radius: var(--radius-sm); }
.tech-list-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  width: 100%;
  padding: var(--spacing-sm) var(--spacing-xs);
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: var(--text-sm);
  text-align: left;
  color: var(--color-text);
  transition: background var(--duration-fast) ease;
  border-radius: var(--radius-sm);
}
.tech-list-item:hover { background: var(--color-bg-soft); }
.tech-list-item__dot { flex-shrink: 0; width: 0.5rem; height: 0.5rem; border-radius: 50%; }
.tech-list-item__name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: var(--text-sm); }
.tech-list-item__hours { flex-shrink: 0; font-weight: 600; font-variant-numeric: tabular-nums; color: var(--color-text-muted); font-size: var(--text-xs); }

.slide-enter-active, .slide-leave-active { transition: max-height var(--duration-normal) var(--ease-in-out), opacity var(--duration-fast) ease; }
.slide-enter-from, .slide-leave-to { max-height: 0; opacity: 0; overflow: hidden; }
.slide-enter-to, .slide-leave-from { max-height: 280px; opacity: 1; }
@media (min-width: 900px) { .slide-enter-to.tech-list, .slide-leave-from.tech-list { max-height: 500px; } }
</style>
