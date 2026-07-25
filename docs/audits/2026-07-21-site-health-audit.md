# Site health audit — 2026-07-21

Formal Phase 14 sweep of **production (oomphtravel.com)**, run from a residential
IP with the repo's audit tooling (`npm run audit:lh`, `audit:links`, `audit:a11y`)
plus Google's Rich Results Test. Pass bars are `docs/seo-checklist.md` items 5–9.

**Verdict: structurally healthy — zero broken links, valid schema, all money
paths green — with real findings in accessibility (fixed same day), mobile
performance (open), and meta descriptions (open).** Details and every number
below.

---

## 1. 404 / redirect map — ✅ PASS (1 finding)

`npm run audit:links` (results: `scripts/audit/out/links.json`):

| Check | Result |
|---|---|
| Sitemap URLs (26, from `/sitemap_index.xml`) | **26/26 return 200** |
| Internal `<a>` links across all page types (17 unique) | **0 broken** |
| `www.` → apex | ✅ 301 → `https://oomphtravel.com/` |
| Garbage URL | ✅ genuine 404 |
| `http://` → `https://` | ❌ **FINDING #1** — plain HTTP serves the site with **200, no redirect** |

**Finding #1 (medium): no HTTP→HTTPS redirect.** `http://oomphtravel.com/`
returns the full page over insecure HTTP. Mitigated by the correct `https`
canonical tag (verified), so search engines aren't confused — but visitors who
type the bare domain can browse unencrypted, and it's a duplicate-content
exposure. **Fix (Eric, ~1 minute, no code):** SiteGround **Site Tools →
Security → HTTPS Enforce → ON** for oomphtravel.com (and staging2). Re-verify
with `npm run audit:links` (expect a 301 on the http check).

## 2. Rich Results (Google's own tester) — ✅ PASS after same-day fixes

Tested live in Google's Rich Results Test per page type:

| Page | Detected | Result |
|---|---|---|
| Home `/` | Breadcrumbs, LocalBusiness, Organization | ✅ valid |
| Service `/luxury-cruise-planning/` | Breadcrumbs, LocalBusiness, Organization | ✅ valid (Service/FAQ aren't rich-result types; FAQ content not yet populated) |
| Journal post | **Articles**, Breadcrumbs, LocalBusiness, Organization | ✅ valid |
| DV sailing | **Events (2)**, Breadcrumbs, LocalBusiness, Organization | ✅ valid |
| `/client-stories/` | Review snippets | ❌ → ✅ **FINDING #2, fixed** |

**Finding #2 (high, FIXED): duplicate Review markup made Google flag invalid
items.** The Client Stories template carried schema.org **microdata**
(`itemtype="https://schema.org/Review"`) on each testimonial alongside the
plugin's JSON-LD — Google merged both and reported "9 items detected: Some are
invalid." This violated the project's schema-ownership rule (plugin is the sole
source, `docs/schema.md`). **Fixed:** microdata stripped from
`inc/client-stories.php`; re-test after the production deploy.

**Also fixed:** RRT's one non-critical warning (missing optional top-level
`telephone` on TravelAgency) — added in `class-schema.php`.

## 3. WCAG AA / axe-core — ❌ → ✅ (2 plugin-owned nits remain)

`npm run audit:a11y` — WCAG 2.1 A/AA, 11 page types × desktop + mobile.

**Before fixes: 312 color-contrast violation nodes + 2 ARIA issues.**
**After fixes (verified on staging): 2 nodes remain, both inside Fluent Forms'
own markup.**

What was wrong and what changed (all in the 2026-07-21 commits):

| Finding | Root cause | Fix |
|---|---|---|
| **#3 (high, FIXED): "invisible" headlines on dark sections** — the final-CTA headline on ~6 pages + the homepage "Hi, I'm Eric." heading rendered near-ink on ink (**1.1:1** — see `images/2026-07-21-final-cta-before.png` vs `-after.png`) | Kadence `theme.json` heading color isn't overridden inside `.is-style-oomph-cabin-notes` | headings now `color: inherit` on inverse sections (`components.css`) |
| **#4 (medium, FIXED): ~300 nodes of low-contrast muted text** (eyebrows, trust strip, microcopy, form privacy) | `--color-slate #7C786C` = 4.23:1 on bone, 3.69:1 on mist — under the 4.5:1 bar | token darkened to **#6B675B** (5.4:1 / 4.7:1); `docs/brand-tokens.md` updated |
| **#5 (low, FIXED): sailing-card "Exclusive shore event" line** | old brass on bone = 3.0:1 | switched to bronze (4.8:1) |
| **#6 (low, FIXED): Fluent Forms submit buttons** | FF per-form styles emit an **empty** `background-color` | theme override matches `.oomph-btn--primary` (brass + ink, 5.7:1) |
| **#7 (low, FIXED): testimonial star ratings** | `aria-label` on a plain `<span>` (prohibited) | added `role="img"` |
| **#8 (low, OPEN): FF progress bar** (`.ff-el-progress-bar`) | unnamed `progressbar` role + low-contrast label — Fluent Forms plugin markup | recommendation: revisit after FF updates, or restyle/label via a small JS shim if it starts to matter |

## 4. Lighthouse mobile — ⚠️ mixed (the open workstream)

`npm run audit:lh` — full numbers in `scripts/audit/out/lighthouse.json`
(pre-fix run; A11y scores will rise after the contrast fixes deploy):

| Page | Perf | A11y | BP | SEO | LCP (ms) | CLS |
|---|---|---|---|---|---|---|
| / | 52 | 94 | 79 | 100 | 10334 | 0.007 |
| /about/ | 71 | 94 | 79 | 100 | 7647 | 0.009 |
| /luxury-cruise-planning/ | 94 | 94 | 79 | 100 | 2105 | 0.007 |
| /custom-italy-travel/ | 73 | 94 | 79 | 92 | 6265 | 0.02 |
| /multi-generational-travel-planning/ | 80 | 94 | 79 | 92 | 4434 | 0.035 |
| /discovery-call/ | 59 | 91 | 79 | 92 | 10102 | 0 |
| /journal/ | 85 | 94 | 79 | 92 | 3978 | 0.026 |
| /client-stories/ | 93 | 90 | 79 | 100 | 1883 | 0.006 |
| /trip-quiz/ | 96 | 94 | 79 | 92 | 1724 | 0.012 |
| /cruise-travel-trends/ | 97 | 94 | 79 | 92 | 1297 | 0.022 |
| /group-cruises/ | 56 | 93 | 79 | 100 | 9282 | 0 |
| journal post | 94 | 90 | 79 | 100 | 2159 | 0 |
| DV sailing | 53 | 94 | 79 | 92 | 10188 | 0 |

Reading it: **CLS is excellent everywhere** (≤0.035 vs the 0.1 bar) and TBT is
fine. The story is **LCP: pages with big hero images take 7.6–10.3s** on
throttled mobile (bar: 2.5s); pages without heavy heroes hit ~1.3–2.2s.

**Finding #9 (medium, PARTLY RESOLVED 2026-07-25 — see
[`2026-07-25-lcp-workstream.md`](2026-07-25-lcp-workstream.md)).** The root
cause below was misdiagnosed: measuring showed the *fonts* (761KB of
unsubsetted variable faces) dominated, not the hero JPEGs —
`/group-cruises/` scored 9.9s LCP on a text element with no images on the
page at all. Fonts subsetted, theme images converted to responsive WebP, hero
and body font preloaded: perf 52→92, LCP 10.3s→3.1s on `/`. Still open, and
no longer in the theme: SG Optimizer critical-CSS (~610ms) and TTFB (~400ms).

Original diagnosis, retained for the record: Root causes measured on `/`:
hero JPEGs of 250–400KB (no WebP/AVIF, no responsive `srcset` — Lighthouse
projects ~50–70% savings) + ~2.9s of render-blocking combined CSS (SG
Optimizer). **Recommended workstream (theme + SG Optimizer settings, ~half a
day):** convert theme images to WebP with `srcset`, preload the per-page hero,
and turn on SG Optimizer's critical-CSS/deferred-CSS options carefully.

**Finding #10 (low, OPEN): meta descriptions missing** on Custom Italy,
Multi-Gen, Discovery Call, Journal, Trip Quiz, Cruise Trends, and DV sailing
pages (the SEO-92 scores). Content task in Rank Math per page; for the 448 DV
sailings, a template default in the plugin would cover them all.

**Best Practices 79 everywhere = Microsoft Clarity's third-party cookies** —
inherent to running Clarity, not a defect. Accept (or drop Clarity someday).

## Recommendations (priority order)

1. **HTTPS Enforce** in SiteGround (Finding #1) — 1 minute, Eric.
2. ~~**Hero-image/LCP workstream** (Finding #9)~~ — code half done 2026-07-25,
   see [`2026-07-25-lcp-workstream.md`](2026-07-25-lcp-workstream.md). Remaining:
   SG Optimizer critical-CSS (Eric, staging first) and the Calendly payload on
   `/discovery-call/`.
3. **Meta descriptions** (Finding #10) — Rank Math per page + plugin default for sailings.
4. FF progress bar a11y (Finding #8) — low, revisit opportunistically.

## Re-running this audit

```bash
npm run audit:links   # 404/redirect map (~2 min)
npm run audit:a11y    # axe WCAG pass    (~1 min)
npm run audit:lh      # Lighthouse       (~15 min)
```

All default to production; set `OOMPH_BASE_URL` to retarget. Rich Results Test:
https://search.google.com/test/rich-results (paste any page URL). Outputs land
in `scripts/audit/out/` (gitignored).
