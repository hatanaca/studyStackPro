import { test, expect } from '@playwright/test'

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'

test.describe('Technology Management', () => {
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

    // Empty state ou botão de ação — ambos abrem o mesmo dialog
    const addButton = page.locator('button:has-text("Nova tecnologia")').first()
    await addButton.click()
    await page.locator('#tech-name').fill('Playwright Tech')
    await page.locator('form.technology-form button[type="submit"]').click()
    await expect(page.locator('text=/Playwright Tech/')).toBeVisible({ timeout: 10000 })
  })

  test('should display technology in list after creation', async ({ page }) => {
    await page.goto(`${BASE_URL}/technologies`)
    await expect(page.locator('text=/Playwright Tech/')).toBeVisible({ timeout: 10000 })
  })
})
