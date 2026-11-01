import { defineConfig, devices } from '@playwright/test';

// The Laravel app is started by `webServer` below with a fresh SQLite database seeded by
// Database\Seeders\E2ESeeder (deterministic data). Frontend assets must be built first (`npm run build`).
const PORT = Number(process.env.E2E_PORT ?? 8001);
const baseURL = process.env.E2E_BASE_URL ?? `http://127.0.0.1:${PORT}`;

export default defineConfig({
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
  use: {
    baseURL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
  webServer: process.env.E2E_BASE_URL
    ? undefined
    : {
        command: `sh ./serve.sh ${PORT}`,
        url: `${baseURL}/up`,
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
      },
});
