import { type FullConfig } from '@playwright/test'

async function globalSetup(config: FullConfig) {
  const baseURL = config.projects[0].use.baseURL || 'http://localhost:8080'

  try {
    const response = await fetch(baseURL, { signal: AbortSignal.timeout(5000) })
    if (!response.ok) {
      console.warn(`[E2E] App not reachable at ${baseURL} (status ${response.status}). Tests requiring a running app will be skipped.`)
    }
  } catch {
    console.warn(`[E2E] App not reachable at ${baseURL}. Tests requiring a running app will be skipped.`)
  }
}

export default globalSetup
