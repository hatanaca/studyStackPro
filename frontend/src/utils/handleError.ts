/**
 * Callback para .catch() que loga o erro em vez de engoli-lo silenciosamente.
 *
 * Em produção, tenta capturar no Sentry (import dinâmico).
 * Em desenvolvimento, usa console.warn.
 *
 * Uso:
 *   apiCall().catch(handleError('contexto'))
 *   await promise.catch(handleError('tag'))
 */
export function handleError(context: string): (error: unknown) => void {
  return (error: unknown) => {
    const message = error instanceof Error ? error.message : String(error)

    if (import.meta.env.DEV) {
      console.warn(`[handleError] ${context}:`, message)
      return
    }

    // Produção: tenta capturar no Sentry sem quebrar se o chunk não carregar
    import('@sentry/vue')
      .then((Sentry) => {
        Sentry.captureException(error instanceof Error ? error : new Error(String(error)), {
          tags: { handled_error: context },
        })
      })
      .catch(() => {
        // fallback: console silencioso — não podemos fazer mais nada
      })
  }
}
