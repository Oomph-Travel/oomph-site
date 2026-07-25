#!/usr/bin/env node
/**
 * Generate responsive WebP variants for the theme's bundled images.
 *
 *   npm run images
 *
 * SiteGround's Speed Optimizer converts Media Library uploads to WebP, but it
 * does not touch files served straight out of the theme directory — which is
 * where the hero and card photography lives. Those were 250–400 KB JPEGs and
 * the reason the 2026-07 audit measured 7–10s LCP on mobile.
 *
 * For each source JPEG/PNG this writes `<name>-<width>.webp` next to it, at the
 * widths the layout actually requests. The original stays put and remains the
 * <img> fallback for the ~3% of browsers without WebP, so nothing breaks if a
 * variant is missing — `oomph_picture()` only emits sources that exist.
 *
 * Re-run after adding or replacing any theme image; it skips variants that are
 * already newer than their source, and discards any variant that would be larger
 * than the original.
 */

import sharp from 'sharp';
import fs from 'node:fs/promises';
import { existsSync, statSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..', '..');
const IMG_DIR = path.join(ROOT, 'wp-content/themes/kadence-oomph-child/assets/images');

// Widths per folder — driven by how the layout uses each image. Keys match
// oomph_image_widths() in the theme's inc/helpers.php, which reads what this
// writes; '.' is the images/ root. Keep the two in step.
const WIDTHS = {
  heroes: [768, 1200, 1600],   // full-bleed, LCP candidates
  cards: [400, 600, 900],      // 3-up grid
  journal: [400, 800, 1200],   // post cards + in-post
  '.': [480, 768, 1000],       // root — homepage hero, portrait, guide cover
  default: [480, 960],
};
const QUALITY = 72;   // above this, WebP loses to the already-tight source JPEGs

// WebP sources count too. hero-background.webp is the homepage LCP element and
// was shipping a single 180KB/1000px file to every phone — already WebP, so
// Lighthouse's modern-formats audit stayed quiet about it, but it was still
// four times the bytes a 412px viewport needs. Re-encoding at our own quality
// and widths is worth it; the `-<digits>` guard keeps outputs from being
// picked up as inputs on the next run.
const isSource = (f) => /\.(jpe?g|png|webp)$/i.test(f) && !/-\d+\.(jpe?g|png|webp)$/i.test(f);

async function walk(dir) {
  const out = [];
  for (const entry of await fs.readdir(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) out.push(...(await walk(full)));
    else if (isSource(entry.name)) out.push(full);
  }
  return out;
}

const files = await walk(IMG_DIR);
let made = 0, skipped = 0, savedBytes = 0;

for (const file of files) {
  const folder = path.relative(IMG_DIR, path.dirname(file)) || '.';
  const widths = WIDTHS[folder] ?? WIDTHS.default;
  const base = file.replace(/\.(jpe?g|png|webp)$/i, '');
  const meta = await sharp(file).metadata();
  const srcSize = statSync(file).size;

  for (const w of widths) {
    if (meta.width && w > meta.width) continue;          // never upscale
    const out = `${base}-${w}.webp`;
    if (existsSync(out) && statSync(out).mtimeMs > statSync(file).mtimeMs) {
      skipped++;
      continue;
    }
    await sharp(file).resize({ width: w, withoutEnlargement: true })
      .webp({ quality: QUALITY, effort: 6 }).toFile(out);

    // Never ship a "variant" that is bigger than the original — that only
    // happens at full width on already-optimised photos, and the <picture>
    // helper simply falls back to the JPEG when the .webp is absent.
    if (statSync(out).size >= srcSize) {
      await fs.unlink(out);
      skipped++;
      continue;
    }
    made++;
    savedBytes += srcSize - statSync(out).size;
  }
}

console.log(
  `${files.length} sources · ${made} variants written, ${skipped} up to date` +
  (savedBytes > 0 ? ` · largest-variant saving ≈ ${(savedBytes / 1048576).toFixed(1)} MB` : '')
);
