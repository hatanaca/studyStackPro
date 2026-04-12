import { ref } from 'vue'
import type { StudySession } from '@/types/domain.types'

interface PdfOptions {
  title: string
  dateRange: { start: string; end: string }
  sessions: StudySession[]
}

const PAGE_W = 595.28
const PAGE_H = 841.89
const MARGIN = 40
const CONTENT_W = PAGE_W - MARGIN * 2
const FOOTER_H = 30

const TITLE_FONT_SIZE = 18
const HEADING_FONT_SIZE = 12
const BODY_FONT_SIZE = 10
const SMALL_FONT_SIZE = 8

const TITLE_LH = TITLE_FONT_SIZE * 1.4
const HEADING_LH = HEADING_FONT_SIZE * 1.4
const BODY_LH = BODY_FONT_SIZE * 1.5
const SMALL_LH = SMALL_FONT_SIZE * 1.5

const FONT = 'helvetica'
const TITLE_FONT = `bold ${TITLE_FONT_SIZE}px ${FONT}`
const HEADING_FONT = `bold ${HEADING_FONT_SIZE}px ${FONT}`
const BODY_FONT = `${BODY_FONT_SIZE}px ${FONT}`
const SMALL_FONT = `${SMALL_FONT_SIZE}px ${FONT}`

function formatDuration(min: number | null): string {
  if (!min) return '-'
  const h = Math.floor(min / 60)
  const m = min % 60
  return h > 0 ? `${h}h ${m}min` : `${m}min`
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

export function usePdfGenerator() {
  const generating = ref(false)

  async function generateReport(options: PdfOptions) {
    generating.value = true

    try {
      const [{ default: JsPDF }, { prepareWithSegments, layoutWithLines }] = await Promise.all([
        import('jspdf'),
        import('@chenglou/pretext'),
      ])

      function pretextLines(text: string, font: string, maxWidth: number, lineHeight: number) {
        const prepared = prepareWithSegments(text, font)
        return layoutWithLines(prepared, maxWidth, lineHeight)
      }

      const pdf = new JsPDF({ unit: 'pt', format: 'a4' })
      let y = MARGIN
      let pageNum = 1

      function addPage() {
        drawFooter()
        pdf.addPage()
        pageNum++
        y = MARGIN
      }

      function drawFooter() {
        pdf.setFontSize(SMALL_FONT_SIZE)
        pdf.setTextColor(150)
        pdf.text(`Página ${pageNum}`, PAGE_W / 2, PAGE_H - 15, { align: 'center' })
        pdf.text('StudyTrack Pro', MARGIN, PAGE_H - 15)
        pdf.setTextColor(0)
      }

      function ensureSpace(needed: number) {
        if (y + needed > PAGE_H - MARGIN - FOOTER_H) addPage()
      }

      function drawText(text: string, font: string, fontSize: number, lineHeight: number, color = 0) {
        const { lines } = pretextLines(text, font, CONTENT_W, lineHeight)
        pdf.setFontSize(fontSize)
        pdf.setTextColor(color)
        for (const line of lines) {
          ensureSpace(lineHeight)
          pdf.text(line.text, MARGIN, y + fontSize)
          y += lineHeight
        }
      }

      // ── Title ──
      drawText(options.title, TITLE_FONT, TITLE_FONT_SIZE, TITLE_LH)
      y += 4

      const rangeText = `Período: ${formatDate(options.dateRange.start)} — ${formatDate(options.dateRange.end)}`
      drawText(rangeText, SMALL_FONT, SMALL_FONT_SIZE, SMALL_LH, 100)
      y += 16

      // ── Summary ──
      const totalMin = options.sessions.reduce((s, ss) => s + (ss.duration_min ?? 0), 0)
      const totalH = Math.floor(totalMin / 60)
      const totalM = totalMin % 60
      const techSet = new Set(options.sessions.map((s) => s.technology?.name).filter(Boolean))

      drawText('Resumo', HEADING_FONT, HEADING_FONT_SIZE, HEADING_LH)
      y += 4

      pdf.setDrawColor(200)
      pdf.line(MARGIN, y, MARGIN + CONTENT_W, y)
      y += 8

      const summaryLines = [
        `Total de sessões: ${options.sessions.length}`,
        `Tempo total: ${totalH}h ${totalM}min`,
        `Tecnologias: ${techSet.size} (${[...techSet].join(', ')})`,
      ]
      for (const line of summaryLines) {
        drawText(line, BODY_FONT, BODY_FONT_SIZE, BODY_LH)
      }
      y += 16

      // ── Sessions table ──
      drawText('Sessões', HEADING_FONT, HEADING_FONT_SIZE, HEADING_LH)
      y += 4
      pdf.line(MARGIN, y, MARGIN + CONTENT_W, y)
      y += 8

      const colWidths = [80, 120, 70, 60, CONTENT_W - 330]
      const headers = ['Data', 'Tecnologia', 'Duração', 'Mood', 'Notas']

      pdf.setFontSize(SMALL_FONT_SIZE)
      pdf.setFont(FONT, 'bold')
      let xOff = MARGIN
      for (let i = 0; i < headers.length; i++) {
        pdf.text(headers[i], xOff, y + SMALL_FONT_SIZE)
        xOff += colWidths[i]
      }
      y += SMALL_LH + 4
      pdf.setFont(FONT, 'normal')

      const YIELD_EVERY = 50
      for (let si = 0; si < options.sessions.length; si++) {
        if (si > 0 && si % YIELD_EVERY === 0) {
          await new Promise<void>((r) => setTimeout(r, 0))
        }

        const session = options.sessions[si]
        const rowHeight = BODY_LH + 4
        ensureSpace(rowHeight)

        xOff = MARGIN
        pdf.setFontSize(SMALL_FONT_SIZE)

        pdf.text(formatDate(session.started_at), xOff, y + SMALL_FONT_SIZE)
        xOff += colWidths[0]

        pdf.text(session.technology?.name ?? '-', xOff, y + SMALL_FONT_SIZE)
        xOff += colWidths[1]

        pdf.text(formatDuration(session.duration_min), xOff, y + SMALL_FONT_SIZE)
        xOff += colWidths[2]

        pdf.text(session.mood ? `${session.mood}/5` : '-', xOff, y + SMALL_FONT_SIZE)
        xOff += colWidths[3]

        const notes = session.notes ?? '-'
        const maxNotesW = colWidths[4]
        const { lines: noteLines } = pretextLines(notes, SMALL_FONT, maxNotesW, SMALL_LH)
        const firstLine = noteLines[0]?.text ?? '-'
        const truncated = noteLines.length > 1 ? firstLine + '…' : firstLine
        pdf.text(truncated, xOff, y + SMALL_FONT_SIZE)

        y += rowHeight

        pdf.setDrawColor(230)
        pdf.line(MARGIN, y - 2, MARGIN + CONTENT_W, y - 2)
      }

      drawFooter()

      const filename = `studytrack-relatorio-${options.dateRange.start}-${options.dateRange.end}.pdf`
      pdf.save(filename)
    } finally {
      generating.value = false
    }
  }

  return { generating, generateReport }
}
