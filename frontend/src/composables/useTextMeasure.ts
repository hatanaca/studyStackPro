import {
  prepare,
  layout,
  prepareWithSegments,
  layoutWithLines,
  walkLineRanges,
} from '@chenglou/pretext'
import { computed, type MaybeRefOrGetter, toValue } from 'vue'

type Prepared = ReturnType<typeof prepare>
type PreparedSegments = ReturnType<typeof prepareWithSegments>

const MAX_CACHE = 500
const prepareMap = new Map<string, Prepared>()
const segmentsMap = new Map<string, PreparedSegments>()

function cacheKey(text: string, font: string): string {
  // Use a compact hash to avoid storing full text in keys
  const textKey =
    text.length <= 80 ? text : `${text.length}:${text.slice(0, 40)}|${text.slice(-30)}`
  return font + '\0' + textKey
}

/** LRU eviction: move accessed entry to end, remove oldest when over limit. */
function lruAccess<V>(map: Map<string, V>, key: string, value: V): void {
  if (map.has(key)) {
    map.delete(key)
  } else if (map.size >= MAX_CACHE) {
    const first = map.keys().next().value
    if (first !== undefined) map.delete(first)
  }
  map.set(key, value)
}

function getPrepared(text: string, font: string): Prepared {
  const k = cacheKey(text, font)
  const cached = prepareMap.get(k)
  if (cached) {
    lruAccess(prepareMap, k, cached)
    return cached
  }
  const p = prepare(text, font)
  lruAccess(prepareMap, k, p)
  return p
}

function getPreparedSegments(text: string, font: string): PreparedSegments {
  const k = cacheKey(text, font)
  const cached = segmentsMap.get(k)
  if (cached) {
    lruAccess(segmentsMap, k, cached)
    return cached
  }
  const p = prepareWithSegments(text, font)
  lruAccess(segmentsMap, k, p)
  return p
}

// ── Imperative API ──

export function measureText(
  text: string,
  font: string,
  maxWidth: number,
  lineHeight: number
): { height: number; lineCount: number } {
  if (
    !text ||
    !Number.isFinite(maxWidth) ||
    maxWidth <= 0 ||
    !Number.isFinite(lineHeight) ||
    lineHeight <= 0
  ) {
    return { height: 0, lineCount: 0 }
  }

  try {
    return layout(getPrepared(text, font), maxWidth, lineHeight)
  } catch {
    return { height: 0, lineCount: 0 }
  }
}

export function measureTextWithLines(
  text: string,
  font: string,
  maxWidth: number,
  lineHeight: number
) {
  if (!text || maxWidth <= 0 || lineHeight <= 0) {
    return { height: 0, lineCount: 0, lines: [] as { text: string; width: number }[] }
  }
  return layoutWithLines(getPreparedSegments(text, font), maxWidth, lineHeight)
}

export function measureLineCount(
  text: string,
  font: string,
  maxWidth: number
): { lineCount: number; maxLineWidth: number } {
  if (!text || maxWidth <= 0) return { lineCount: 0, maxLineWidth: 0 }
  const prepared = getPreparedSegments(text, font)
  let lineCount = 0
  let maxLineWidth = 0
  walkLineRanges(prepared, maxWidth, (line) => {
    lineCount++
    if (line.width > maxLineWidth) maxLineWidth = line.width
  })
  return { lineCount, maxLineWidth }
}

// ── Reactive API ──

export function useTextLayout(
  text: MaybeRefOrGetter<string>,
  font: MaybeRefOrGetter<string>,
  maxWidth: MaybeRefOrGetter<number>,
  lineHeight: MaybeRefOrGetter<number>
) {
  const result = computed(() =>
    measureText(toValue(text), toValue(font), toValue(maxWidth), toValue(lineHeight))
  )
  return {
    height: computed(() => result.value.height),
    lineCount: computed(() => result.value.lineCount),
  }
}

// ── Font helpers ──

/**
 * Extracts a canvas-compatible font string and pixel line-height
 * from a mounted DOM element's computed style.
 */
export function resolveFont(el: HTMLElement): { font: string; lineHeightPx: number } {
  const cs = window.getComputedStyle(el)
  const sizePx = parseFloat(cs.fontSize)
  const lhRaw = parseFloat(cs.lineHeight)
  const lineHeightPx = Number.isFinite(lhRaw) ? lhRaw : sizePx * 1.5
  const font = `${cs.fontWeight} ${sizePx}px ${cs.fontFamily}`
  return { font, lineHeightPx }
}

let _sessionNotesFont: { font: string; lineHeightPx: number } | null = null

/**
 * Returns the font string and line-height for session card notes.
 * Computed once from the root font-size and design tokens.
 */
export function getSessionNotesFont(): { font: string; lineHeightPx: number } {
  if (_sessionNotesFont) return _sessionNotesFont
  const root = document.documentElement
  const rootCs = window.getComputedStyle(root)
  const rootSize = parseFloat(rootCs.fontSize) || 16
  const sizePx = 0.875 * rootSize
  const snug = rootCs.getPropertyValue('--leading-snug').trim()
  let lineHeightPx: number
  if (snug) {
    const parsed = parseFloat(snug)
    lineHeightPx = Number.isFinite(parsed)
      ? parsed <= 3
        ? sizePx * parsed
        : parsed
      : sizePx * 1.375
  } else {
    lineHeightPx = sizePx * 1.375
  }
  const fontFamily =
    rootCs.getPropertyValue('--font-sans').trim() || "'DM Sans', system-ui, sans-serif"
  _sessionNotesFont = {
    font: `normal ${sizePx}px ${fontFamily}`,
    lineHeightPx,
  }
  return _sessionNotesFont
}

export function clearMeasureCache(): void {
  prepareMap.clear()
  segmentsMap.clear()
  _sessionNotesFont = null
  _cachedPadPx = null
}

let _cachedPadPx: number | null = null

function resolveCardPadding(): number {
  if (_cachedPadPx !== null) return _cachedPadPx
  if (typeof document === 'undefined') return 16
  const cs = window.getComputedStyle(document.documentElement)
  const lg = cs.getPropertyValue('--spacing-lg').trim()
  let padEachPx = 16
  if (lg.endsWith('rem')) {
    padEachPx = parseFloat(lg) * (parseFloat(cs.fontSize) || 16)
  } else if (lg.endsWith('px')) {
    padEachPx = parseFloat(lg)
  }
  _cachedPadPx = padEachPx
  return padEachPx
}

/**
 * Largura máxima para quebra de texto nas notas do SessionCard.
 * O virtualizador usa a largura do container; o texto efetivo é mais estreito (padding + borda).
 */
export function sessionCardNotesMaxWidth(containerOuterWidth: number): number {
  if (!Number.isFinite(containerOuterWidth) || containerOuterWidth <= 0) return 400
  if (typeof document === 'undefined') {
    return Math.max(48, containerOuterWidth - 34)
  }
  const padEachPx = resolveCardPadding()
  const cardBorderHorizontal = 2
  return Math.max(48, containerOuterWidth - cardBorderHorizontal - padEachPx * 2)
}
