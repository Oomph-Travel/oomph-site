import { test, expect } from '@playwright/test';
import { getJsonLdTypes } from './helpers/schema';

/**
 * Group Cruises / Distinctive Voyages. Covers the archive browse surface and a
 * single sailing page, including a regression guard that internal TLN reference
 * numbers (voyage / US group / CA group) never leak into the rendered page.
 */
test.describe('/group-cruises/ archive', () => {
  test('shows line + region filters and at least one sailing card', async ({ page }) => {
    await page.goto('/group-cruises/', { waitUntil: 'domcontentloaded' });

    const filters = page.locator('form.oomph-cruise-filters');
    await expect(filters).toBeVisible();
    await expect(filters.locator('select[name="line"]')).toBeVisible();
    await expect(filters.locator('select[name="region"]')).toBeVisible();

    const cards = page.locator('a.oomph-sailing-card');
    const count = await cards.count();
    if (count === 0) {
      test.skip(true, 'No published sailings on the archive to assert against.');
    }
    await expect(cards.first()).toBeVisible();
  });

  test('sailing-type filter separates hosted from amenity departures', async ({ page }) => {
    await page.goto('/group-cruises/', { waitUntil: 'domcontentloaded' });

    const typeSelect = page.locator('form.oomph-cruise-filters select[name="type"]');
    await expect(typeSelect).toBeVisible();

    // Hosted view: every card shown must be a hosted/DV card.
    await page.goto('/group-cruises/?type=hosted', { waitUntil: 'domcontentloaded' });
    const hosted = page.locator('a.oomph-sailing-card');
    if ((await hosted.count()) > 0) {
      await expect(page.locator('a.oomph-sailing-card--amenity')).toHaveCount(0);
    }

    // Amenity view: every card shown must be an amenity card.
    await page.goto('/group-cruises/?type=amenity', { waitUntil: 'domcontentloaded' });
    const amenity = page.locator('a.oomph-sailing-card');
    const amenityCount = await amenity.count();
    if (amenityCount === 0) {
      test.skip(true, 'No amenity departures published yet.');
    }
    await expect(page.locator('a.oomph-sailing-card--amenity')).toHaveCount(amenityCount);
  });
});

test.describe('single DV sailing', () => {
  test('renders, carries Event schema, and leaks no internal refs', async ({ page }) => {
    await page.goto('/group-cruises/', { waitUntil: 'domcontentloaded' });
    const firstCard = page.locator('a.oomph-sailing-card').first();
    if ((await firstCard.count()) === 0) {
      test.skip(true, 'No published sailings to open.');
    }

    await firstCard.click();
    await page.waitForLoadState('domcontentloaded');

    // Renders a visible H1.
    await expect(page.locator('h1').first()).toBeVisible();

    // Event schema (DV or hosted both emit Event).
    const types = await getJsonLdTypes(page);
    expect(types, `sailing schema (found: ${types.join(', ')})`).toContain('Event');

    // Regression guard: internal reference fields are stored but must never
    // render. These labels/tokens should not appear in visible text.
    const bodyText = (await page.locator('body').innerText()).toLowerCase();
    for (const leak of ['voyage number', 'group number', 'us group', 'ca group']) {
      expect(bodyText, `internal ref "${leak}" leaked into the page`).not.toContain(leak);
    }

    // If a shore-event flyer link exists, it should open in a new tab safely.
    const flyer = page.locator('.oomph-dv-ticket__flyer a');
    if ((await flyer.count()) > 0) {
      await expect(flyer.first()).toHaveAttribute('rel', /noopener/);
      await expect(flyer.first()).toHaveAttribute('target', '_blank');
    }
  });
});
