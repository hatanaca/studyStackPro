import { test, expect } from '@playwright/test'

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'
const TEST_EMAIL = `e2e-session-${Date.now()}@example.com`
const TEST_PASSWORD = process.env.E2E_TEST_PASSWORD || 'TestPassword123!'

test.describe('Study Sessions Flow', () => {
  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage()
    await page.goto(`${BASE_URL}/auth/register`)
    await page.fill('input[name="name"]', 'Session Test User')
    await page.fill('input[name="email"]', TEST_EMAIL)
    await page.fill('input[name="password"]', TEST_PASSWORD)
    await page.fill('input[name="password_confirmation"]', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await page.waitForURL(/.*(?!auth\/)/, { timeout: 15000 })

    await page.goto(`${BASE_URL}/technologies`)
    const addButton = page.locator('button:has-text("Adicionar"), button:has-text("Nova"), button:has-text("Criar")')
    if (await addButton.isVisible({ timeout: 5000 })) {
      await addButton.click()
    }
    const nameInput = page.locator('input[name="name"], input[placeholder*="nome"]').first()
    if (await nameInput.isVisible({ timeout: 5000 })) {
      await nameInput.fill('Test Tech for Sessions')
    }
    const submitButton = page.locator('button[type="submit"]:visible, button:has-text("Salvar"):visible').first()
    if (await submitButton.isVisible()) {
      await submitButton.click()
      await page.waitForTimeout(2000)
    }

    await page.context().storageState({ path: 'e2e/.auth/session-user.json' })
    await page.close()
  })

  test.use({ storageState: 'e2e/.auth/session-user.json' })

  test('should navigate to sessions page', async ({ page }) => {
    await page.goto(`${BASE_URL}/sessions`)
    await expect(page).toHaveURL(/.*session/)
  })

  test('should display session list or empty state', async ({ page }) => {
    await page.goto(`${BASE_URL}/sessions`)
    const content = page.locator('text=/sessão|session|nenhuma|empty/i')
    await expect(content).toBeVisible({ timeout: 10000 })
  })

  test('should have start focus mode button', async ({ page }) => {
    await page.goto(`${BASE_URL}/sessions`)
    const startButton = page.locator('button:has-text("Iniciar"), button:has-text("Foco"), button:has-text("Start")')
    await expect(startButton).toBeVisible({ timeout: 10000 })
  })
})
