import { test, expect } from '@playwright/test'

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'

test.describe('Study Sessions Flow', () => {
  test.use({ storageState: 'e2e/.auth/session-user.json' })

  test('should navigate to sessions page', async ({ page }) => {
    await page.goto(`${BASE_URL}/sessions`)
    await expect(page).toHaveURL(/.*session/)
  })

  test('should display session list or empty state', async ({ page }) => {
    await page.goto(`${BASE_URL}/sessions`)
    const content = page.locator('main')
    await expect(content).toBeVisible({ timeout: 10000 })
    const emptyOrList = page.locator('h3:has-text("Nenhuma sessão registrada"), .session-list, [data-testid="session-list"]').first()
    await expect(emptyOrList).toBeVisible({ timeout: 10000 })
  })

  test('should have new session button', async ({ page }) => {
    await page.goto(`${BASE_URL}/sessions`)
    const startButton = page.locator('button:has-text("Nova sessão")')
    await expect(startButton).toBeVisible({ timeout: 10000 })
  })
})
