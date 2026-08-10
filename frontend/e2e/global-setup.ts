import { execSync } from 'node:child_process'
import { mkdirSync } from 'node:fs'
import { chromium, type FullConfig } from '@playwright/test'

const PASSWORD = process.env.E2E_TEST_PASSWORD || 'TestPassword123!'

async function registerUser(baseURL: string, name: string, email: string, path: string) {
  const browser = await chromium.launch()
  const context = await browser.newContext({ storageState: { cookies: [], origins: [] } })
  const page = await context.newPage()
  try {
    await page.goto(`${baseURL}/register`)
    await page.fill('#reg-name', name)
    await page.fill('#reg-email', email)
    await page.fill('#reg-password', PASSWORD)
    await page.fill('#reg-password-confirm', PASSWORD)
    await page.click('button[type="submit"]')
    await page.waitForURL(/^(?!.*login|.*register)/, { timeout: 20000 })
    await context.storageState({ path })
  } finally {
    await context.close()
    await browser.close()
  }
}

async function globalSetup(config: FullConfig) {
  const baseURL = config.projects[0].use.baseURL || 'http://localhost:8080'

  // Diretório dos storage states usados pelos specs autenticados
  mkdirSync('e2e/.auth', { recursive: true })

  // Zera o rate limiter local (Redis, DB 1) para os testes não estourarem
  // throttle de register/login. No CI o cache é `array` (limite por request),
  // então este passo é no-op quando o Redis/compose não está disponível.
  try {
    const out = execSync(
      "docker compose exec redis sh -c 'redis-cli --no-auth-warning -a \"$REDIS_PASSWORD\" -n 1 FLUSHDB'",
      { stdio: 'pipe', timeout: 10000 }
    )
    console.log('[E2E globalSetup] FLUSHDB OK:', out.toString().trim())
  } catch (e) {
    console.log('[E2E globalSetup] FLUSHDB falhou (segue sem limpar):', String(e).split('\n')[0])
  }

  try {
    const response = await fetch(baseURL, { signal: AbortSignal.timeout(5000) })
    if (!response.ok) {
      console.warn(`[E2E] App not reachable at ${baseURL} (status ${response.status}). Tests requiring a running app will be skipped.`)
      return
    }
  } catch {
    console.warn(`[E2E] App not reachable at ${baseURL}. Tests requiring a running app will be skipped.`)
    return
  }

  // Cria os storage states (1 usuário por spec autenticado). Roda aqui, de forma
  // serial e antes de qualquer worker, para evitar corridas de escrita/rate limit.
  const users = [
    { name: 'Nav Test User', email: `e2e-nav-${Date.now()}@example.com`, path: 'e2e/.auth/nav-user.json' },
    { name: 'Tech Test User', email: `e2e-tech-${Date.now()}@example.com`, path: 'e2e/.auth/tech-user.json' },
    { name: 'Session Test User', email: `e2e-session-${Date.now()}@example.com`, path: 'e2e/.auth/session-user.json' },
  ]
  for (const u of users) {
    await registerUser(baseURL, u.name, u.email, u.path)
    console.log(`[E2E globalSetup] storageState criado: ${u.path}`)
  }
}

export default globalSetup
