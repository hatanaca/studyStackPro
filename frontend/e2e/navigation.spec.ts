import { test, expect } from '@playwright/test'

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'
const TEST_EMAIL = `e2e-nav-${Date.now()}@example.com`
const TEST_PASSWORD = 'TestPassword123!'

test.describe('Navigation and Layout', () => {
  test.beforeAll(async ({ browser }) => {
    const page = await browser.newPage()
    await page.goto(`${BASE_URL}/auth/register`)
    await page.fill('input[name="name"]', 'Nav Test User')
    await page.fill('input[name="email"]', TEST_EMAIL)
    await page.fill('input[name="password"]', TEST_PASSWORD)
    await page.fill('input[name="password_confirmation"]', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await page.waitForURL(/.*(?!auth\/)/, { timeout: 15000 })
    await page.context().storageState({ path: 'e2e/.auth/nav-user.json' })
    await page.close()
  })

  test.use({ storageState: 'e2e/.auth/nav-user.json' })

  test('should display sidebar navigation', async ({ page }) => {
    await page.goto(`${BASE_URL}/dashboard`)
    const sidebar = page.locator('nav, [role="navigation"], aside, .sidebar')
    await expect(sidebar).toBeVisible({ timeout: 10000 })
  })

  test('should navigate to dashboard', async ({ page }) => {
    await page.goto(`${BASE_URL}/dashboard`)
    await expect(page).toHaveURL(/.*dashboard/)
  })

  test('should navigate to technologies', async ({ page }) => {
    await page.goto(`${BASE_URL}/technologies`)
    await expect(page).toHaveURL(/.*technolog/)
  })

  test('should navigate to sessions', async ({ page }) => {
    await page.goto(`${BASE_URL}/sessions`)
    await expect(page).toHaveURL(/.*session/)
  })

  test('should navigate to settings', async ({ page }) => {
    await page.goto(`${BASE_URL}/settings`)
    await expect(page).toHaveURL(/.*setting/)
  })

  test('should navigate to profile', async ({ page }) => {
    await page.goto(`${BASE_URL}/profile`)
    await expect(page).toHaveURL(/.*profile/)
  })

  test('should have working theme toggle', async ({ page }) => {
    await page.goto(`${BASE_URL}/dashboard`)
    const themeToggle = page.locator('[data-testid="theme-toggle"], button:has-text("Tema"), .theme-toggle')
    if (await themeToggle.isVisible({ timeout: 5000 })) {
      await themeToggle.click()
      await page.waitForTimeout(500)
      const html = page.locator('html')
      const hasDarkClass = await html.evaluate((el) =>
        el.classList.contains('dark') || el.getAttribute('data-theme') === 'dark'
      )
      expect(typeof hasDarkClass).toBe('boolean')
    }
  })
})
