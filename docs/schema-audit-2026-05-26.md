# Schema Duplication Audit — Rank Math Pro vs `oomph-travel-core`

**Date:** 2026-05-26
**Scope:** Determine whether Rank Math Pro and the `oomph-travel-core` plugin emit overlapping JSON-LD (`Organization`, `TravelAgency`, `Person`, `Service`, `Event`, `BreadcrumbList`, `Article`, `WebPage`, `FAQPage`, `Review`/`AggregateRating`).
**Method:** Read-only. Plugin source read locally; Rank Math settings + schema postmeta read from **production** via SSH WP-CLI; rendered pages curled from **production** with a **staging** parity spot-check. No source, settings, or postmeta were modified.

> **Note on method vs the original brief:** the brief specified `ddev wp …` — this project runs on **Local by Flywheel, not DDEV**, and Rank Math Pro is installed on **staging + production, not Local** (Local has only free Rank Math). All commands were translated accordingly, and the rendered-page audit targets production (where Pro actually runs). Four URLs in the brief (`/fees`, `/client-stories`, `/italy-planning-guide`, `/cabin-selection-guide`) **do not exist** and were omitted; three real URLs (`/trip-quiz/`, `/cruise-travel-trends/`, journal single posts) were added.

---

## TL;DR

- **No live duplication exists today.** Every audited page emits **exactly one** JSON-LD block, and it comes from the **plugin**. Rank Math emits **zero** JSON-LD.
- The reason: Rank Math's **Schema / Rich Snippets module (`rich-snippet`) is disabled** on both production and staging. Its schema-related settings (`knowledgegraph_type`, per-post-type `default_rich_snippet: article`) are **configured but inert**.
- **The collision is latent, not absent.** Toggling that single module ON — via the Rank Math dashboard or the setup wizard — would immediately produce duplicate `Person`, duplicate `Organization`/`TravelAgency`, and add `Article` to every post (duplicating the plugin's `BlogPosting`) and to every page.
- **Ownership mismatch vs the intended division:** the plugin currently owns `BlogPosting` (Article) and `FAQPage`, which the brief assigned to Rank Math. This is a decision to make, independent of the module state.
- All JSON-LD on the site is **valid JSON** with `@context: "https://schema.org"`. No malformed blocks, no legacy `http://schema.org`, no per-page manual schema overrides.

---

## Phase A — Source inventory

### A1. Plugin: `wp-content/plugins/oomph-travel-core/includes/class-schema.php`

Single entry point: `Schema::output()`, hooked on **`wp_head` priority 5** (registered in `oomph-travel-core.php:52`). It emits **one combined `@graph`** with `@context: "https://schema.org"`. No other plugin file emits JSON-LD.

| Method | `@type` emitted | Gating conditional |
|---|---|---|
| `organization()` | `TravelAgency` (`@id` `…/#organization`) | **Always** (every page) |
| `person()` | `Person` (`@id` `…/about/#advisor`) | **Always** (every page) |
| `breadcrumb()` | `BreadcrumbList` | `is_singular() \|\| is_front_page()` |
| `event_for_current_post()` | `Event` | `is_singular('oomph_cruise')` (the cruise CPT) |
| `article()` | `BlogPosting` | `is_singular('post')` |
| `service_for_current_page()` | `Service` | `is_service_page()` — page template `service-page.php` **OR** slug in `custom-italy-travel`, `multi-generational-travel-planning` (filterable via `oomph_service_page_slugs`) |
| `faqpage_for_current_page()` | `FAQPage` | A service page **with real Q&A** — ACF `service_faqs` or the `oomph_page_faqs` filter; returns `null` when empty |

**Plugin owns (emits):** `TravelAgency`, `Person`, `BreadcrumbList`, `Event`, `BlogPosting`, `Service`, `FAQPage`.

### A2. Plugin: `includes/class-seo.php`

**Not a schema source.** Serves `/llms.txt` and defers `robots.txt` to Rank Math. No JSON-LD. (No `@context`, no `ld+json`.)

### A3. Theme `kadence-oomph-child`

**Clean — no JSON-LD output.** Grep for `ld+json` / `@context` / `schema.org` across the theme returns only:
- `front-page.php` — a comment confirming it **delegates** schema to the plugin ("…output by oomph-travel-core plugin via wp_head, so we don't output it here").
- `inc/enqueue.php` — unrelated emoji-script `remove_action` calls.

No stray schema in `functions.php` or any template part.

### A4. Rank Math — stored configuration (production)

- **Plugins active:** `seo-by-rank-math` v1.0.270 (free) **+** `seo-by-rank-math-pro` v3.0.113 (Pro). *(Confirms Pro is installed — it is not on Local.)*
- **Modules enabled (`rank_math_modules`):** `link-counter`, `analytics`, `seo-analysis`, `sitemap`, `woocommerce`, `buddypress`, `bbpress`, `acf`, `web-stories`, `content-ai`, `instant-indexing`, `local-seo`.
  - **`rich-snippet` (Schema / Structured Data) is NOT enabled** → confirmed `False`. This is the module that renders JSON-LD. With it off, Rank Math emits **no** schema graph.
- **Schema-relevant Titles settings (inert while the module is off):**
  - `knowledgegraph_type: person` *(prod)* — would emit a `Person` knowledge-graph entity.
  - `local_business_type: Organization` + `local-seo` module on — would emit an `Organization` knowledge-graph entity.
  - `pt_post_default_rich_snippet: article` / `pt_post_default_article_type: BlogPosting` — would put `BlogPosting` on **every post**.
  - `pt_page_default_rich_snippet: article` / `pt_page_default_article_type: Article` — would put `Article` on **every page**.
  - CPTs (`oomph_cruise`, `oomph_itinerary`, `oomph_destination`): `default_rich_snippet: off` — no Rank Math snippet on these.
- **Per-page manual schema:** `SELECT … FROM {prefix}postmeta WHERE meta_key LIKE 'rank_math_schema%'` → **0 rows.** No per-page overrides exist; Rank Math is entirely on type defaults.
- **What Rank Math *does* emit today:** only its meta wrapper —
  `<!-- Search Engine Optimization by Rank Math PRO … -->` … `<!-- /Rank Math WordPress SEO plugin -->` — containing title/meta description/canonical/OpenGraph/Twitter and the XML sitemap. **No JSON-LD.**

---

## Phase B — Duplication on rendered pages (production)

Each page was curled and every `<script type="application/ld+json">` block parsed. Source attribution: the plugin emits a single clean `@graph` block (no comment wrapper); Rank Math would wrap its JSON-LD in `<!-- Rank Math … -->`. **No Rank Math JSON-LD block was found on any page.**

| URL | JSON-LD `@type`s emitted | Source | Duplicates? | Action |
|---|---|---|---|---|
| `/` (home) | `TravelAgency`, `Person`, `BreadcrumbList` | Plugin | No | None |
| `/about/` | `TravelAgency`, `Person`, `BreadcrumbList` | Plugin | No | None |
| `/luxury-cruise-planning/` | `TravelAgency`, `Person`, `BreadcrumbList`, `Service` | Plugin | No | None |
| `/custom-italy-travel/` | `TravelAgency`, `Person`, `BreadcrumbList`, `Service`, `FAQPage` | Plugin | No | None |
| `/multi-generational-travel-planning/` | `TravelAgency`, `Person`, `BreadcrumbList`, `Service`, `FAQPage` | Plugin | No | None |
| `/discovery-call/` | `TravelAgency`, `Person`, `BreadcrumbList` | Plugin | No | None |
| `/trip-quiz/` | `TravelAgency`, `Person`, `BreadcrumbList` | Plugin | No | None |
| `/cruise-travel-trends/` | `TravelAgency`, `Person`, `BreadcrumbList` | Plugin | No | None |
| `/journal/` | `TravelAgency`, `Person`, `BreadcrumbList` | Plugin | No | None |
| `/journal/the-slow-cruise/` | `TravelAgency`, `Person`, `BreadcrumbList`, `BlogPosting` | Plugin | No | None |
| `/journal/first-premium-cruise/` | `TravelAgency`, `Person`, `BreadcrumbList`, `BlogPosting` | Plugin | No | None |

Every page: exactly **1** JSON-LD block, **0** Rank Math JSON-LD blocks.

### Staging parity (spot-check)
- Same plugin versions; `rich-snippet` module **also disabled**.
- `/` and `/luxury-cruise-planning/` each render **1** plugin JSON-LD block, **0** from Rank Math — matches production.
- **Config drift:** staging `knowledgegraph_type: company` vs production `person`. Inert today (module off); align for hygiene.

---

## Phase C — Validation

| Check | Result |
|---|---|
| Every JSON-LD block parses as valid JSON | ✅ Pass (11/11 pages) |
| `@context` is exactly `https://schema.org` | ✅ Pass — no legacy `http://schema.org` anywhere |
| Malformed blocks | None |
| Duplicate `@id` collisions | None (single source) |

---

## Conflicts found

### Active conflicts (today)
**None.** Rank Math emits no JSON-LD; the plugin is the sole source.

### Latent / armed conflicts (if `rich-snippet` module is enabled)
The settings are fully configured to collide the moment the Schema module is switched on:

| `@type` | Plugin emits | Rank Math *would* emit | Result |
|---|---|---|---|
| `Person` | Always (`#advisor`) | Knowledge graph (`knowledgegraph_type: person`) | **Duplicate Person** |
| `Organization` / `TravelAgency` | `TravelAgency` (`#organization`) | `Organization` knowledge graph (local-seo) | **Duplicate org entity, likely clashing `@id`** |
| `BlogPosting` / `Article` | `BlogPosting` on posts | `Article`/`BlogPosting` on **every post** | **Duplicate on every journal post** |
| `Article` on pages | — | `Article` on **every page** (`pt_page_default_rich_snippet: article`) | **New, inappropriate `Article`** on Home/About/Service/etc. |

### Ownership mismatch vs the intended division (independent of module state)
Intended: plugin owns `Organization`/`TravelAgency`/`Person`/`Service`/`Event`/`BreadcrumbList`; Rank Math owns `Article`/`WebPage`/`FAQPage`/`Review`+`AggregateRating`.

- ⚠️ The plugin currently **also owns `BlogPosting` (Article) and `FAQPage`** — assigned to Rank Math in the brief.
- Rank Math currently owns **nothing** (Schema module off). `WebPage` and `Review`/`AggregateRating` exist **nowhere** on the site today (no testimonials built yet).

---

## Recommended fixes (for review — no changes made)

Ordered by priority. Default stance from the brief: silence Rank Math for plugin-owned types. Given the audit, the plugin is already the clean single source — so the work is mostly about **making the safe state durable** and **resolving two ownership questions**.

### Fix 1 — Make "Rank Math emits no JSON-LD" durable (highest priority)
Today's safety depends on a single toggle that the setup wizard or an admin can flip. Harden it in code so an accidental enable can't arm the collisions.

- **Code (plugin):** add a defensive filter so Rank Math's JSON-LD graph is always emptied, even if the module is enabled. In `class-schema.php` (or a small `init` hook):
  ```php
  add_filter( 'rank_math/json_ld', '__return_empty_array', 99 );
  ```
  *(Selective alternative: unset only the six plugin-owned `@type`s from the `$data` array in that filter, leaving room for Rank Math to own `Review`/`WebPage` later. Full-empty is simplest and matches "plugin is the single source.")*
- **Settings guardrail (admin):** keep **WP Admin → Rank Math → Dashboard → "Schema (Structured Data)"** toggled **OFF** on staging and production.
- **Verify after applying:**
  ```bash
  # module should remain absent:
  wp option get rank_math_modules --format=json   # (run on the server) → no "rich-snippet"
  # re-curl and confirm still exactly one block:
  curl -s https://oomphtravel.com/ | grep -c 'application/ld+json'   # → 1
  ```

### Fix 2 — Resolve ownership of `BlogPosting` + `FAQPage`
- **Recommended: keep both in the plugin.** They are working, on-page-gated (FAQPage only emits with real Q&A), and `@id`-linked to the plugin's `Person`/`Organization` graph — richer than Rank Math's defaults. **Update the division of responsibility** to: *plugin owns all structured data; Rank Math owns titles/meta/canonical/OG/sitemap (+ optionally `Review` later).*
- **Not recommended:** moving them to Rank Math, because that requires enabling the Schema module, which re-arms the `Person`/`Organization`/page-`Article` collisions in Fix 1.

### Fix 3 — Align the staging/prod knowledge-graph config drift
Inert while the module is off, but tidy up to prevent surprises:
```bash
# on staging server, to match prod:
wp option patch update rank-math-options-titles knowledgegraph_type person
```
*(Decide the correct value first: `person` (solo advisor) vs `company` (LLC). Moot once Fix 1 disables Rank Math JSON-LD, but worth settling.)*

### Fix 4 — Decide `Review` + `AggregateRating` ownership before `/client-stories` is built
No testimonials exist yet. When they do, keep `Review`/`AggregateRating` in the **plugin** for a single coherent `@graph` (consistent with Fix 2), only marking up real, attributable, on-page reviews (per `docs/cro-rules.md` R31).

---

## Theme / `functions.php` stray-schema check

✅ **Clean.** No JSON-LD originates from the child theme or its `functions.php`. `front-page.php` explicitly delegates to the plugin. The only schema source on the site is `oomph-travel-core`.

---

## Apply order (after your go-ahead, one item at a time)

1. **Fix 1** (durable guard) — code filter + confirm module stays off. *Lowest risk, highest value.*
2. **Fix 2** (ownership decision) — update division docs; no code change if we keep plugin ownership.
3. **Fix 3** (config drift) — one `wp option patch` on staging.
4. **Fix 4** (future) — revisit when testimonials are built.

*Awaiting approval before any code, settings, or postmeta change.*
