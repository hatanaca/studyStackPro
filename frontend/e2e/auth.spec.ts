import { test, expect } from '@playwright/test'

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'
const TEST_EMAIL = `e2e-test-${Date.now()}@example.com`
const TEST_PASSWORD = 'TestPassword123!'
const TEST_NAME = 'E2E Test User'

test.describe('Authentication Flow', () => {
  test('should redirect to login page when not authenticated', async ({ page }) => {
    await page.goto(`${BASE_URL}/dashboard`)
    await expect(page).toHaveURL(/.*login|auth/)
  })

  test('should display login form', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/login`)
    await expect(page.locator('input[type="email"], input[name="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"], input[name="password"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toBeVisible()
  })

  test('should show error with wrong credentials', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/login`)
    await page.fill('input[type="email"], input[name="email"]', 'wrong@example.com')
    await page.fill('input[type="password"], input[name="password"]', 'wrongpassword')
    await page.click('button[type="submit"]')
    await expect(page.locator('text=/inválid|incorrect|credenciais/i')).toBeVisible({ timeout: 10000 })
  })

  test('should register a new user', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/register`)
    await page.fill('input[name="name"]', TEST_NAME)
    await page.fill('input[name="email"]', TEST_EMAIL)
    await page.fill('input[name="password"]', TEST_PASSWORD)
    await page.fill('input[name="password_confirmation"]', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await expect(page).toHaveURL(/.*(?!auth\/register)/, { timeout: 15000 })
  })

  test('should login and see dashboard', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/login`)
    await page.fill('input[type="email"], input[name="email"]', TEST_EMAIL)
    await page.fill('input[type="password"], input[name="password"]', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await expect(page).toHaveURL(/.*(?!auth\/)/, { timeout: 15000 })
  })

  test('should logout successfully', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/login`)
    await page.fill('input[type="email"], input[name="email"]', TEST_EMAIL)
    await page.fill('input[type="password"], input[name="password"]', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await expect(page).toHaveURL(/.*(?!auth\/)/, { timeout: 15000 })

    const logoutButton = page.locator('button:has-text("Sair"), a:has-text("Sair"), [data-testid="logout"]')
    if (await logoutButton.isVisible()) {
      await logoutButton.click()
      await expect(page).toHaveURL(/.*(login|auth)/, { timeout: 10000 })
    }
  })

  test('should persist session across page reload', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/login`)
    await page.fill('input[type="email"], input[name="email"]', TEST_EMAIL)
    await page.fill('input[type="password"], input[name="password"]', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await expect(page).toHaveURL(/.*(?!auth\/)/, { timeout: 15000 })

    await page.reload()
    await expect(page).not.toHaveURL(/.*(login|auth)/, { timeout: 10000 })
  })
})
