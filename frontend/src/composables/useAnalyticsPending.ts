import { ref, computed } from 'vue'
import type { DailyMinute, UserMetrics, TechnologyMetric } from '@/types/domain.types'

export interface PendingSession {
  date: string
  minutes: number
  technology: { id: string; name: string; color: string } | null
}

export function useAnalyticsPending() {
  const pendingSessions = ref<PendingSession[]>([])
  const sessionCountAtPendingStart = ref<number | null>(null)

  function mergeDailyWithPending(raw: DailyMinute[]): DailyMinute[] {
    if (!pendingSessions.value.length) return raw
    const merged = raw.map((d) => ({ ...d }))
    for (const ps of pendingSessions.value) {
      const entry = merged.find((d) => d.date === ps.date)
      if (entry) {
        entry.total_minutes += ps.minutes
        entry.session_count = (entry.session_count ?? 0) + 1
      } else {
        merged.push({ date: ps.date, total_minutes: ps.minutes, session_count: 1 })
      }
    }
    merged.sort((a, b) => a.date.localeCompare(b.date))
    return merged
  }

  function reconcilePending(apiTotalSessions: number) {
    if (!pendingSessions.value.length || sessionCountAtPendingStart.value === null) return
    const expected = sessionCountAtPendingStart.value + pendingSessions.value.length
    if (apiTotalSessions >= expected) {
      pendingSessions.value = []
      sessionCountAtPendingStart.value = null
    }
  }

  function addLocalTodaySession(
    sessionDate: string,
    minutes: number,
    technology?: { id: string; name: string; color: string },
    currentTotalSessions?: number
  ) {
    if (pendingSessions.value.length === 0) {
      sessionCountAtPendingStart.value = currentTotalSessions ?? 0
    }
    pendingSessions.value = [
      ...pendingSessions.value,
      { date: sessionDate, minutes, technology: technology ?? null },
    ]
  }

  function clearPending() {
    pendingSessions.value = []
    sessionCountAtPendingStart.value = null
  }

  function applyPendingToUserMetrics(base: UserMetrics | null): UserMetrics | null {
    if (!base) return null
    if (!pendingSessions.value.length) return base
    const pendingMins = pendingSessions.value.reduce((s, p) => s + p.minutes, 0)
    const pendingCount = pendingSessions.value.length
    return {
      ...base,
      total_sessions: base.total_sessions + pendingCount,
      total_minutes: base.total_minutes + pendingMins,
      total_hours: Math.round(((base.total_minutes + pendingMins) / 60) * 100) / 100,
    }
  }

  function applyPendingToTechMetrics(base: TechnologyMetric[]): TechnologyMetric[] {
    if (!pendingSessions.value.length) return base
    const merged = base.map((tm) => ({ ...tm }))
    for (const ps of pendingSessions.value) {
      if (!ps.technology) continue
      const existing = merged.find((tm) => tm.technology?.id === ps.technology!.id)
      if (existing) {
        existing.total_minutes += ps.minutes
        existing.session_count += 1
        existing.last_studied_at = new Date().toISOString()
      } else {
        merged.push({
          technology: {
            id: ps.technology.id,
            name: ps.technology.name,
            color: ps.technology.color,
            slug: '',
            is_active: true,
          },
          total_minutes: ps.minutes,
          session_count: 1,
          last_studied_at: new Date().toISOString(),
        })
      }
    }
    return merged
  }

  const hasPending = computed(() => pendingSessions.value.length > 0)

  return {
    pendingSessions,
    sessionCountAtPendingStart,
    hasPending,
    mergeDailyWithPending,
    reconcilePending,
    addLocalTodaySession,
    clearPending,
    applyPendingToUserMetrics,
    applyPendingToTechMetrics,
  }
}
