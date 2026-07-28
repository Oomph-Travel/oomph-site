#!/usr/bin/env node
/**
 * Lighthouse mobile audit — one run per page type, serially.
 *
 *   npm run audit:lh                       # audits production, median of 5 runs
 *   OOMPH_BASE_URL=... npm run audit:lh    # audit another environment
 *   LH_RUNS=1 npm run audit:lh             # single run (fast, but not evidence)
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
// Lighthouse on a live site is noisy — bandwidth, third-party origins and
// server cache state all move the number. Report the median of several runs
// and the spread alongside it. Override with LH_RUNS=1 for a quick look.
const RUNS = Math.max(1, Number(process.env.LH_RUNS ?? 5));

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

const median = (xs) => xs.slice().sort((a, b) => a - b)[Math.floor(xs.length / 2)];

function runOnce(url) {
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
  return {
    performance: cat('performance'),
    accessibility: cat('accessibility'),
    bestPractices: cat('best-practices'),
    seo: cat('seo'),
    lcpMs: num('largest-contentful-paint') && Math.round(num('largest-contentful-paint')),
    cls: num('cumulative-layout-shift') && Number(num('cumulative-layout-shift').toFixed(3)),
    tbtMs: num('total-blocking-time') && Math.round(num('total-blocking-time')),
  };
}

for (const p of paths) {
  const url = BASE + p;
  process.stderr.write(`Lighthouse: ${url} (${RUNS} runs) `);
  const trials = [];
  let error = null;
  for (let i = 0; i < RUNS; i++) {
    try {
      trials.push(runOnce(url));
      process.stderr.write('.');
    } catch (e) {
      error = String(e.message ?? e).slice(0, 200);
      process.stderr.write('x');
    }
  }
  if (!trials.length) {
    results.push({ path: p, runs: RUNS, error: error ?? 'all runs failed' });
    process.stderr.write(' FAILED\n');
    continue;
  }
  const pick = (k) => median(trials.map((t) => t[k]).filter((v) => v !== null && v !== undefined));
  const lcps = trials.map((t) => t.lcpMs).filter(Boolean);
  results.push({
    path: p,
    runs: trials.length,
    performance: pick('performance'),
    accessibility: pick('accessibility'),
    bestPractices: pick('bestPractices'),
    seo: pick('seo'),
    lcpMs: pick('lcpMs'),
    cls: pick('cls'),
    tbtMs: pick('tbtMs'),
    // Spread is not decoration. On 2026-07-25 this page set measured 92 and 65
    // on consecutive runs of the same URL; a single number invited a wrong
    // conclusion about a regression. If min and max straddle a decision, the
    // run is not evidence.
    perfRange: [Math.min(...trials.map((t) => t.performance)), Math.max(...trials.map((t) => t.performance))],
    lcpRangeMs: lcps.length ? [Math.min(...lcps), Math.max(...lcps)] : null,
    ...(error ? { partialError: error } : {}),
  });
  process.stderr.write(' done\n');
}

fs.writeFileSync(
  path.join(OUT_DIR, 'lighthouse.json'),
  JSON.stringify({ base: BASE, generatedAt: new Date().toISOString(), results }, null, 2)
);

// Markdown table.
const pass = (r) =>
  r.performance >= 95 && r.accessibility === 100 && r.bestPractices === 100 &&
  r.seo === 100 && r.lcpMs < 2500 && r.cls < 0.1;
console.log(`Median of ${RUNS} run(s) per page; ranges show run-to-run spread.\n`);
console.log('| Page | Perf (range) | A11y | BP | SEO | LCP ms (range) | CLS | TBT (ms) | Pass |');
console.log('|---|---|---|---|---|---|---|---|---|');
for (const r of results) {
  if (r.error) {
    console.log(`| ${r.path} | — | — | — | — | — | — | — | ❌ error |`);
    continue;
  }
  const pr = r.perfRange && r.perfRange[0] !== r.perfRange[1] ? ` (${r.perfRange[0]}–${r.perfRange[1]})` : '';
  const lr = r.lcpRangeMs && r.lcpRangeMs[0] !== r.lcpRangeMs[1] ? ` (${r.lcpRangeMs[0]}–${r.lcpRangeMs[1]})` : '';
  console.log(
    `| ${r.path} | ${r.performance}${pr} | ${r.accessibility} | ${r.bestPractices} | ${r.seo} | ` +
      `${r.lcpMs}${lr} | ${r.cls} | ${r.tbtMs} | ${pass(r) ? '✅' : '⚠️'} |`
  );
}
