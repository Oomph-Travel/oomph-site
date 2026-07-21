import { test, expect } from '@playwright/test';

/**
 * Cabin Quiz money path. Drives the client-side flow intro → questions → gate,
 * then synthesizes the Fluent Forms success event to reveal a result. No real
 * email is ever submitted (the gate form is never filled or sent).
 * Hooks: page-trip-quiz.php + assets/js/cabin-quiz.js.
 */
test.describe('/trip-quiz/', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/trip-quiz/', { waitUntil: 'domcontentloaded' });
  });

  test('advances intro → questions → gate without submitting', async ({ page }) => {
    const intro = page.locator('[data-quiz-step="intro"]');
    const questions = page.locator('[data-quiz-step="questions"]');
    const gate = page.locator('[data-quiz-step="gate"]');

    await expect(intro).toBeVisible();
    await page.locator('[data-quiz-start]').click();
    await expect(questions).toBeVisible();

    // Answer each question by clicking the first option of whichever fieldset
    // is currently shown. Bounded loop so a broken flow fails fast.
    for (let i = 0; i < 7; i++) {
      if (await gate.isVisible()) break;
      const currentOption = page
        .locator('[data-quiz-q]:visible .oomph-quiz__opt')
        .first();
      await expect(currentOption).toBeVisible();
      await currentOption.click();
    }

    await expect(gate).toBeVisible();
    // The gate holds the Cabin Quiz Fluent form, or its explicit fallback.
    const gateForm = gate.locator('form.frm-fluent-form, .fluentform form');
    const gateFallback = gate.getByText(/results form is being set up/i);
    const ok = (await gateForm.count()) > 0 || (await gateFallback.count()) > 0;
    expect(ok, 'gate should hold the quiz form or its fallback').toBeTruthy();
  });

  test('synthesized form success reveals a result profile', async ({ page }) => {
    await page.locator('[data-quiz-start]').click();
    const gate = page.locator('[data-quiz-step="gate"]');
    for (let i = 0; i < 7; i++) {
      if (await gate.isVisible()) break;
      await page.locator('[data-quiz-q]:visible .oomph-quiz__opt').first().click();
    }
    await expect(gate).toBeVisible();

    // Bridge the same jQuery event Fluent Forms fires on success — no network,
    // no real submission. cabin-quiz.js listens for this to reveal results.
    const hasJQuery = await page.evaluate(() => typeof (window as any).jQuery === 'function');
    if (!hasJQuery) {
      test.skip(true, 'jQuery not present — cannot synthesize the success event.');
    }
    await page.evaluate(() => {
      (window as any).jQuery(document.body).trigger('fluentform_submission_success');
    });

    const results = page.locator('[data-quiz-step="results"]');
    await expect(results).toBeVisible();
    await expect(page.locator('.oomph-quiz__result[data-profile]:visible').first()).toBeVisible();
  });
});
