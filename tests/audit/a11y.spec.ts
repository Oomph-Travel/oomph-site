import { test } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import fs from 'node:fs';
import path from 'node:path';
import { ROUTES } from '../e2e/fixtures/routes';

/**
 * WCAG 2.1 A/AA sweep (contrast included) via axe-core, per page type, on
 * desktop + mobile viewports. This is an AUDIT: it records violations to
 * scripts/audit/out/a11y/*.json for the audit doc instead of failing the run —
 * the deliverable is the report.
 */

const OUT_DIR = path.resolve(__dirname, '..', '..', 'scripts', 'audit', 'out', 'a11y');

test.beforeAll(() => {
  fs.mkdirSync(OUT_DIR, { recursive: true });
});

for (const route of ROUTES) {
  test(`axe WCAG A/AA: ${route.name} (${route.path})`, async ({ page }, testInfo) => {
    await page.goto(route.path, { waitUntil: 'load' });

    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze();

    const slug = route.path.replace(/\W+/g, '-').replace(/^-|-$/g, '') || 'home';
    const file = path.join(OUT_DIR, `${slug}.${testInfo.project.name}.json`);
    fs.writeFileSync(
      file,
      JSON.stringify(
        {
          path: route.path,
          project: testInfo.project.name,
          violations: results.violations.map((v) => ({
            id: v.id,
            impact: v.impact,
            help: v.help,
            nodes: v.nodes.slice(0, 5).map((n) => n.target.join(' ')),
            nodeCount: v.nodes.length,
          })),
        },
        null,
        2
      )
    );

    if (results.violations.length) {
      console.log(
        `⚠️  ${route.path} [${testInfo.project.name}]: ` +
          results.violations.map((v) => `${v.id}(${v.impact},${v.nodes.length})`).join(', ')
      );
    }
  });
}
