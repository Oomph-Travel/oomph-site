import { test, expect } from '@playwright/test';

/**
 * Link-in-bio page — /links/ (page-links.php).
 *
 * The single destination the Instagram profile link points at. The generic
 * page-smoke and schema specs cover it via the route fixture; this spec pins
 * the page-specific contract:
 *
 *   - the template actually bound (rendered .oomph-links__inner, not the
 *     blank default page template)
 *   - noindex, follow on every robots tag. NOTE: two robots tags is the
 *     measured reality on both environments — WP core's wp_robots output plus
 *     Rank Math's own tag (which appends its max-* preview directives). The
 *     directives agree, and under noindex the max-* hints are moot, so the
 *     invariant asserted here is "no tag permits indexing" rather than an
 *     exact tag count.
 *   - the featured Journal card (auto-follows the newest post)
 *   - the one primary CTA, pointing at /discovery-call/ (R1)
 *   - every row / footer link resolves — this page exists for one referrer
 *     and a dead row is a dead end for exactly the audience Eric sent here
 */
test.describe('/links/ link-in-bio', () => {
  test('template binds and renders the link stack', async ({ page }) => {
    const response = await page.goto('/links/', { waitUntil: 'domcontentloaded' });
    expect(response!.status()).toBe(200);

    await expect(page.locator('.oomph-links__inner')).toBeVisible();
    // The quiet rows — the page's reason to exist.
    expect(await page.locator('a.oomph-links__row').count()).toBeGreaterThan(0);
  });

  test('is noindex, follow on every robots tag', async ({ page }) => {
    await page.goto('/links/', { waitUntil: 'domcontentloaded' });

    const robots = page.locator('meta[name="robots"]');
    const count = await robots.count();
    expect(count, 'at least one robots meta').toBeGreaterThan(0);

    for (let i = 0; i < count; i++) {
      const content = (await robots.nth(i).getAttribute('content')) ?? '';
      expect(content, `robots tag ${i + 1}/${count}: "${content}"`).toMatch(/\bnoindex\b/);
      expect(content, `robots tag ${i + 1}/${count}: "${content}"`).toMatch(/\bfollow\b/);
    }
  });

  test('features the newest Journal post as a card', async ({ page }) => {
    await page.goto('/links/', { waitUntil: 'domcontentloaded' });

    const feature = page.locator('article.oomph-links__feature');
    if ((await feature.count()) === 0) {
      test.skip(true, 'No published Journal post to feature.');
    }

    await expect(feature).toBeVisible();
    const link = feature.locator('a.oomph-links__feature-link');
    await expect(link).toHaveAttribute('href', /.+/);
    await expect(link).not.toBeEmpty();
  });

  test('carries the primary CTA to /discovery-call/', async ({ page }) => {
    await page.goto('/links/', { waitUntil: 'domcontentloaded' });

    const cta = page.locator('.oomph-links__cta a.oomph-btn--primary');
    await expect(cta).toBeVisible();
    await expect(cta).toHaveAttribute('href', /\/discovery-call\/?/);
    await expect(cta).toContainText(/start a conversation/i);
  });

  test('every row and footer link resolves', async ({ page }) => {
    await page.goto('/links/', { waitUntil: 'domcontentloaded' });

    const hrefs = await page
      .locator('a.oomph-links__row, .oomph-links__more a')
      .evaluateAll((els) => els.map((el) => (el as HTMLAnchorElement).href));
    expect(hrefs.length).toBeGreaterThan(0);

    // Serially, and through the page's request context (shares the anti-bot
    // clearance cookies) — see the SiteGround note in playwright.config.ts.
    for (const href of hrefs) {
      const res = await page.request.get(href);
      expect(res.status(), `${href} resolves`).toBeLessThan(400);
    }
  });
});

test.describe('mobile chrome', () => {
  test('@mobile /links/ keeps the sticky CTA reachable', async ({ page }) => {
    await page.goto('/links/', { waitUntil: 'domcontentloaded' });
    const sticky = page.locator('.oomph-sticky-cta a[href*="/discovery-call/"]');
    await expect(sticky).toBeAttached();
  });
});
