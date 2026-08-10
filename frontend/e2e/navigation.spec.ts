import { test, expect } from '@playwright/test'

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'

test.describe('Navigation and Layout', () => {
  test.use({ storageState: 'e2e/.auth/nav-user.json' })

  test('should display sidebar navigation', async ({ page }) => {
    await page.goto(`${BASE_URL}/dashboard`)
    const sidebar = page.locator('aside.app-sidebar')
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
      const html = page.locator('html')
      const themeBefore = await html.getAttribute('data-theme')
      await themeToggle.click()
      await page.waitForTimeout(500)
      const themeAfter = await html.getAttribute('data-theme')
      expect(themeAfter).not.toBe(themeBefore)
    }
  })
})
