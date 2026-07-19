import * as Sentry from '@sentry/vue'

/**
 * Callback para .catch() que captura erros no Sentry.
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

    Sentry.captureException(error instanceof Error ? error : new Error(String(error)), {
      tags: { handled_error: context },
    })
  }
}
