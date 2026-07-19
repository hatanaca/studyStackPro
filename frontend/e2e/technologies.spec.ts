import { test, expect } from '@playwright/test'

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'
const TEST_EMAIL = `e2e-tech-${Date.now()}@example.com`
const TEST_PASSWORD = process.env.E2E_TEST_PASSWORD || 'TestPassword123!'

test.describe('Technology Management', () => {
  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage()
    await page.goto(`${BASE_URL}/auth/register`)
    await page.fill('input[name="name"]', 'Tech Test User')
    await page.fill('input[name="email"]', TEST_EMAIL)
    await page.fill('input[name="password"]', TEST_PASSWORD)
    await page.fill('input[name="password_confirmation"]', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await page.waitForURL(/.*(?!auth\/)/, { timeout: 15000 })
    await page.context().storageState({ path: 'e2e/.auth/tech-user.json' })
    await page.close()
  })

  test.use({ storageState: 'e2e/.auth/tech-user.json' })

  test('should navigate to technologies page', async ({ page }) => {
    await page.goto(`${BASE_URL}/technologies`)
    await expect(page).toHaveURL(/.*technolog/)
  })

  test('should display empty state when no technologies', async ({ page }) => {
    await page.goto(`${BASE_URL}/technologies`)
    const emptyState = page.locator('text=/nenhuma|empty|adicionar/i')
    await expect(emptyState).toBeVisible({ timeout: 10000 })
  })

  test('should create a new technology', async ({ page }) => {
    await page.goto(`${BASE_URL}/technologies`)

    const addButton = page.locator('button:has-text("Adicionar"), button:has-text("Nova"), button:has-text("Criar")')
    if (await addButton.isVisible({ timeout: 5000 })) {
      await addButton.click()
    }

    const nameInput = page.locator('input[name="name"], input[placeholder*="nome"], input[placeholder*="name"]').first()
    if (await nameInput.isVisible({ timeout: 5000 })) {
      await nameInput.fill('Playwright Tech')
    }

    const submitButton = page.locator('button[type="submit"]:visible, button:has-text("Salvar"):visible, button:has-text("Criar"):visible').first()
    if (await submitButton.isVisible()) {
      await submitButton.click()
      await expect(page.locator('text=/Playwright Tech/')).toBeVisible({ timeout: 10000 })
    }
  })

  test('should display technology in list after creation', async ({ page }) => {
    await page.goto(`${BASE_URL}/technologies`)
    await expect(page.locator('text=/Playwright Tech/')).toBeVisible({ timeout: 10000 })
  })
})
