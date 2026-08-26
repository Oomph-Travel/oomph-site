#!/usr/bin/env node
/**
 * Validate enrichment payloads before they go into a PR.
 *
 * Usage:  node validate-payloads.mjs [filename-filter]
 *   e.g.  node validate-payloads.mjs 2027      // this month's batch
 *         node validate-payloads.mjs           // every payload in enrichment/
 *
 * Checks each file parses as JSON, carries a non-empty `why`, reads as first
 * person, and contains no words from the voice guide's No List. Exits non-zero
 * if anything fails, so it can gate a commit.
 */

import { readdirSync, readFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';

const DIR = 'enrichment';
const filter = process.argv[2] ?? '';

// docs/voice-guide.md — the No List, plus a few superlatives R50 rules out.
const NO_LIST = [
  'curated', 'bespoke', 'hidden gem', 'wanderlust', 'white-glove', 'magical',
  'breathtaking', 'jaw-dropping', 'stunning', 'iconic', 'indulge', 'pampered',
  'once in a lifetime', 'epic', 'ultimate', 'world-class', '5-star', 'luxe',
  'unforgettable', 'vibes', 'paradise', 'dream destination', 'bucket list',
  'transformative', 'foodie', 'bestie', 'hubby', 'getaway', 'luxurious',
  'amazing', 'incredible', 'the best', 'adventure of a lifetime',
];

// "escape" only offends as a noun-ish travel cliché; "escape the crowds" is fine.
const NO_LIST_CONTEXTUAL = [/\bthe\s+perfect\s+escape\b/i, /\ban?\s+escape\b/i];

if (!existsSync(DIR)) {
  console.error(`No ${DIR}/ directory here — run this from the repo root.`);
  process.exit(1);
}

const files = readdirSync(DIR)
  .filter(f => f.startsWith('enrich-') && f.endsWith('.json') && f.includes(filter))
  .sort();

if (files.length === 0) {
  console.error(`No payloads matching "${filter}" in ${DIR}/.`);
  process.exit(1);
}

let failures = 0;
const flag = (file, msg) => { console.log(`  FAIL  ${file}\n        ${msg}`); failures++; };

for (const file of files) {
  let payload;
  try {
    payload = JSON.parse(readFileSync(join(DIR, file), 'utf8'));
  } catch (err) {
    flag(file, `not valid JSON — ${err.message}`);
    continue;
  }

  const why = typeof payload.why === 'string' ? payload.why.trim() : '';
  if (!why) {
    flag(file, 'missing or empty "why"');
    continue;
  }

  // First person is the voice guide's core rule: Oomph is one advisor, not a "we".
  if (!/\b(I|I'd|I'm|I've|my|me)\b/.test(why)) {
    flag(file, 'no first-person voice — needs "I" or "my", not a detached description');
  }
  if (/\bwe\b|\bour\b/i.test(why)) {
    flag(file, 'uses "we"/"our" — Oomph is a solo advisor');
  }

  const hits = NO_LIST.filter(w => why.toLowerCase().includes(w));
  for (const re of NO_LIST_CONTEXTUAL) if (re.test(why)) hits.push(re.source);
  if (hits.length) flag(file, `No List words: ${hits.join(', ')}`);

  // Long blurbs stop being blurbs. Abbreviations inflate a naive sentence count,
  // so guard on words instead — 2–3 sentences lands well under 100.
  const words = why.split(/\s+/).length;
  if (words > 110) flag(file, `${words} words — aim for 2–3 sentences`);
}

console.log(
  failures === 0
    ? `\nOK — ${files.length} payload${files.length === 1 ? '' : 's'} passed.`
    : `\n${failures} issue${failures === 1 ? '' : 's'} across ${files.length} payloads.`
);
process.exit(failures === 0 ? 0 : 1);
