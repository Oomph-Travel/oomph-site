# Prompt for the next Claude Code session — paste everything below the line

---

Work in `/Users/sonicgoo/code/oomph-site` (start Claude Code there, not in another directory — a previous session accidentally opened in `~/Developer/Gardezen`). Read `CLAUDE.md` first and follow it.

## Context: what already happened (2026-07-28)

The link-in-bio page at `/links/` is **live on production** (`https://oomphtravel.com/links/`) and verified. History, all merged: PR #43 (the page, from a git bundle), #45 + #46 (Group Cruises row: a live sailing count was wrong twice — 900 vs the archive's 731, then "338 sailings" — and ended as the hardcoded string "The sailings I'm hosting"), #44 (production release). WP page ID 2036, slug `links`, template binds by slug. The page is `noindex, follow` (verified: exactly one robots tag, no Rank Math conflict). All 11 links resolve. e2e suite passes 36/36 against both staging and production.

Facts that will save you time:

- Staging is `staging2.oomphtravel.com` (NOT `staging.oomphtravel.com`, which doesn't resolve). Staging WP page ID is 1508.
- Deploys: push to `develop` → staging, PR `develop`→`main` + environment approval → production. The rsync in `.github/workflows/deploy.yml` ships ONLY the child theme + oomph-travel-core plugin and excludes `docs/` and `*.md`.
- The repo squash-merges promotions, so after every `develop`→`main` release you must reconcile (`git merge origin/main` into develop, keep develop's side of any docs conflict) or the next promotion PR shows as CONFLICTING with a misleading 100+-file three-dot diff. As of the last session the branches are reconciled and the next promo merges clean. Always judge a promotion by `git diff origin/main origin/develop` (two-dot, tree diff), never three-dot.
- On production, SG Optimizer combines all CSS — `oomph-links-page` won't appear in page source. To verify theme CSS is live, fetch the `siteground-optimizer-combined-css-*.css` file and grep for the selectors.
- Staging and production hold different content: staging has 0 upcoming hosted sailings, production has ~338. Staging is a weak proxy for anything sailing-count-shaped.
- `docs/audits/2026-07-25-lcp-workstream.md` documents that Lighthouse LCP on this site is bimodal (~3s fast mode / ~6.7s slow mode, network-path artifact). Always use median of 5 runs; `npm run audit:lh` does this.
- e2e: `OOMPH_BASE_URL=https://staging2.oomphtravel.com npx playwright test` (Chromium already installed). Tests never submit forms.

## Open work, in priority order

1. **LCP on `/links/` fails CLAUDE.md's bar** — production median 3.19s (5 runs: 2.94/3.01/3.19/6.73/6.73) vs 2.5s target; even the fastest run misses. CLS passes (0.083). On production the featured image is only 64KB, so the JPEG is NOT the main driver there; Lighthouse points at render-blocking CSS (~268ms) and unused CSS (~190ms) from the 186KB SG Optimizer combined stylesheet. Investigate before optimizing images. On staging the image IS heavy (166KB JPEG, no WebP sibling).
2. **Add e2e coverage for `/links/`** — the suite predates the page and covers none of it. A spec should assert: page renders (`.oomph-links__inner` present, not the blank default), exactly one robots meta and it says `noindex, follow`, the featured card exists, the primary CTA ("Start a conversation" → `/discovery-call/`) is present, all row/footer links resolve. Model it on `tests/e2e/pages.smoke.spec.ts`.
3. **Stale docblock in `page-links.php`** — the header comment still says the Group Cruises row "counts live sailings"; #46 replaced that with a fixed string. Fix the comment (this file is on production; it's a comment-only change but still goes through develop → staging → PR).
4. **Featured-image content issues (Eric's action, remind him):** (a) the current production featured image `CEL_ML_Ship_Exterior.jpg` is cruise-line stock — CLAUDE.md forbids generic stock for featured imagery, and the card auto-follows the newest Journal post; the `oomph_links_featured_post_id` filter can pin a better post. (b) Featured images have empty `alt` text in the media library (WCAG AA gap) — both the Japan post image (prod) and the first-premium-cruise image (staging).
5. **Housekeeping:** PR #44 (the production release) is titled "Develop" with an empty body — backfill it as the release record. Delete merged remote branches: `feat/links-page`, `fix/links-sailing-count`, `fix/links-row-copy`.
6. **Confirm with Eric** whether the Instagram profile link has been pointed at `https://oomphtravel.com/links` — that was the whole point and only he can do it.

## Permissions gotchas from last session

In non-interactive/auto sessions, `gh pr merge` and sometimes `git commit`/`git push` on protected-feeling actions get blocked by the permission classifier — `.claude/settings.local.json` in this repo has `Bash(git *)`, `Bash(gh auth *)`, `Bash(gh api *)` but nothing for `gh pr`. Don't burn time retrying; hand the exact command to Eric instead. Production deploys additionally require his approval click in GitHub Actions (environment `production` has required reviewers).

Constraints that always apply: never push to `main` directly; production only via PR from `develop` after Eric has seen staging; never edit CTA copy/destination/microcopy; ask before installing anything; if a step surprises you, stop and ask.
