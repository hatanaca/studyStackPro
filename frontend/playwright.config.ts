import { defineConfig } from '@playwright/test'

const baseURL = process.env.BASE_URL || 'http://localhost:8080'

export default defineConfig({
  testDir: './e2e',
  globalSetup: './e2e/global-setup.ts',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  // Workers serializados: a app aplica rate limit por IP (throttle:60,1) e o
  // globalSetup já registra 3 usuários — paralelizar satura o limite local.
  workers: 1,
  reporter: 'html',
  timeout: 30000,
  use: {
    baseURL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { browserName: 'chromium' },
    },
  ],
  // Only start dev server when not in CI (CI expects app to be running already)
  webServer: process.env.CI
    ? undefined
    : {
        command: 'npm run dev',
        port: 5173,
        reuseExistingServer: true,
        timeout: 30000,
      },
})
