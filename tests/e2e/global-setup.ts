import { chromium, FullConfig } from '@playwright/test';
import fs from 'fs';
import path from 'path';

/**
 * Warm-up pass before the suite runs.
 *
 * SiteGround's anti-bot layer sometimes greets datacenter IPs (e.g. GitHub
 * Actions runners) with an HTTP 202 JS challenge instead of the page. The
 * challenge sets a cookie and reloads; a browser that waits it out gets
 * normal 200s afterwards. This setup visits the homepage until it sees a
 * real page (or gives up), then saves the browser storage state so every
 * test context starts with the already-cleared cookies — one challenge for
 * the whole suite instead of one per test.
 */

export const STORAGE_STATE = path.join(
  __dirname,
  '..',
  '..',
  'playwright',
  '.cache',
  'storage-state.json'
);

export default async function globalSetup(_config: FullConfig): Promise<void> {
  const baseURL = process.env.OOMPH_BASE_URL ?? 'https://staging2.oomphtravel.com';
  const browser = await chromium.launch();
  const page = await browser.newPage();

  let cleared = false;
  for (let attempt = 1; attempt <= 6; attempt++) {
    try {
      const response = await page.goto(baseURL, { waitUntil: 'domcontentloaded' });
      const status = response?.status() ?? 0;
      if (status === 200 && (await page.locator('h1').count()) > 0) {
        cleared = true;
        break;
      }
      // Give the challenge time to run its JS, set its cookie, and reload.
      await page.waitForTimeout(5000);
      await page.waitForLoadState('domcontentloaded').catch(() => {});
      if ((await page.locator('h1').count()) > 0) {
        cleared = true;
        break;
      }
    } catch {
      await page.waitForTimeout(3000);
    }
  }

  fs.mkdirSync(path.dirname(STORAGE_STATE), { recursive: true });
  await page.context().storageState({ path: STORAGE_STATE });
  await browser.close();

  if (!cleared) {
    // Don't abort — tests still run and report precise failures; this just
    // flags that the bot wall never cleared during warm-up.
    console.warn(
      `[global-setup] Could not confirm a clean 200 from ${baseURL} after 6 attempts — the suite may hit the bot challenge.`
    );
  }
}
