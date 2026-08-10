import { test, expect } from '@playwright/test'

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080'
const TEST_EMAIL = `e2e-test-${Date.now()}@example.com`
const TEST_PASSWORD = process.env.E2E_TEST_PASSWORD || 'TestPassword123!'
const TEST_NAME = 'E2E Test User'

// Os testes dependem da ordem (register cria o usuário usado pelos logins seguintes)
test.describe.configure({ mode: 'serial' })

test.describe('Authentication Flow', () => {
  test('should redirect to login page when not authenticated', async ({ page }) => {
    await page.goto(`${BASE_URL}/dashboard`)
    await expect(page).toHaveURL(/.*login|auth/)
  })

  test('should display login form', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`)
    await expect(page.locator('input[type="email"], input[name="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"], input[name="password"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toBeVisible()
  })

  test('should show error with wrong credentials', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`)
    await page.fill('#login-email', 'wrong@example.com')
    await page.fill('#login-password', 'wrongpassword')
    await page.click('button[type="submit"]')
    await expect(page.locator('text=/inválid|incorrect|credenciais/i')).toBeVisible({ timeout: 10000 })
  })

  test('should register a new user', async ({ page }) => {
    await page.goto(`${BASE_URL}/register`)
    await page.fill('#reg-name', TEST_NAME)
    await page.fill('#reg-email', TEST_EMAIL)
    await page.fill('#reg-password', TEST_PASSWORD)
    await page.fill('#reg-password-confirm', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await expect(page).not.toHaveURL(/login|register/, { timeout: 15000 })
  })

  test('should login and see dashboard', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`)
    await page.fill('#login-email', TEST_EMAIL)
    await page.fill('#login-password', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await expect(page).not.toHaveURL(/login|register/, { timeout: 15000 })
  })

  test('should logout successfully', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`)
    await page.fill('#login-email', TEST_EMAIL)
    await page.fill('#login-password', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await expect(page).not.toHaveURL(/login|register/, { timeout: 15000 })

    const logoutButton = page.locator('.app-sidebar__logout, button:has-text("Sair"), [data-testid="logout"]').first()
    await logoutButton.click()
    // A app limpa a sessão localmente (token removido); o guard redireciona na próxima navegação
    await expect.poll(() =>
      page.evaluate(() => localStorage.getItem('studytrack_token'))
    ).toBeNull()
  })

  test('should persist session across page reload', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`)
    await page.fill('#login-email', TEST_EMAIL)
    await page.fill('#login-password', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await expect(page).not.toHaveURL(/login|register/, { timeout: 15000 })

    await page.reload()
    await expect(page).not.toHaveURL(/.*(login|auth)/, { timeout: 10000 })
  })
})
