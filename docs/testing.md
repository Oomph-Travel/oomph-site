# Testing — end-to-end smoke tests

Plain-language guide to the automated tests that watch over the site.

## What these tests do

They're **smoke tests** — quick checks that the important pages load and the key
paths still work, run automatically against **staging** (`staging2.oomphtravel.com`).
They catch the kind of breakage that a template edit or plugin update can cause:
a page 500-ing, a missing "Start a conversation" button, the cabin quiz not
advancing, or the structured data (SEO schema) disappearing from a page.

They cover:

- **Every page type loads** (Home, About, the three service pages, Discovery Call,
  Journal, Client Stories, Cabin Quiz, Cruise Trends, Group Cruises) — returns a
  real page, shows its headline, and shows the "Start a conversation" button.
- **SEO schema is present** on each page type (TravelAgency + Person everywhere;
  Service on service pages; Event on a sailing; BlogPosting on a journal post).
- **Discovery Call** page — the intake form and the Calendly booking area render.
- **Cabin Quiz** — you can move from the intro through all seven questions to the
  email gate, and a result reveals.
- **Group Cruises** — the archive shows its filters and sailing cards; a single
  sailing page shows Event schema and **never leaks internal reference numbers**.

## What they deliberately DON'T do

**No form is ever actually submitted.** The tests stop right before hitting
"send," so they never create a fake lead, never email you, and never book a real
Calendly slot. They check that the forms *render and behave*, not that a
submission goes through.

## When they run (automatically)

- **After each update to staging** — whenever code is pushed to `develop` and
  deploys, the tests run against the freshly-deployed staging site.
- **Every night against production** — a scheduled run checks the live site
  (still submitting nothing).

### Known limitation: SiteGround's anti-bot protection (confirmed with SG support, 2026-07-21)

Both sites sit behind SiteGround's Anti-Bot system, which sometimes challenges
GitHub's shared datacenter IPs with an HTTP 202 CAPTCHA page — especially after
rapid repeated runs. SiteGround investigated (ticket, Jul 2026) and confirmed:
they won't exempt whole sites or whitelist GitHub's rotating IP pool, but they
**will whitelist up to 5 static IPs or 5 /24 ranges** if we ever route the test
runner through a static address (small VPS/proxy, ~$5–20/mo — decided against
for now). Practical upshot: CI runs are best-effort — at the normal cadence
(two slow, serialized runs/day) they pass; if a run fails with every page
"not loading" (202s), it hit the bot wall — **just re-run it**. A local
`npx playwright test` from a home IP is always reliable and is the
authoritative check.
- **On demand** — anyone can trigger a run from the repo's **Actions** tab →
  "E2E smoke (staging)" → **Run workflow** → choose staging or production.

They are **non-blocking**: a failing test reports a problem but never stops a
deploy or a release. If something fails, the run uploads a **Playwright report**
(in the Actions run's artifacts) with screenshots of what went wrong.

## Running them yourself (optional, on your Mac)

You need [Node.js](https://nodejs.org) installed (version 20+). In the Terminal:

```bash
cd ~/code/oomph-site
npm ci                              # one-time: install the test tool
npx playwright install chromium     # one-time: install the browser it drives
npx playwright test                 # run the tests (against staging)
npx playwright show-report          # open the results in your browser
```

To point the tests at a different site, set `OOMPH_BASE_URL` first:

```bash
OOMPH_BASE_URL=https://oomphtravel.com npx playwright test   # production
OOMPH_BASE_URL=http://oomph-local.local npx playwright test  # your Local site
```

## Where the tests live

- `playwright.config.ts` — configuration (default target, browsers, retries).
- `tests/e2e/` — the tests themselves, one file per area
  (`pages.smoke`, `schema`, `discovery`, `quiz`, `group-cruises`).
- `tests/e2e/fixtures/routes.ts` — the list of pages and expected schema; the
  place to add a new page.
- `.github/workflows/e2e.yml` — the automation that runs them.

All of this is **repo-root tooling** and is never deployed to the live theme.
