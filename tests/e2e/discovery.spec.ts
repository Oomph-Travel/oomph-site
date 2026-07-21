import { test, expect } from '@playwright/test';

/**
 * Discovery Call money path — render + structure only. No form submission and
 * no Calendly booking are ever performed.
 */
test.describe('/discovery-call/', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/discovery-call/', { waitUntil: 'domcontentloaded' });
  });

  test('intake form renders (or its explicit fallback)', async ({ page }) => {
    // Fluent Forms renders <form class="frm-fluent-form">; if the form row is
    // missing the template prints "The intake form is being set up."
    const form = page.locator('form.frm-fluent-form, .fluentform form');
    const fallback = page.getByText(/intake form is being set up/i);

    const hasForm = (await form.count()) > 0;
    const hasFallback = (await fallback.count()) > 0;
    expect(hasForm || hasFallback, 'neither the intake form nor its fallback rendered').toBeTruthy();

    if (hasForm) {
      await expect(form.first()).toBeVisible();
    }
  });

  test('booking section and Calendly area are present', async ({ page }) => {
    await expect(page.locator('#book')).toBeAttached();

    // Calendly renders the widget only when a booking URL is configured;
    // otherwise a placeholder note stands in. Exactly one should exist.
    const widget = page.locator('.calendly-inline-widget');
    const placeholder = page.locator('.oomph-calendly--placeholder');
    const total = (await widget.count()) + (await placeholder.count());
    expect(total, 'expected either a Calendly widget or its placeholder').toBeGreaterThan(0);
  });

  test('booking confirmation starts hidden', async ({ page }) => {
    const confirm = page.locator('[data-calendly-confirm]');
    if ((await confirm.count()) === 0) {
      test.skip(true, 'No Calendly configured — confirmation node absent.');
    }
    await expect(confirm).toBeHidden();
  });
});
