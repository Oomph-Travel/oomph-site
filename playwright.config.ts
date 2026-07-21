import { defineConfig, devices } from '@playwright/test';

/**
 * E2E smoke tests for oomphtravel.com.
 *
 * Target is an already-deployed site (staging by default), so there is no
 * local webServer — tests hit BASE_URL over the network. Override the target
 * with OOMPH_BASE_URL, e.g.:
 *   OOMPH_BASE_URL=http://oomph-local.local npx playwright test
 *   OOMPH_BASE_URL=https://oomphtravel.com   npx playwright test
 *
 * The suite never submits a real form — see docs/testing.md.
 */

const BASE_URL = process.env.OOMPH_BASE_URL ?? 'https://staging2.oomphtravel.com';

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 2 : undefined,
  reporter: [['list'], ['html', { open: 'never' }]],

  use: {
    baseURL: BASE_URL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    // A polite, identifiable UA so these hits are easy to spot in logs.
    userAgent: 'OomphE2E/1.0 (+Playwright smoke tests)',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      // Mobile viewport — runs only @mobile-tagged specs (sticky CTA / mobile nav).
      name: 'mobile-chrome',
      use: { ...devices['Pixel 5'] },
      grep: /@mobile/,
    },
  ],
});
