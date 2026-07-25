# Oomph Travel — WordPress Build Plan

**Status reconciled:** 2026-07-03
**Canonical spec:** [`docs/source/build-plan-v2.docx`](docs/source/build-plan-v2.docx) (the 16-phase plan with full prompts, section sequences, and acceptance criteria)
**Working principle:** the docx is the immutable spec; this file tracks live status, locked decisions, deviations, and open questions. When the two disagree, the docx is the spec, this file is the truth about reality.

---

## Where the build stands (2026-07-03)

**The site is LIVE on production (oomphtravel.com) and healthy.** Phases 0–12 and 15 are complete; the remaining work is automated testing (Phase 13), a formal pre-launch audit pass (Phase 14), and the ongoing operating cadence (Phase 16, mostly content).

Last code deploy was **2026-05-27**. Since then the repo has been dormant, but **content has continued to be added directly in WordPress** — e.g. a third journal post (`/journal/10-day-united-kingdom-itinerary/`) now live that isn't reflected in any commit. Journal posts, forms, and menus are DB content, not git-deployed.

**Live and verified (health-check 2026-07-03):**
- All primary pages return HTTP 200; a nonsense path correctly 404s. TTFB mostly < 0.7s.
- Home, About, Luxury Cruise Planning, Discovery Call, Custom Italy, Multi-Generational, Journal (3 posts), Client Stories, Cabin Quiz (`/trip-quiz/`), Cruise Travel Trends.
- JSON-LD `@graph` renders (TravelAgency + Person + BreadcrumbList sitewide; BlogPosting on posts; Review + AggregateRating on `/client-stories/`) — plugin is the sole source.
- GA4 (`G-2QCSEFT44S`) firing; `sitemap_index.xml` (2 sub-sitemaps), `llms.txt`, `robots.txt` all served.
- SSL valid through 2026-10-01 (Let's Encrypt, auto-renews).
- Primary CTA "Start a conversation →" present sitewide.

**No planning fee** — Eric does not charge one; all fee copy is reframed to commission-based / no-added-cost. R44–R46 (the `/how-i-work/` fees page) are **moot** and will not be built.

---

## Locked decisions

| Decision | Choice | Date | Notes |
|---|---|---|---|
| Child theme directory | `kadence-oomph-child` | 2026-05-14 | Renamed from `oomph-child` to match docx §6.3. Rename complete; live on prod. |
| Theme/plugin split | Adopted | 2026-05-14 | Custom plugin `plugins/oomph-travel-core` for CPTs, taxonomies, schema injection, environment guards. Theme = presentation only. (docx §6.1, §7) |
| ACF Pro | **Adopted** | 2026-05-14 | JSON sync redirected to `plugins/oomph-travel-core/acf-json/` so field groups live with the CPTs they describe. Three field groups specified in [`docs/acf-field-groups.md`](docs/acf-field-groups.md): Page Hero, Service Page, Group Cruise. Create via UI; JSON commits via the plugin rsync. |
| Local environment | WP Local (Local by Flywheel) | (pre-existing) | Keeping; docx §1 specs DDEV but Local is set up and symlinked. No migration unless something breaks. WP-CLI runs via Local's site shell, not `ddev wp`. |
| IDE / AI tooling | Claude Code only | 2026-05-14 | Not using Antigravity IDE or Cursor. **No `AGENTS.md`** — that file exists only to feed Cursor/Antigravity the same instructions as CLAUDE.md, and we don't need it. |
| Deploy pipeline | GitHub Actions → rsync (in place) | (pre-existing) | Keeping; docx §8 specs a local `scripts/deploy.sh`. CI deploy is strictly better for audit, branch protection, and machine independence. May still adopt docx's `pull-db.sh` / `pull-uploads.sh` for the content-down direction. |
| Repo visibility | Public | (pre-existing) | Source PDFs (Brand Book, SEO/CRO research) are gitignored in `docs/source/`. |

---

## Phase status — as of 2026-07-03

| # | Phase | Docx ref | Status | Notes / remaining |
|---|---|---|---|---|
| 0 | Pre-flight | §0 | ✅ Done | — |
| 1 | Local environment | §1 | ✅ Done | Local by Flywheel (see decisions) |
| 2 | GitHub repo | §2 | ✅ Done | — |
| 3 | Claude Code config | §3 | ✅ Done | — |
| 4 | CLAUDE.md + deeper docs | §4 | ✅ Done | All docs present: brand-tokens, voice-guide, schema, cro-rules, cro-backlog, seo-checklist, deploy, page-playbooks, acf-field-groups, schema-division. No `AGENTS.md` by decision. |
| 5 | WordPress foundation | §5 | ✅ Done | Kadence Pro + child active; Rank Math, Site Kit (GA4+GSC), Clarity (prod-only), Fluent Forms **Pro**, SG Optimizer all live. |
| 6 | Child theme scaffold | §6 | ✅ Done | `kadence-oomph-child` — templates, parts, custom footer (`inc/footer.php`). |
| 7 | Custom plugin | §7 | ✅ Done | `wp-content/plugins/oomph-travel-core` — schema, seo, environment, clarity-guard, CLI, ACF config, 3 CPTs (cruise/itinerary/destination). |
| 8 | Deploy pipeline | §8 | ✅ Done | GitHub Actions → rsync (theme + plugin). `pull-*` scripts still un-adopted. |
| 9 | Brand system in code | §9 | ✅ Done | `theme.json` + tokens/base/components CSS; "Deep Marine" palette (Marine Navy + Old Brass + Warm Bone). |
| 10 | Page builds | §10 | ✅ Done | Home, About, all service pages, Discovery Call, Journal, Client Stories, Cabin Quiz, lead-magnet landers. Group-cruise template **not** built (see Phase 16 backlog). |
| 11 | SEO + schema wiring | §11 | ✅ Done | Plugin owns all JSON-LD (hardened vs. Rank Math); llms.txt, robots.txt, XML sitemap, Bing Webmaster. Deep contextual internal-linking pass deferred until more journal content. |
| 12 | Forms + Calendly + lead magnets | §12 | ✅ Done | Discovery 3-step form (R38) → Calendly inline + on-page booking confirmation; Cabin Quiz + Cruise Trends + Newsletter, each auto-delivering its PDF. **Flodesk not yet wired** (using Fluent Forms). |
| 13 | Playwright (MCP + e2e) | §13 | ✅ Done | `tests/e2e/` Playwright smoke suite (35 checks) vs staging: page loads + CTA, JSON-LD per page type, Discovery form/Calendly render, Cabin Quiz flow→gate, Group Cruises archive + no-leaked-refs. Non-blocking CI in `.github/workflows/e2e.yml`. Docs: `docs/testing.md`. No form is ever submitted. |
| 14 | Pre-launch checklist | §14 | ✅ Done | Formal audit run + recorded 2026-07-21: `docs/audits/2026-07-21-site-health-audit.md`. Links/sitemap/schema clean; 312 WCAG contrast nodes + invisible dark-section headlines found and FIXED same day. Open: HTTPS Enforce toggle (Eric), hero-image LCP workstream, meta descriptions. Re-runnable via `npm run audit:{links,a11y,lh}`. |
| 15 | Launch day | §15 | ✅ Done | Production live since 2026-05-24; DNS/SSL/analytics all confirmed. |
| 16 | Operating cadence | §16 | 🔄 Ongoing | Content cadence active (journal posts being added). Group cruises, real testimonials + more Review schema, Flodesk, deeper internal linking are the open items. |

---

## Active deviations from the docx

1. **Local by Flywheel, not DDEV.** Working setup. No reason to migrate. Implication: WP-CLI commands run via Local's site shell, not `ddev wp`. The `pull-*` scripts in docx Appendix D need adjustment to the Local path (`~/Local Sites/oomph-local/app/public/`) when adopted.
2. **GitHub Actions CI deploy, not local `scripts/deploy.sh`.** CI is strictly better for audit and machine-independence. The current pipeline rsyncs the child theme on push to `develop` → staging or `main` → production with a manual approval gate on prod.
3. **Child theme name `kadence-oomph-child`** (matches docx). Rename is complete and the GitHub Actions `SG_*_THEME_PATH` secrets were updated; deploys to staging + prod verified.
4. ~~**ACF Pro deferred.**~~ ACF Pro adopted 2026-05-14. JSON sync target redirected to the plugin; field group specs in `docs/acf-field-groups.md`.

---

## Open questions

- Adopt the docx's `pull-db.sh` / `pull-uploads.sh` content-down pipeline now or wait until first production-to-local mirror is needed?
<!-- ACF Pro decision resolved 2026-05-14 — adopted. License confirmed installed on Local + staging + production 2026-05-15. -->
- Cloudflare Free in front of production at launch (docx §15.3) — yes or no?
- Brand Book / SEO+CRO PDFs: leave as binary-only references in `docs/source/`, or extract to markdown for in-repo grepability? (Condensed SEO/CRO is already markdown; Brand Book is not.)

---

## Working principles

Carried over from prior versions; non-negotiable.

- **Two-direction rule.** Code flows up: repo → staging → production. Content flows down: production → staging → local. Violate the rule and you overwrite real client content.
- **One CTA per page.** "Start a conversation →" linking to `/discovery-call`. Voice rules in [`docs/voice-guide.md`](docs/voice-guide.md) are non-negotiable.
- **No page builder.** Block editor only. No Elementor, Divi, Bricks.
- **Block editor constraints.** Authors cannot pick arbitrary colors — palette is locked via `theme.json`. No pill radius on buttons.
- **Verification gate.** Before any page is marked done: Lighthouse mobile passes (LCP <2.5s, INP <200ms, CLS <0.1), schema validates in Google Rich Results Test, WCAG AA contrast verified on every text/background pair, screenshots taken at mobile + desktop.
- **You only edit files inside `wp-content/themes/kadence-oomph-child/` and `plugins/oomph-travel-core/`.** Never modify Kadence parent.

---

## Sequenced work plan

The initial build (Phases 0–12) shipped and is live. Remaining work, in priority order:

### Next up (engineering)

1. ~~**Phase 13 — Playwright e2e tests.**~~ ✅ **Done (2026-07-21).** `tests/e2e/` smoke suite runs against staging (default `OOMPH_BASE_URL`): page-load + sitewide-CTA checks per page type, JSON-LD `@type` presence per type, Discovery form + Calendly render, Cabin Quiz intro→gate flow (+ synthesized success reveal), and Group Cruises archive/single incl. a no-leaked-internal-refs guard. **Forms are never actually submitted** (no junk leads). CI: `.github/workflows/e2e.yml` — non-blocking, runs after each develop→staging deploy + nightly + manual. Guide: `docs/testing.md`. Follow-ons (not built): real tagged-submit PDF-delivery coverage, Lighthouse/perf budgets, visual-regression snapshots.
2. ~~**Phase 14 — Formal pre-launch/health audit.**~~ ✅ **Done (2026-07-21).** Recorded in `docs/audits/2026-07-21-site-health-audit.md`. Same-day fixes: invisible dark-section headlines, sitewide muted-text contrast token, duplicate Review microdata, FF button styling, star-rating ARIA, schema telephone. Open follow-ups (see the doc's Recommendations): SiteGround HTTPS Enforce (Eric, 1 min), hero-image/LCP performance workstream, meta descriptions (incl. a plugin default for DV sailings).
3. **LCP workstream — code half done (2026-07-25), on `develop`/staging, not yet in production.** `docs/audits/2026-07-25-lcp-workstream.md`. The 2026-07-21 diagnosis was wrong: the dominant cost was 761KB of unsubsetted variable fonts, not hero JPEGs — `/group-cruises/` measured 9.9s LCP on a *text* element with no images on the page. Fonts subsetted (−53%, `npm run fonts`), theme images now responsive WebP via `oomph_picture()` (`npm run images`), Inter + per-page hero preloaded. Perf 52→92, LCP 10.3s→3.1s on `/`; e2e green, no visual change. **Still short of the 2.5s bar, and the rest isn't in the theme:** SG Optimizer critical-CSS (~610ms, Eric, staging first), TTFB (~400ms), and ~2.9MB of Calendly on `/discovery-call/` (needs an R64/R65 decision). Merge to `main` still pending review.

### Content & growth (Phase 16, ongoing — mostly Eric's input)

- **Journal cadence.** 3 posts live; the Trends guide has ~7 chapters left to mine. Add first-hand opening lines and swap in Eric's own travel photos (R19/R20).
- **Group-cruise template + Event schema.** The one page type from Phase 10 not yet built; `oomph_cruise` CPT + Event schema already scaffolded in the plugin, so this is template + content.
- **Ship Library + enrichment (2026-07-21, `feat/ship-library`).** `oomph_ship` CPT (enter a ship once — gallery, intro, quick facts — every sailing of it renders the section automatically), ship/gallery band in both `single-oomph_cruise` layouts, DV layout gains the day-by-day accordion, and `wp oomph enrich-sailing` writes prepared itinerary/inclusions/blurb payloads onto imported drafts (never publishes, never overwrites human copy). Content work now: seed ship records for the lines Eric sells; monthly enrichment payloads per new featured sailing. See `docs/updating-sailings.md` § "Deepening a sailing page".
- **Real testimonials + expanded Review schema.** `/client-stories/` has 4 seeded testimonials; grow toward the Year-1 target (R32) as real ones arrive.
- **Flodesk** newsletter integration (currently Fluent Forms handles signups).
- **Deeper contextual internal-linking pass** once there's more journal content (R17/R18).

### Un-adopted / optional

- Docx `pull-db.sh` / `pull-uploads.sh` content-down scripts (adopt when a prod→local mirror is first needed).
- Cloudflare Free in front of production (docx §15.3) — open question below.

---

## How to use this file

Before any session, skim the **Phase status** table and **Locked decisions**. Open [`docs/source/build-plan-v2.docx`](docs/source/build-plan-v2.docx) for the playbook detail of whatever you're building. Update **Phase status**, **Locked decisions**, and **Open questions** as state changes — keep this file honest. Tag the repo (`vX.Y.Z-name`) at every meaningful milestone so we can roll back without guessing what state the working tree was in.
