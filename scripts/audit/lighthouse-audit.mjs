#!/usr/bin/env node
/**
 * Lighthouse mobile audit — one run per page type, serially.
 *
 *   npm run audit:lh                       # audits production
 *   OOMPH_BASE_URL=... npm run audit:lh    # audit another environment
 *
 * Reads the page-type inventory from tests/e2e/fixtures/routes.ts (single
 * source of truth), adds the newest journal post + soonest sailing discovered
 * from the live site, runs `npx lighthouse` (mobile emulation is Lighthouse's
 * default) against each, and writes:
 *   - scripts/audit/out/lighthouse.json   (full per-URL numbers)
 *   - a markdown results table on stdout  (paste into the audit doc)
 *
 * Pass bars (docs/seo-checklist.md #8): Perf >=95, A11y 100, BP 100, SEO 100,
 * LCP <2500ms, CLS <0.1. INP needs field data; TBT is reported as the lab proxy.
 */

import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const REPO = path.resolve(__dirname, '..', '..');
const OUT_DIR = path.join(__dirname, 'out');
const BASE = (process.env.OOMPH_BASE_URL ?? 'https://oomphtravel.com').replace(/\/$/, '');

// Page-type paths from the e2e route fixture (regex read — the fixture is TS).
const fixture = fs.readFileSync(path.join(REPO, 'tests/e2e/fixtures/routes.ts'), 'utf8');
const paths = [...fixture.matchAll(/path:\s*'([^']+)'/g)].map((m) => m[1]);

// Discover one representative singular for the two dynamic types.
async function discoverFirst(pagePath, linkRe) {
  try {
    const html = await (await fetch(BASE + pagePath)).text();
    const m = html.match(linkRe);
    return m ? new URL(m[1], BASE).pathname : null;
  } catch {
    return null;
  }
}
const journalPost = await discoverFirst(
  '/journal/',
  /class="oomph-card oomph-card--clickable[^"]*" href="([^"]+)"/
);
const sailing = await discoverFirst(
  '/group-cruises/',
  /class="oomph-card oomph-card--clickable oomph-sailing-card" href="([^"]+)"/
);
if (journalPost) paths.push(journalPost);
if (sailing) paths.push(sailing);

fs.mkdirSync(OUT_DIR, { recursive: true });
const results = [];

for (const p of paths) {
  const url = BASE + p;
  process.stderr.write(`Lighthouse: ${url} ... `);
  try {
    const raw = execFileSync(
      'npx',
      [
        'lighthouse',
        url,
        '--output=json',
        '--output-path=stdout',
        '--quiet',
        '--chrome-flags=--headless=new',
        '--only-categories=performance,accessibility,best-practices,seo',
      ],
      { cwd: REPO, encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 }
    );
    const lhr = JSON.parse(raw);
    const cat = (k) => Math.round((lhr.categories[k]?.score ?? 0) * 100);
    const num = (k) => lhr.audits[k]?.numericValue ?? null;
    results.push({
      path: p,
      performance: cat('performance'),
      accessibility: cat('accessibility'),
      bestPractices: cat('best-practices'),
      seo: cat('seo'),
      lcpMs: num('largest-contentful-paint') && Math.round(num('largest-contentful-paint')),
      cls: num('cumulative-layout-shift') && Number(num('cumulative-layout-shift').toFixed(3)),
      tbtMs: num('total-blocking-time') && Math.round(num('total-blocking-time')),
    });
    process.stderr.write('done\n');
  } catch (e) {
    results.push({ path: p, error: String(e.message ?? e).slice(0, 200) });
    process.stderr.write('FAILED\n');
  }
}

fs.writeFileSync(
  path.join(OUT_DIR, 'lighthouse.json'),
  JSON.stringify({ base: BASE, generatedAt: new Date().toISOString(), results }, null, 2)
);

// Markdown table.
const pass = (r) =>
  r.performance >= 95 && r.accessibility === 100 && r.bestPractices === 100 &&
  r.seo === 100 && r.lcpMs < 2500 && r.cls < 0.1;
console.log('| Page | Perf | A11y | BP | SEO | LCP (ms) | CLS | TBT (ms) | Pass |');
console.log('|---|---|---|---|---|---|---|---|---|');
for (const r of results) {
  if (r.error) {
    console.log(`| ${r.path} | — | — | — | — | — | — | — | ❌ error |`);
    continue;
  }
  console.log(
    `| ${r.path} | ${r.performance} | ${r.accessibility} | ${r.bestPractices} | ${r.seo} | ` +
      `${r.lcpMs} | ${r.cls} | ${r.tbtMs} | ${pass(r) ? '✅' : '⚠️'} |`
  );
}
