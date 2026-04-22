import { z } from 'zod'

/**
 * Schemas Zod para validar respostas da API em runtime.
 * Tipos podem ser derivados com z.infer<typeof Schema>.
 */

export const userMetricsSchema = z.object({
  total_sessions: z.number(),
  total_minutes: z.number(),
  total_hours: z.number(),
  avg_session_min: z.number().optional(),
  longest_session_min: z.number().optional(),
  current_streak_days: z.number().optional(),
  max_streak_days: z.number().optional(),
  last_session_at: z.string().nullable().optional(),
})

/** Campos opcionais que a API pode enviar como `null` (Laravel) — não usar só `.optional()`, que rejeita null. */
const optionalApiString = z
  .union([z.string(), z.null()])
  .optional()
  .transform((v) => (v == null ? undefined : v))

export const technologySchema = z.object({
  id: z.string(),
  name: z.string(),
  slug: z.string(),
  color: z.string(),
  icon: optionalApiString,
  description: optionalApiString,
  is_active: z.boolean().default(true),
  created_at: z.string().optional(),
  updated_at: z.string().optional(),
})

export const technologyMetricSchema = z.object({
  technology: technologySchema.nullable(),
  total_minutes: z.number(),
  total_hours: z.number().optional(),
  session_count: z.number(),
  percentage_total: z.number().optional(),
  last_studied_at: z.string().nullable().optional(),
})

export const dailyMinuteSchema = z.object({
  date: z.string(),
  total_minutes: z.number(),
  session_count: z.number().optional(),
})

export const dashboardDataSchema = z.object({
  user_metrics: userMetricsSchema,
  technology_metrics: z.array(technologyMetricSchema),
  time_series_30d: z.array(dailyMinuteSchema),
  top_technologies: z.array(technologyMetricSchema),
})

export const apiResponseSchema = <T extends z.ZodType>(dataSchema: T) =>
  z.object({
    success: z.literal(true),
    data: dataSchema,
    message: z.string().optional(),
    meta: z.record(z.unknown()).optional(),
  })

export const studySessionSchema = z.object({
  id: z.string(),
  user_id: z.string(),
  technology_id: z.string(),
  title: z.string().nullable().optional(),
  technology: technologySchema.optional(),
  started_at: z.string(),
  ended_at: z.string().nullable(),
  duration_min: z.number().nullable(),
  duration_formatted: z.string().nullable().optional(),
  notes: z.string().nullable(),
  mood: z.number().nullable(),
  focus_score: z.number().nullable().optional(),
  created_at: z.string(),
})

export const activeSessionSchema = studySessionSchema.extend({
  elapsed_seconds: z.number(),
})

export const paginationMetaSchema = z.object({
  current_page: z.number(),
  last_page: z.number(),
  per_page: z.number(),
  total: z.number(),
})

export type DashboardDataParsed = z.infer<typeof dashboardDataSchema>
export type StudySessionParsed = z.infer<typeof studySessionSchema>
export type TechnologyParsed = z.infer<typeof technologySchema>

/**
 * Valida o payload da API e retorna os dados tipados ou lança.
 */
export function parseDashboardResponse(raw: unknown): DashboardDataParsed {
  const parsed = z.object({ success: z.boolean(), data: dashboardDataSchema }).safeParse(raw)
  if (!parsed.success) {
    if (import.meta.env.DEV) {
      console.warn('[Dashboard] Zod parse failed:', parsed.error.flatten())
    }
    throw new Error('Resposta inválida do dashboard')
  }
  if (!parsed.data.success || !parsed.data.data) {
    throw new Error('Falha ao carregar dashboard')
  }
  return parsed.data.data
}

export function parseSessionsListResponse(raw: unknown): {
  data: StudySessionParsed[]
  meta?: z.infer<typeof paginationMetaSchema>
} {
  const parsed = z
    .object({
      success: z.boolean(),
      data: z.array(studySessionSchema),
      meta: paginationMetaSchema.optional(),
    })
    .safeParse(raw)
  if (!parsed.success || !parsed.data.success) {
    throw new Error('Resposta inválida da lista de sessões')
  }
  return { data: parsed.data.data, meta: parsed.data.meta }
}

export function parseTechnologiesListResponse(raw: unknown): TechnologyParsed[] {
  const parsed = z
    .object({
      success: z.boolean(),
      data: z.union([z.array(technologySchema), z.null()]),
    })
    .safeParse(raw)
  if (!parsed.success) {
    if (import.meta.env.DEV) {
      console.warn('[Technologies] Zod parse failed:', parsed.error.flatten())
    }
    throw new Error('Resposta inválida da lista de tecnologias')
  }
  if (!parsed.data.success) {
    throw new Error('Resposta inválida da lista de tecnologias')
  }
  return parsed.data.data ?? []
}
