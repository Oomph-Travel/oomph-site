# Prompt for the next Claude Code session — paste everything below the line

---

Work in `/Users/sonicgoo/code/oomph-site` (start Claude Code there, not in another directory — a previous session accidentally opened in `~/Developer/Gardezen`). Read `CLAUDE.md` first and follow it.

## Context: verified repo state (2026-08-09)

`develop` is clean, 169 commits, **last commit 2026-07-28**. Nothing has shipped since. `develop` is 127 ahead of `main`, reconciled, and the next promotion merges clean.

The link-in-bio page at `/links/` is **live on production** (`https://oomphtravel.com/links/`) and verified. History, all merged: PR #43 (the page, from a git bundle), #45 + #46 (Group Cruises row: a live sailing count was wrong twice — 900 vs the archive's 731, then "338 sailings" — and ended as the hardcoded string "The sailings I'm hosting"), #44 (production release). WP page ID 2036, slug `links`, template binds by slug. The page is `noindex, follow` (verified: exactly one robots tag, no Rank Math conflict). All 11 links resolve. e2e suite passes 36/36 against both staging and production.

**Resolved on 2026-07-28 — do not redo these.** The `/links/` e2e spec landed (`tests/e2e/links.spec.ts`, commit 6712441) and the stale `page-links.php` docblock was corrected (commit 243ce48). Earlier versions of this file listed both as open.

Facts that will save you time:

- Staging is `staging2.oomphtravel.com` (NOT `staging.oomphtravel.com`, which doesn't resolve). Staging WP page ID is 1508.
- Deploys: push to `develop` → staging, PR `develop`→`main` + environment approval → production. The rsync in `.github/workflows/deploy.yml` ships ONLY the child theme + oomph-travel-core plugin and excludes `docs/` and `*.md`.
- The repo squash-merges promotions, so after every `develop`→`main` release you must reconcile (`git merge origin/main` into develop, keep develop's side of any docs conflict) or the next promotion PR shows as CONFLICTING with a misleading 100+-file three-dot diff. Always judge a promotion by `git diff origin/main origin/develop` (two-dot, tree diff), never three-dot.
- On production, SG Optimizer combines all CSS — `oomph-links-page` won't appear in page source. To verify theme CSS is live, fetch the `siteground-optimizer-combined-css-*.css` file and grep for the selectors.
- Staging and production hold different content: staging has 0 upcoming hosted sailings, production has ~338. Staging is a weak proxy for anything sailing-count-shaped.
- `docs/audits/2026-07-25-lcp-workstream.md` documents that Lighthouse LCP on this site is bimodal (~3s fast mode / ~6.7s slow mode, network-path artifact). Always use median of 5 runs; `npm run audit:lh` does this.
- e2e: `OOMPH_BASE_URL=https://staging2.oomphtravel.com npx playwright test` (Chromium already installed). Tests never submit forms.

## Open work, in priority order

### 1. LCP on `/links/` — the Fluent Forms CSS is sitewide because of the footer

Production median 3.19s (5 runs: 2.94/3.01/3.19/6.73/6.73) vs the 2.5s target; even the fastest run misses. CLS passes (0.083). On production the featured image is only 64KB, so the JPEG is NOT the driver — Lighthouse points at render-blocking CSS (~268ms) and unused CSS (~190ms) from the 186KB SG Optimizer combined stylesheet. Fluent Forms is ~43KB of that.

**Correction to earlier notes (2026-08-09):** the previously recorded fix — "dequeue Fluent Forms CSS except on pages that use a form" — does not work as written. `inc/footer.php` line 99 embeds a Fluent Forms "Newsletter Signup" form in the **sitewide footer**, so there is no page without a form. The conditional dequeue has nothing to bite on until the footer embed is removed first.

Decision taken with Eric on 2026-08-09: **remove the footer newsletter form entirely.** It is email-only, has no Flodesk connection, and its sole action is an admin email notification (`scripts/seed-content.php` section 4b). Entries live only in the WordPress database — export them to CSV before touching anything.

Only three pages legitimately use Fluent Forms, all bound by slug: `/discovery-call/` ("Discovery Call Intake"), `/trip-quiz/` ("Cabin Quiz"), `/cruise-travel-trends/` ("Cruise Travel Trends"). After the footer embed is gone, dequeue Fluent Forms CSS and JS everywhere except those three.

Watch out: `.oomph-footer__grid` at `assets/css/components.css:662` is hardcoded to four columns (`1.4fr 1fr 1fr 1.4fr`). The breakpoint overrides at lines 753 and 756 already collapse to `1fr 1fr` and `1fr` and need no change.

Target: combined stylesheet drops from ~186KB to roughly ~143KB. Judge the result on real-visitor Core Web Vitals in Search Console, not lab scores — that is the standing decision.

### 2. Seeder ordering bug

A latent bug skips SEO metadata on a brand-new page slug. It has not bitten yet. It will, the next time a page is created.

### 3. Featured-image content issues (Eric's action, remind him)

(a) The production featured image `CEL_ML_Ship_Exterior.jpg` is cruise-line stock, and the card auto-follows the newest Journal post; the `oomph_links_featured_post_id` filter can pin a better post. (b) Featured images have empty `alt` text in the media library (WCAG AA gap) — both the Japan post image (prod) and the first-premium-cruise image (staging).

### 4. Housekeeping

PR #44 (the production release) is titled "Develop" with an empty body — backfill it as the release record. Delete merged remote branches: `feat/links-page`, `fix/links-sailing-count`, `fix/links-row-copy`.

### 5. Confirm with Eric

Has the Instagram profile link been pointed at `https://oomphtravel.com/links`? That was the whole point and only he can do it. Still unconfirmed as of 2026-08-09.

## Permissions gotchas

In non-interactive/auto sessions, `gh pr merge` and sometimes `git commit`/`git push` on protected-feeling actions get blocked by the permission classifier — `.claude/settings.local.json` in this repo has `Bash(git *)`, `Bash(gh auth *)`, `Bash(gh api *)` but nothing for `gh pr`. Don't burn time retrying; hand the exact command to Eric instead. Production deploys additionally require his approval click in GitHub Actions (environment `production` has required reviewers).

Constraints that always apply: never push to `main` directly; production only via PR from `develop` after Eric has seen staging; never edit CTA copy/destination/microcopy; ask before installing anything; if a step surprises you, stop and ask.
