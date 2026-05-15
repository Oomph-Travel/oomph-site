# Oomph Travel — WordPress Build Plan

**Status reconciled:** 2026-05-14
**Canonical spec:** [`docs/source/build-plan-v2.docx`](docs/source/build-plan-v2.docx) (the 16-phase plan with full prompts, section sequences, and acceptance criteria)
**Working principle:** the docx is the immutable spec; this file tracks live status, locked decisions, deviations, and open questions. When the two disagree, the docx is the spec, this file is the truth about reality.

---

## Locked decisions

| Decision | Choice | Date | Notes |
|---|---|---|---|
| Child theme directory | `kadence-oomph-child` | 2026-05-14 | Renamed from `oomph-child` to match docx §6.3. Migration tracked under "Theme rename" below. |
| Theme/plugin split | Adopted | 2026-05-14 | Custom plugin `plugins/oomph-travel-core` for CPTs, taxonomies, schema injection, environment guards. Theme = presentation only. (docx §6.1, §7) |
| ACF Pro | Deferred | 2026-05-14 | Revisit at Phase 10.12 (Group Cruise template). Until then, structured fields via core block bindings + post meta where needed. |
| Local environment | WP Local (Local by Flywheel) | (pre-existing) | Keeping; docx §1 specs DDEV but Local is set up and symlinked. No migration unless something breaks. WP-CLI runs via Local's site shell, not `ddev wp`. |
| IDE / AI tooling | Claude Code only | 2026-05-14 | Not using Antigravity IDE or Cursor. **No `AGENTS.md`** — that file exists only to feed Cursor/Antigravity the same instructions as CLAUDE.md, and we don't need it. |
| Deploy pipeline | GitHub Actions → rsync (in place) | (pre-existing) | Keeping; docx §8 specs a local `scripts/deploy.sh`. CI deploy is strictly better for audit, branch protection, and machine independence. May still adopt docx's `pull-db.sh` / `pull-uploads.sh` for the content-down direction. |
| Repo visibility | Public | (pre-existing) | Source PDFs (Brand Book, SEO/CRO research) are gitignored in `docs/source/`. |

---

## Phase status — as of 2026-05-14

| # | Phase | Docx ref | Status | Immediate next action |
|---|---|---|---|---|
| 0 | Pre-flight | §0 | ✅ Done | — |
| 1 | Local environment | §1 | ✅ Done (Local by Flywheel, see decisions) | — |
| 2 | GitHub repo | §2 | ✅ Done | — |
| 3 | Claude Code config | §3 | ✅ Done | — |
| 4 | CLAUDE.md + 7 deeper docs | §4 | ⚠️ Partial | Generate `cro-backlog.md`, `seo-checklist.md`; stub `deploy.md`, `page-playbooks.md`. (No `AGENTS.md` — not using Cursor/Antigravity.) |
| 5 | WordPress foundation | §5 | ⚠️ Partial | Kadence + child theme active. Customizer, Rank Math, Site Kit, Clarity, Fluent Forms, SG Optimizer baseline pending. |
| 6 | Child theme scaffold | §6 | ⚠️ Skeleton only | Rename to `kadence-oomph-child` (next session); tokens implementation lives in Phase 9. |
| 7 | Custom plugin | §7 | ❌ Not started | Scaffold `plugins/oomph-travel-core` per docx §7.2 |
| 8 | Deploy pipeline | §8 | ✅ Done (CI not local script) | Adopt `pull-db.sh` / `pull-uploads.sh` when first DB pull is needed |
| 9 | Brand system in code | §9 | ❌ Not started | Generate `theme.json` + `tokens.css` + `base.css` + `components.css` (after theme rename) |
| 10 | Page builds | §10 | ❌ Not started | Home page first |
| 11 | SEO + schema wiring | §11 | ❌ Not started | After plugin scaffold |
| 12 | Forms + Calendly + lead magnets | §12 | ❌ Not started | After Home page exists |
| 13 | Playwright (MCP + e2e) | §13 | ❌ Not started | After first 2-3 pages |
| 14 | Pre-launch checklist | §14 | ❌ Not started | Final stretch |
| 15 | Launch day | §15 | ❌ Not started | Final stretch |
| 16 | Operating cadence | §16 | ❌ Not started | Post-launch |

---

## Active deviations from the docx

1. **Local by Flywheel, not DDEV.** Working setup. No reason to migrate. Implication: WP-CLI commands run via Local's site shell, not `ddev wp`. The `pull-*` scripts in docx Appendix D need adjustment to the Local path (`~/Local Sites/oomph-local/app/public/`) when adopted.
2. **GitHub Actions CI deploy, not local `scripts/deploy.sh`.** CI is strictly better for audit and machine-independence. The current pipeline rsyncs the child theme on push to `develop` → staging or `main` → production with a manual approval gate on prod.
3. **Child theme name `kadence-oomph-child`** (matches docx) but the GitHub Actions `SG_*_THEME_PATH` secrets will need updating during the rename — see "Theme rename" section below.
4. **ACF Pro deferred.** Field group design tracked in `docs/source/build-plan-v2.docx` §7.3 for future reference.

---

## Open questions

- Adopt the docx's `pull-db.sh` / `pull-uploads.sh` content-down pipeline now or wait until first production-to-local mirror is needed?
- ACF Pro decision deadline: Phase 10.12 (Group Cruise template) — needs repeater fields for itinerary days.
- Cloudflare Free in front of production at launch (docx §15.3) — yes or no?
- Brand Book / SEO+CRO PDFs: leave as binary-only references in `docs/source/`, or extract to markdown for in-repo grepability? (Condensed SEO/CRO is already markdown; Brand Book is not.)

---

## Working principles

Carried over from prior versions; non-negotiable.

- **Two-direction rule.** Code flows up: repo → staging → production. Content flows down: production → staging → local. Violate the rule and you overwrite real client content.
- **One CTA per page.** "Book a Discovery Call →" linking to `/discovery-call`. Voice rules in [`docs/voice-guide.md`](docs/voice-guide.md) are non-negotiable.
- **No page builder.** Block editor only. No Elementor, Divi, Bricks.
- **Block editor constraints.** Authors cannot pick arbitrary colors — palette is locked via `theme.json`. No pill radius on buttons.
- **Verification gate.** Before any page is marked done: Lighthouse mobile passes (LCP <2.5s, INP <200ms, CLS <0.1), schema validates in Google Rich Results Test, WCAG AA contrast verified on every text/background pair, screenshots taken at mobile + desktop.
- **You only edit files inside `wp-content/themes/kadence-oomph-child/` and `plugins/oomph-travel-core/`.** Never modify Kadence parent.

---

## Sequenced work plan

### Immediate (this round)

1. **Phase 4 docs cleanup.** Generate `docs/cro-backlog.md` and `docs/seo-checklist.md`. Stub `docs/deploy.md` and `docs/page-playbooks.md`. (No `AGENTS.md` — only Claude Code in use.)
2. **Theme rename `oomph-child` → `kadence-oomph-child`.** Local FS rename + symlink + `style.css` text domain + `CLAUDE.md` references + `.gitignore` references. **User action embedded:** update GH secrets `SG_PROD_THEME_PATH` and `SG_STAGE_THEME_PATH`. Then push develop → verify staging deploy → push main → verify production deploy.
3. **Phase 9.1 — `theme.json` + `tokens.css`.** Generate schema v3 `theme.json` from [`docs/brand-tokens.md`](docs/brand-tokens.md). Generate `assets/css/tokens.css` with all raw tokens + semantic tokens (`--surface-default`, `--signal-accent`, etc.) per docx §9.1.
4. **Phase 9.2–9.5 — Components.** `base.css` (reset + body + headings + prose + links), `components.css` (buttons + cards + sticky CTA + forms), `editor.css` (Gutenberg mirror). `register_block_style()` calls in `inc/kadence-overrides.php`.
5. **Phase 7 — Plugin scaffold.** `plugins/oomph-travel-core` with namespace `OomphTravel\Core`, three CPTs, two taxonomies, `class-schema.php`, `class-environment.php`, `class-clarity-guard.php`, `class-cli.php`. No ACF wiring yet.
6. **Phase 10.3 — Home page.** First real page. Forces tokens + components + schema + plugin to prove themselves end-to-end on one URL before we replicate the pattern across nine more pages.

### Deferred until after Home page is shipped

- Phase 5 WordPress foundation completions (Customizer, Rank Math wizard, Clarity production-only config, Fluent Forms SMTP setup).
- Phase 10.4–10.15 (About → Service hubs → Discovery Call → lead magnets → fees → testimonials → group cruise template → journal).
- Phase 11 SEO wiring (Rank Math schema config, `llms.txt`, robots.txt, Bing Webmaster, internal linking pass).
- Phase 12 Forms (Discovery Pre-Intake 3-step, Calendly inline class, Flodesk embeds, Clarity tags).
- Phase 13 Playwright (MCP config + committed `tests/e2e/` + GH Actions workflow).
- Phase 14–16 pre-launch, launch, ops cadence.

---

## How to use this file

Before any session, skim the **Phase status** table and **Locked decisions**. Open [`docs/source/build-plan-v2.docx`](docs/source/build-plan-v2.docx) for the playbook detail of whatever you're building. Update **Phase status**, **Locked decisions**, and **Open questions** as state changes — keep this file honest. Tag the repo (`vX.Y.Z-name`) at every meaningful milestone so we can roll back without guessing what state the working tree was in.
