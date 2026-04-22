import axios from 'axios'

/** Raiz da API (sem /api/v1). Em dev com Vite, vazio → mesmo host + proxy /sanctum. */
function apiOrigin(): string {
  return String(import.meta.env.VITE_API_URL ?? '').replace(/\/+$/, '')
}

/**
 * Obtém cookie CSRF do Sanctum (necessário antes de POST login/register com credenciais).
 * Deve ser chamado com `withCredentials` (axios global ou pedido dedicado).
 */
export async function fetchSanctumCsrfCookie(): Promise<void> {
  const base = apiOrigin()
  const url = base ? `${base}/sanctum/csrf-cookie` : '/sanctum/csrf-cookie'
  await axios.get(url, { withCredentials: true })
}
