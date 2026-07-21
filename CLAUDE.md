# CLAUDE.md — Oomph Travel WordPress Build

You are the paired developer on the oomphtravel.com rebuild. Read this file at the start of every session. The rules below are imperative — follow them.

## Project overview

This is the WordPress rebuild of **oomphtravel.com** for Oomph Travel LLC, a solo luxury travel advisory specializing in **premium cruises and custom European journeys**. The site is built on SiteGround + Kadence Pro (child theme), deployed via GitHub Actions, with Rank Math handling SEO and Microsoft Clarity handling CRO measurement.

The advisor is Eric Hempel, based in Port Angeles, WA. Read like a trusted concierge writing to one client — not a brochure, not a marketplace.

## Tech stack

- **Hosting:** SiteGround GrowBig (SSH enabled, WP-CLI, staging environment)
- **CMS:** WordPress 6.x with block editor (Gutenberg). NO page builders.
- **Parent theme:** Kadence (free) + Kadence Pro bundle
- **Child theme:** `kadence-oomph-child` — presentation only (CSS, patterns, parts, templates)
- **Custom plugin:** `oomph-travel-core` — data layer (CPTs, taxonomies, schema injection, env guards)
- **SEO:** Rank Math (free tier; Pro added at month 6 if needed)
- **Speed:** SG Optimizer (free, SiteGround-native; outperforms WP Rocket on this stack)
- **Forms:** Fluent Forms (free) for Discovery Call inquiry; Flodesk embeds for newsletter
- **Booking:** Calendly embedded inline (never modal)
- **Analytics:** Site Kit by Google (GA4 + GSC) + Microsoft Clarity (free, unlimited)
- **Deployment:** GitHub Actions → rsync over SSH to SiteGround. See `.github/workflows/deploy.yml`.

## Project structure

```
oomph-site/
├── CLAUDE.md                      ← this file
├── BUILD-PLAN.md                  ← full build plan with prompts
├── docs/
│   ├── brand-tokens.md            ← colors, type, spacing, radii, shadows
│   ├── voice-guide.md             ← the No List, voice rules, CTA copy
│   ├── schema.md                  ← JSON-LD library, one per page type
│   └── cro-rules.md               ← R1–R66 rules, non-negotiable
├── .github/workflows/deploy.yml   ← CI/CD pipeline
├── wp-content/
│   └── themes/
│       └── kadence-oomph-child/   ← child theme (presentation)
├── plugins/
│   └── oomph-travel-core/         ← custom plugin (CPTs, schema, env guards)
│           ├── style.css
│           ├── functions.php
│           ├── theme.json
│           ├── assets/
│           │   ├── css/
│           │   ├── js/
│           │   └── fonts/
│           ├── patterns/          ← block patterns
│           ├── parts/             ← template parts (header, footer)
│           ├── templates/         ← page templates
│           └── inc/               ← PHP includes (schema, hooks, helpers)
└── .gitignore
```

**You only edit files inside `wp-content/themes/kadence-oomph-child/` and `plugins/oomph-travel-core/`.** Never modify Kadence parent theme files; if you need to change parent behavior, override in the child. Presentation belongs in the theme; CPTs, schema, and environment-aware code belong in the plugin (so the data layer survives a theme switch).

## Imported docs (load on demand)

@docs/brand-tokens.md
@docs/voice-guide.md
@docs/schema.md
@docs/cro-rules.md

## Workflow commands

```bash
# Local dev (assumes Local by Flywheel or wp-env)
wp theme activate kadence-oomph-child
wp plugin activate oomph-travel-core
wp cache flush

# Deploy to staging (on push to develop branch — automatic via GitHub Actions)
git push origin develop

# Deploy to production (on push to main branch — automatic, requires PR review)
git push origin main

# Purge SiteGround cache after deploy (handled by deploy.yml)
wp sg purge

# Validate Core Web Vitals on a changed page
# Run from the URL bar: https://pagespeed.web.dev/?url=<page-url>
```

## What to ASK before doing — IMPORTANT

These actions require human approval before execution. Stop and ask:

- Installing a new WordPress plugin
- Changing primary CTA copy, destination URL, or microcopy
- Modifying schema markup structure
- Adding any popup, interstitial, or third-party script
- Touching `wp-config.php`, `.htaccess`, or anything outside the child theme
- Pushing directly to `main` (always via PR from `develop`)

## What NOT to do — YOU MUST follow

- **NEVER** use words from the No List in `docs/voice-guide.md`. If a draft contains "curated," "bespoke," "hidden gem," "wanderlust," "white-glove," etc. — rewrite.
- **NEVER** use pure white (`#FFFFFF`) or pure black (`#000000`). Use Paper (`#FEFCF8`) and True Ink (`#14171A`).
- **NEVER** use pill radius (`9999px`) on buttons. Squared geometry — `--radius-sm: 4px`.
- **NEVER** lazy-load the hero LCP image. Use `fetchpriority="high"` and explicit dimensions.
- **NEVER** ship a page with more than one primary CTA. Secondaries are ghost/outlined.
- **NEVER** use generic stock photography for hero or featured imagery.
- **NEVER** display unearned credentials (e.g., "DS-Italy") as earned.
- **NEVER** modify the Kadence parent theme. Override in the child.
- **NEVER** use a page builder (Elementor, Divi, etc.). Block editor only.
- **NEVER** push schema markup for content not visible on the page.
- **NEVER** deploy to production without staging validation first.

## Voice in one sentence

A trusted advisor who has personally been to the place, writing to one client at a time. Use "I," not "we." Specifics beat adjectives. Name the place, the season, the milestone.

## The 60·20·10·6·4 color recipe

- **60%** warm neutrals (Paper, Bone, Mist)
- **20%** Ink + Charcoal
- **10%** Peacock Ink
- **6%** Terracotta Warm
- **4%** one secondary accent per section — never two

On editorial pages, push neutrals to 70 and Terracotta to 3. On inquiry flows, Peacock + Terracotta can climb to ~18 combined.

## The four signature combinations

1. **The Hero** — Bone · Peacock Ink · Terracotta Warm. Default web hero.
2. **The Editorial Inversion** — True Ink · Champagne · Bone. Footers, premium blocks.
3. **The European Itinerary** — Bone · Soft Sage · Muted Brick · Champagne.
4. **The Quiet Premium** — Paper · Deep Peacock · Stone hairlines. Forms, advisor bio.

## Verification before declaring a task done

1. Run Lighthouse (mobile profile). LCP < 2.5s, INP < 200ms, CLS < 0.1.
2. Validate schema with Google Rich Results Test. Zero errors.
3. Confirm primary CTA above the fold AND in mobile sticky bar.
4. Take mobile + desktop screenshots. Compare to spec.
5. Console clean. No failed network requests.
6. WCAG AA contrast on every text/background pair (4.5:1 minimum).
7. Run the e2e smoke suite: `npm ci && npx playwright test` (defaults to staging; set `OOMPH_BASE_URL` to retarget). See `docs/testing.md`. These never submit forms — they're repo-root tooling and are never deployed to the theme.

## When in doubt

Ask. Prefer staging over production. Prefer simplicity over cleverness. Prefer Eric's actual voice over copy that sounds like every other travel site.

The tagline: **Life is short — travel with Oomph.**
