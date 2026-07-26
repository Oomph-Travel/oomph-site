# LCP workstream — 2026-07-25

Closes the code half of **Finding #9** in
[`2026-07-21-site-health-audit.md`](2026-07-21-site-health-audit.md). Measured
with `npx lighthouse` (mobile emulation, the Lighthouse default) from a
residential connection.

**Verdict: perf 52–85 → 84–96, LCP 10.3s → 3.1s on the worst page. LCP still
misses the 2.5s bar on every page, and the remaining gap is no longer in the
theme.** What's left is SiteGround/SG Optimizer configuration and, on
`/discovery-call/`, Calendly.

---

## The audit's diagnosis was wrong

Finding #9 attributed the 7–10s LCPs to "hero JPEGs of 250–400KB (no
WebP/AVIF, no responsive `srcset`)". Measuring first showed something else:

| Page | LCP | FCP | LCP element |
|---|---|---|---|
| `/` | 9.9s | 4.5s | hero WebP (180KB) |
| `/group-cruises/` | 9.9s | 5.3s | **a `<p>` of text** — no image on the page |
| `/discovery-call/` | 10.1s | 5.2s | hero JPEG (344KB) |

`/group-cruises/` scoring 9.9s with no images at all is the tell. The heaviest
thing on every page was the self-hosted fonts:

```
342 KB  inter-variable-latin.woff2
229 KB  fraunces-variable-latin-italic.woff2
190 KB  fraunces-variable-latin.woff2
        = 761 KB, and ~95% of page weight on /group-cruises/
```

Despite the `-latin` filenames these were the **full faces** — Inter carried
2,849 mapped glyphs (Greek, Cyrillic, Vietnamese, symbols). Lighthouse's image
opportunities on `/discovery-call/`, the page the diagnosis best fitted, came
to ~1.2s of a 10.1s LCP. Images alone could never have reached the bar.

## What changed

**Fonts — 1,135 KB → 544 KB (−53%).** `npm run fonts`
(`scripts/fonts/subset.sh`). Originals moved to `scripts/fonts/src/`, which is
repo-root tooling and never rsynced. Subset to Latin plus the twelve non-ASCII
characters the brand actually renders — including the `→` in every primary CTA
and the `✓` in inline validation, both verified present in the output.
(Fraunces never contained either; Inter renders them, unchanged.) Fraunces
additionally pins its `SOFT` and `WONK` axes, which nothing varies — no rule
sets `font-variation-settings` and browsers only auto-vary `opsz` — taking it
from −20% to −56%.

Deliberately **not** done: pinning Inter's `opsz` (saves ~52KB but optical
sizing applies at every size, so it is a real typographic change) or clamping
`wght` to the 100–600 the CSS names (~8KB, and breaks `<strong>` at 700 in
journal prose).

**Images.** `oomph_picture()` emits `<picture>` with a WebP `srcset` from the
variants `npm run images` generates, original as fallback; `oomph_preload_hero()`
emits a matching `imagesrcset` so the LCP image is discovered in `<head>`
rather than after CSS parse. Both read the same width/`sizes` tables — that is
what stops the browser downloading the hero twice.

The generator now also treats WebP as a source. The homepage LCP element
(`hero-background.webp`) had no variants at all and shipped one 180KB file to
every phone; it is now 30KB at 768px. Quality is a ceiling rather than a
promise: heroes target q85, and the script steps down 5 points at a time until
each variant fits R3's 250KB cap, reporting where it landed.

**Resource hints.** Preload Inter (the body face, and the LCP element on
`/group-cruises/`); preconnect to the analytics origins Lighthouse measured at
~450ms. Fraunces is deliberately not preloaded — it only sets headlines, `swap`
paints those immediately, and another ~100KB would compete with the hero on
image-LCP pages.

## Results

Before = production, old code (the 2026-07-21 table). After = staging2.

| Page | Perf | LCP | CLS |
|---|---|---|---|
| `/` | 52 → **92** | 10.3s → **3.1s** | 0.007 |
| `/group-cruises/` | 56 → **89** | 9.3s → **3.4s** | 0.001 |
| `/discovery-call/` | 59 → **84** | 10.1s → **4.3s** | 0.007 |
| `/custom-italy-travel/` | 73 → **93** | 6.3s → **3.1s** | 0.020 |
| `/multi-generational-travel-planning/` | 80 → **96** | 4.4s → **2.8s** | 0.035 |
| `/journal/` | 85 → **92** | 4.0s → **3.1s** | 0.026 |

CLS stays excellent throughout — explicit `width`/`height` survived the swap.
The 36-check e2e suite passes against staging, and desktop screenshots of `/`
and `/group-cruises/` are visually identical to production, with `h1`, CTA and
eyebrow all resolving to the same family, style and weight as before.

**Two caveats on these numbers.** Staging does not load Microsoft Clarity
(production-gated via `Clarity_Guard`), worth ~192KB of third-party on `/` and
`/group-cruises/` — so production will land somewhat below the staging figures.
And staging does not have SG Optimizer's CSS combination on, so its
render-blocking profile differs from production's.

## What is left, and why it is not in the theme

LCP still misses 2.5s everywhere. On `/` the remaining budget is:

- **render-blocking CSS — ~610ms.** The other half of the Finding #9
  recommendation ("turn on SG Optimizer's critical-CSS/deferred-CSS options
  carefully") is still not done. This is a SiteGround dashboard setting, and
  critical-CSS generation can break rendering, so it wants a careful pass on
  staging first — Eric.
- **TTFB — ~400ms.** Hosting/caching, not code.

`/discovery-call/` is a separate problem: **~2.9MB of Calendly** on initial
load (1,269KB stylesheet, 1,256KB script, plus 243KB of Stripe), which is why
it remains the slowest page despite its hero dropping 344KB → 74KB. Deferring
the Calendly embed until it scrolls into view would be the single biggest win
left on the site's primary conversion path — but it touches that path and adds
load-time behaviour to a third-party script, so it needs a decision (R64/R65)
rather than a commit.

Also noted, not acted on: `assets/images/journal/*.jpg` (two files, 564KB) are
referenced by no template — journal cards use Media Library featured images.
They look like leftovers from an earlier iteration.

## Re-running

```bash
npm run fonts    # re-subset after replacing anything in scripts/fonts/src/
npm run images   # re-generate variants after adding/replacing theme images
npm run audit:lh # Lighthouse (~15 min, all page types)
```

`npm run fonts` needs `pip3 install fonttools brotli`.
