#!/usr/bin/env node
/**
 * 404 / redirect map for the audit doc.
 *
 *   npm run audit:links                     # audits production
 *   OOMPH_BASE_URL=... npm run audit:links  # another environment
 *
 * 1. Every URL in sitemap_index.xml (and child sitemaps) must return 200.
 * 2. Every same-host link found on the page-type pages must resolve (following
 *    redirects) to 200.
 * 3. Canonical redirects behave: http→https, www→apex (or the site's actual
 *    direction), and a garbage URL returns a genuine 404.
 *
 * Serial with a small delay — politeness toward SiteGround's anti-bot layer.
 * Writes scripts/audit/out/links.json and a markdown summary to stdout.
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const REPO = path.resolve(__dirname, '..', '..');
const OUT_DIR = path.join(__dirname, 'out');
const BASE = (process.env.OOMPH_BASE_URL ?? 'https://oomphtravel.com').replace(/\/$/, '');
const HOST = new URL(BASE).host;

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const UA = { 'user-agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36', connection: 'close' };

// fetch with retries — the server closes idle keep-alive sockets between our
// politeness-delayed serial requests, which surfaces as UND_ERR_SOCKET.
async function safeFetch(url, opts = {}) {
  let lastErr;
  for (let i = 0; i < 3; i++) {
    try {
      return await fetch(url, { ...opts, headers: { ...UA, ...(opts.headers ?? {}) } });
    } catch (e) {
      lastErr = e;
      await sleep(2000 * (i + 1));
    }
  }
  throw lastErr;
}

async function status(url, { follow = true } = {}) {
  await sleep(350);
  const res = await safeFetch(url, { redirect: follow ? 'follow' : 'manual' });
  return { status: res.status, finalUrl: res.url, location: res.headers.get('location') };
}

// ---------- 1. Sitemap URLs ----------
const locRe = /<loc>([^<]+)<\/loc>/g;
const indexXml = await (await safeFetch(`${BASE}/sitemap_index.xml`)).text();
const childSitemaps = [...indexXml.matchAll(locRe)].map((m) => m[1]);
const sitemapUrls = new Set();
for (const sm of childSitemaps) {
  const xml = await (await safeFetch(sm)).text();
  for (const m of xml.matchAll(locRe)) sitemapUrls.add(m[1]);
}

const sitemapResults = [];
for (const url of sitemapUrls) {
  try {
    const r = await status(url, { follow: false });
    sitemapResults.push({ url, ...r });
    process.stderr.write(`${r.status} ${url}\n`);
  } catch (e) {
    sitemapResults.push({ url, status: 'ERR', error: String(e.cause ?? e.message ?? e).slice(0, 120) });
    process.stderr.write(`ERR ${url}\n`);
  }
}

// ---------- 2. Internal links on page-type pages ----------
const fixture = fs.readFileSync(path.join(REPO, 'tests/e2e/fixtures/routes.ts'), 'utf8');
const pagePaths = [...fixture.matchAll(/path:\s*'([^']+)'/g)].map((m) => m[1]);

const linkTargets = new Map(); // url -> found-on
for (const p of pagePaths) {
  const html = await (await safeFetch(BASE + p)).text();
  for (const m of html.matchAll(/<a\s[^>]*href="([^"#]+)"/g)) {
    let u;
    try {
      u = new URL(m[1], BASE);
    } catch {
      continue;
    }
    if (u.host !== HOST) continue;
    if (/\.(css|js|png|jpe?g|webp|svg|ico|woff2?)(\?|$)/i.test(u.pathname)) continue;
    const key = u.origin + u.pathname;
    if (!linkTargets.has(key)) linkTargets.set(key, p);
  }
}

const linkResults = [];
for (const [url, foundOn] of linkTargets) {
  try {
    const r = await status(url);
    linkResults.push({ url, foundOn, ...r });
    if (r.status !== 200) process.stderr.write(`BROKEN ${r.status} ${url} (on ${foundOn})\n`);
  } catch (e) {
    linkResults.push({ url, foundOn, status: 'ERR', error: String(e.cause ?? e.message ?? e).slice(0, 120) });
    process.stderr.write(`ERR ${url} (on ${foundOn})\n`);
  }
}

// ---------- 3. Canonical redirects + real 404 ----------
const redirectChecks = [];
for (const [name, url, expect] of [
  ['http → https', `http://${HOST}/`, 'redirects to https'],
  ['www → apex', `https://www.${HOST.replace(/^www\./, '')}/`, 'redirects to apex (or serves canonically)'],
  ['garbage URL is a 404', `${BASE}/this-page-should-not-exist-audit-check/`, '404'],
]) {
  try {
    const r = await status(url);
    redirectChecks.push({ name, url, expect, ...r });
  } catch (e) {
    redirectChecks.push({ name, url, expect, error: String(e.message ?? e) });
  }
}

// ---------- Output ----------
fs.mkdirSync(OUT_DIR, { recursive: true });
fs.writeFileSync(
  path.join(OUT_DIR, 'links.json'),
  JSON.stringify(
    { base: BASE, generatedAt: new Date().toISOString(), sitemapResults, linkResults, redirectChecks },
    null,
    2
  )
);

const badSitemap = sitemapResults.filter((r) => r.status !== 200);
const badLinks = linkResults.filter((r) => r.status !== 200);
console.log(`Sitemap URLs checked: ${sitemapResults.length}; non-200: ${badSitemap.length}`);
for (const b of badSitemap) console.log(`  ${b.status} ${b.url}`);
console.log(`Internal links checked: ${linkResults.length}; broken: ${badLinks.length}`);
for (const b of badLinks) console.log(`  ${b.status} ${b.url} (found on ${b.foundOn})`);
console.log('Redirect checks:');
for (const c of redirectChecks) {
  console.log(`  ${c.name}: ${c.error ?? `${c.status} → ${c.finalUrl ?? c.location ?? ''}`}`);
}
