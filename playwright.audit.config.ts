import { defineConfig, devices } from '@playwright/test';

/**
 * One-off audit config (WCAG/axe pass) — separate from the smoke suite so
 * `npx playwright test` behavior is unchanged. Run via:
 *   npm run audit:a11y            (defaults to production)
 *   OOMPH_BASE_URL=... npm run audit:a11y
 */

const BASE_URL = process.env.OOMPH_BASE_URL ?? 'https://oomphtravel.com';

export default defineConfig({
  testDir: './tests/audit',
  globalSetup: './tests/e2e/global-setup',
  fullyParallel: false,
  workers: 1, // serial — SiteGround anti-bot politeness
  retries: 0,
  reporter: [['list']],

  use: {
    baseURL: BASE_URL,
    storageState: 'playwright/.cache/storage-state.json',
  },

  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'] } },
    { name: 'mobile', use: { ...devices['Pixel 5'] } },
  ],
});
