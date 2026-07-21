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

// Staging sits behind SiteGround's rate-based anti-bot layer, which challenges
// bursts of parallel requests from datacenter IPs. In CI, run staging
// serially (~human speed); production and local runs keep parallel workers.
const IS_STAGING_TARGET = BASE_URL.includes('staging');

export default defineConfig({
  testDir: './tests/e2e',
  // Warm-up: absorbs SiteGround's anti-bot JS challenge once and shares the
  // cleared cookies (storage state) with every test context.
  globalSetup: './tests/e2e/global-setup',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? (IS_STAGING_TARGET ? 1 : 2) : undefined,
  reporter: [['list'], ['html', { open: 'never' }]],

  use: {
    baseURL: BASE_URL,
    storageState: 'playwright/.cache/storage-state.json',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    // NOTE: deliberately no custom userAgent — non-browser UA strings are a
    // classic bot-detection trigger, and this suite must pass SiteGround's
    // anti-bot layer from CI datacenter IPs.
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
