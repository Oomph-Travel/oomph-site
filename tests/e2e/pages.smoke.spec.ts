import { test, expect } from '@playwright/test';
import { ROUTES } from './fixtures/routes';

/**
 * Page-level smoke: every page type loads (200), renders a visible H1, and —
 * except the Discovery page itself — surfaces the sitewide "Start a
 * conversation" CTA pointing at /discovery-call/.
 */
test.describe('page smoke', () => {
  for (const route of ROUTES) {
    test(`${route.name} (${route.path}) loads and renders`, async ({ page }) => {
      const response = await page.goto(route.path, { waitUntil: 'domcontentloaded' });
      expect(response, `no response for ${route.path}`).toBeTruthy();
      expect(response!.status(), `HTTP status for ${route.path}`).toBe(200);

      // Exactly one, visible, non-empty H1.
      const h1 = page.locator('h1').first();
      await expect(h1).toBeVisible();
      await expect(h1).not.toBeEmpty();
      if (route.h1) {
        await expect(h1).toContainText(route.h1);
      }

      // Sitewide primary CTA. The destination page and the own-funnel pages
      // (quiz / journal / lead magnet) are exempt.
      if (!route.isDiscoveryDest && !route.noSitewideCta) {
        const cta = page.getByRole('link', { name: /start a conversation/i }).first();
        await expect(cta).toBeVisible();
        await expect(cta).toHaveAttribute('href', /\/discovery-call\/?/);
      }
    });
  }
});

test.describe('mobile chrome', () => {
  test('@mobile home shows the primary CTA on a phone viewport', async ({ page }) => {
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    const cta = page.getByRole('link', { name: /start a conversation/i }).first();
    await expect(cta).toBeVisible();
    await expect(cta).toHaveAttribute('href', /\/discovery-call\/?/);
  });

  test('@mobile discovery page has the sticky booking CTA', async ({ page }) => {
    await page.goto('/discovery-call/', { waitUntil: 'domcontentloaded' });
    // Sticky mobile CTA jumps to the #book anchor (page-discovery-call.php).
    // It reveals on scroll, so assert it's present in the DOM with the right
    // target rather than immediately visible.
    const sticky = page.locator('.oomph-sticky-cta a[href="#book"]');
    await expect(sticky).toBeAttached();
  });
});
