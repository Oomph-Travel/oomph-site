# Oomph Travel — WordPress Build Plan

**Volume I · MMXXVI**
A comprehensive plan for rebuilding **oomphtravel.com** on WordPress + SiteGround + Kadence Pro, deployed via GitHub Actions, with Claude Code as the paired developer.

Pairs with the Brand Book (Volume I, MMXXVI) and the 2026 SEO + CRO Foundations research.

---

## Contents

- [0. How to use this kit](#0-how-to-use-this-kit)
- [1. Pre-flight decisions](#1-pre-flight-decisions)
- [2. GitHub ↔ SiteGround connection (operational priority)](#2-github--siteground-connection-operational-priority)
- [3. CI/CD pipeline walkthrough](#3-cicd-pipeline-walkthrough)
- [4. Local development environment](#4-local-development-environment)
- [5. WordPress foundation](#5-wordpress-foundation)
- [6. Child theme scaffold](#6-child-theme-scaffold)
- [7. Brand token implementation](#7-brand-token-implementation)
- [8. Information architecture](#8-information-architecture)
- [9. Page build playbooks](#9-page-build-playbooks)
- [10. SEO implementation](#10-seo-implementation)
- [11. CRO implementation](#11-cro-implementation)
- [12. Validation & launch](#12-validation--launch)
- [13. Post-launch operating cadence](#13-post-launch-operating-cadence)
- [Appendix A — Claude Code prompt quick reference](#appendix-a--claude-code-prompt-quick-reference)
- [Appendix B — Reference URLs](#appendix-b--reference-urls)

---

## 0. How to use this kit

This repository contains four kinds of files:

| File | Purpose | Edit frequency |
|---|---|---|
| `CLAUDE.md` (root) | Claude Code's project memory — loaded every session | Rare; after the same mistake twice |
| `docs/*.md` | Brand tokens, voice, schema, CRO rules — loaded by Claude Code on demand | When the brand or rules evolve |
| `BUILD-PLAN.md` (this file) | Sequencing, procedures, and copy-paste prompts | When the plan changes |
| `wp-content/themes/oomph-child/**` | The actual site code | Constantly |

**First session read order:** this file → `CLAUDE.md` → `docs/brand-tokens.md` → `docs/voice-guide.md`. The schema and CRO docs are loaded on demand.

**Build run order:** Section 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8 → 9 → 10 → 11 → 12. Section 13 is operating cadence after launch.

**Prompt convention:** Claude Code prompts appear in fenced blocks tagged **Prompt → Claude Code**. Paste verbatim into Claude Code. Review the diff before accepting.

```text
**Prompt → Claude Code**
Example body.
```

---

## 1. Pre-flight decisions

### 1.1 Stack — confirmed

| Layer | Choice | Source |
|---|---|---|
| Hosting | SiteGround GrowBig | SEO research §6 |
| CMS | WordPress 6.x with block editor (Gutenberg) | SEO research §6 |
| Parent theme | **Kadence (free) + Kadence Pro bundle** *(you own this)* | SEO research §6 |
| Child theme | `oomph-child` — custom, all our work | This plan |
| SEO | Rank Math Free (Pro at month 6 only if needed) | SEO research §6 |
| Speed | SG Optimizer (free, SiteGround-native; beats WP Rocket on this stack) | SEO research §7 |
| Forms | Fluent Forms (free) for inquiry; Flodesk embeds for newsletter | SEO research §6 |
| Booking | Calendly inline embed (never modal) | SEO research §5 |
| Analytics | Site Kit by Google (GA4 + GSC) + Microsoft Clarity (free, unlimited) | SEO research §7–8 |
| Email | Flodesk *(existing)* | Eric's stack |
| CI/CD | GitHub Actions → rsync over SSH to SiteGround | This plan §3 |

### 1.2 Decisions to make on the record

**Domain.** Current oomphtravel.com gets replaced. We build on the SiteGround **staging** environment (free with GrowBig), then push to production on cutover day. 301 redirect map handles old URLs.

**GitHub visibility.** Private. Public adds zero value and exposes pre-launch decisions.

**Repo name.** `oomph-site` (matches local folder, no surprises).

**Branching strategy.** Two long-lived branches:
- `main` → production (oomphtravel.com)
- `develop` → staging (the SG staging URL)

Feature work happens on short-lived branches off `develop`, merged via PR. Direct pushes to `main` are blocked by branch protection.

**Dedicated SSH user.** Create a deploy-only SSH user in SiteGround Site Tools, distinct from your maintenance user, so the deploy key has minimum scope and can be rotated independently.

**Database.** The pipeline does **not** push database. Database flows are handled via SiteGround Site Tools → WordPress → Staging → **Deploy Staging to Live** (which copies the staging DB and uploads to production). Theme code flows via git. Content flows via the WP admin.

### 1.3 Accounts to confirm

- [ ] SiteGround GrowBig with `oomphtravel.com` provisioned
- [ ] GitHub account with private repo creation rights
- [ ] Domain registrar access (for the final DNS cutover)
- [ ] Google account → Search Console + Analytics + Business Profile + Site Kit
- [ ] Microsoft account → Clarity (free)
- [ ] Calendly account
- [ ] Flodesk *(existing)*
- [ ] Bing Webmaster Tools *(critical — 87% of ChatGPT Search citations come from Bing top-10)*
- [ ] Kadence Pro bundle *(owned)*

### 1.4 Out of scope for v1 (recorded so the question doesn't reopen)

- No page builder (Elementor, Divi, Bricks). Block editor only.
- No premium SEO suite — Rank Math Free is sufficient.
- No A/B testing platform — traffic too low for statistical significance. Revisit at 5,000+ monthly unique.
- No live chat — inappropriate for high-touch advisory.
- No countdown timers, fake scarcity widgets, exit-intent popups on mobile.
- No fonts from Google CDN — self-host Fraunces and Inter.
- No DS-Italy credential display until earned.

---

## 2. GitHub ↔ SiteGround connection (operational priority)

This is the part you specifically need running. Read it linearly. Don't skip steps.

### 2.1 What we're building, end to end

```
┌─────────────────┐   git push   ┌──────────────────┐   GitHub Actions   ┌──────────────────┐
│  Local clone    │ ───────────▶ │  GitHub repo     │ ─────────────────▶ │  SiteGround host │
│  (your Mac)     │              │  oomph-site      │   rsync over SSH   │  (staging or     │
│                 │              │  (private)       │                    │   production)    │
└─────────────────┘              └──────────────────┘                    └──────────────────┘
        ▲                                                                          │
        │                                                                          │
        └─────────────────────  Claude Code  ─────────────────────────────────────-┘
                          (paired developer, reads CLAUDE.md)
```

Push to `develop` → staging deploys automatically. Open a PR `develop → main`, review the diff, merge → production deploys automatically (with an optional approval gate via GitHub Environments).

### 2.2 Generate the SSH deploy keypair

This key is used **only** by GitHub Actions to write to SiteGround. Treat it like a password.

On your Mac:

```bash
# Generate a dedicated keypair — NO passphrase (GitHub Actions can't enter one)
ssh-keygen -t ed25519 -C "github-actions-deploy@oomphtravel" -f ~/.ssh/oomph_deploy_key

# When prompted for passphrase, just press Enter twice.
```

You now have two files:

- `~/.ssh/oomph_deploy_key` — **private** key (goes into a GitHub secret)
- `~/.ssh/oomph_deploy_key.pub` — **public** key (goes into SiteGround)

### 2.3 Add the public key to SiteGround (production AND staging)

Do this **twice** — once on the production site, once on the staging environment. They have separate SSH key managers.

**Production:**

1. SiteGround → **Websites** → Manage Site → **Site Tools**.
2. **Devs → SSH Keys Manager → Import**.
3. Paste the contents of `~/.ssh/oomph_deploy_key.pub` (the entire single line starting with `ssh-ed25519`).
4. Leave the IP whitelist empty (GitHub Actions runners use rotating IPs).
5. Save. SiteGround will show you the **SSH connection details** — copy these to a secure note:
   - **Username** (e.g., `u123-abc456`)
   - **Hostname** (e.g., `ssh.us123.siteground.us`)
   - **Port** (usually `18765`)

**Staging:**

1. Create the staging environment first if you haven't: Site Tools → **WordPress → Staging → Create Staging Copy**.
2. Once created, the staging environment has its own Site Tools panel (link from the staging row).
3. Repeat steps 2–5 from above inside the staging Site Tools.
4. Note the staging SSH details — they differ from production.

### 2.4 Confirm SSH access manually

Before going anywhere near GitHub, prove the key works from your terminal:

```bash
# Production
ssh -i ~/.ssh/oomph_deploy_key \
    -p 18765 \
    your-prod-user@your-prod-host \
    "echo 'Production SSH works' && wp --info"

# Staging
ssh -i ~/.ssh/oomph_deploy_key \
    -p 18765 \
    your-stage-user@your-stage-host \
    "echo 'Staging SSH works' && wp --info"
```

Expected: both connect without prompting for a password, run the echo, and print WP-CLI's version info. If you get "Permission denied (publickey)," the key isn't in SiteGround's SSH Keys Manager for that environment — recheck step 2.3.

### 2.5 Find the absolute theme paths

The deploy pipeline needs to know **exactly** where to write files. SSH in and find them:

```bash
# Production
ssh -i ~/.ssh/oomph_deploy_key -p 18765 your-prod-user@your-prod-host
cd ~ && find . -type d -name "themes" -path "*/wp-content/themes" 2>/dev/null
```

You'll see something like:

```
./www/oomphtravel.com/public_html/wp-content/themes
```

The full deploy target is:

```
/home/customer/www/oomphtravel.com/public_html/wp-content/themes/oomph-child
```

(Replace `customer` with your actual home directory user — `pwd` after `cd ~` will tell you.)

Repeat for staging. Note both paths.

### 2.6 Create the GitHub repository

On github.com → **New repository**:

- Name: `oomph-site`
- Visibility: **Public** *(originally specced as private; flipped to public — no pre-launch secrets live in the repo, and public unlocks free GitHub Actions minutes and easier sharing)*
- Initialize: leave **everything unchecked** (no README, no .gitignore, no license — we have ours)

After creation, GitHub shows a "quick setup" page with a remote URL. Copy the SSH URL: `git@github.com:YOUR-USERNAME/oomph-site.git`.

### 2.7 Initialize the local repo and push the scaffold

In a terminal, navigate to where you want the project to live (e.g., `~/code/`) and:

```bash
mkdir oomph-site && cd oomph-site
git init -b main
git remote add origin git@github.com:YOUR-USERNAME/oomph-site.git
```

Now hand off to Claude Code to scaffold the repo from the `oomph-build-plan` kit:

```text
**Prompt → Claude Code**
I have a kit of scaffolding files from BUILD-PLAN.md. Set them up in the current empty git repo.

Steps:
1. Copy these files into the repo root (preserving paths):
   - CLAUDE.md
   - BUILD-PLAN.md
   - .gitignore
   - docs/brand-tokens.md
   - docs/voice-guide.md
   - docs/schema.md
   - docs/cro-rules.md
   - .github/workflows/deploy.yml

2. Create empty skeleton files for the child theme so the structure is committable. Use .gitkeep in empty dirs:
   - wp-content/themes/oomph-child/style.css   (just the WordPress header block — see BUILD-PLAN.md §6)
   - wp-content/themes/oomph-child/functions.php   (just the skeleton from §6)
   - wp-content/themes/oomph-child/theme.json   ({"version": 3})
   - wp-content/themes/oomph-child/assets/css/.gitkeep
   - wp-content/themes/oomph-child/assets/js/.gitkeep
   - wp-content/themes/oomph-child/assets/fonts/.gitkeep
   - wp-content/themes/oomph-child/assets/images/.gitkeep
   - wp-content/themes/oomph-child/parts/.gitkeep
   - wp-content/themes/oomph-child/templates/.gitkeep
   - wp-content/themes/oomph-child/patterns/.gitkeep
   - wp-content/themes/oomph-child/inc/.gitkeep

3. Generate a short README.md for the repo with: project name, one-paragraph summary, link to BUILD-PLAN.md, "Getting started" pointing to BUILD-PLAN.md §4.

4. Show me `git status` after staging. Do NOT commit yet — I want to review.
```

Once you've reviewed and committed:

```bash
git add .
git commit -m "Initial scaffold — CLAUDE.md, docs, deploy workflow, child theme skeleton"
git push -u origin main
git checkout -b develop
git push -u origin develop
```

### 2.8 Configure GitHub secrets

Repo → **Settings → Secrets and variables → Actions → New repository secret**. Add each:

| Secret name | Value |
|---|---|
| `SG_SSH_PRIVATE_KEY` | The **entire** contents of `~/.ssh/oomph_deploy_key` (open in a text editor, include the `-----BEGIN OPENSSH PRIVATE KEY-----` and `-----END OPENSSH PRIVATE KEY-----` lines and the trailing newline) |
| `SG_PROD_HOST` | Production SSH hostname from §2.3 |
| `SG_PROD_USER` | Production SSH username |
| `SG_PROD_PORT` | `18765` (usually) |
| `SG_PROD_THEME_PATH` | Absolute path from §2.5, ending in `/wp-content/themes/oomph-child` |
| `SG_STAGE_HOST` | Staging SSH hostname |
| `SG_STAGE_USER` | Staging SSH username |
| `SG_STAGE_PORT` | Staging SSH port |
| `SG_STAGE_THEME_PATH` | Staging absolute path |

### 2.9 Configure branch protection

Repo → **Settings → Branches → Add branch protection rule** for `main`:

- [x] Require a pull request before merging
- [x] Required approvals: **0** *(solo dev — a self-approval gate adds friction without catching anything; the real pause is the production environment approval in §2.10, which can't be self-approved-away mid-deploy)*
- [x] Require status checks to pass before merging
- [x] Require linear history
- [x] Do not allow bypassing the above settings

### 2.10 Optional — production approval gate

For an extra safety pause before any production deploy:

Settings → **Environments → New environment** → name it `production`. Under "Deployment protection rules," add **Required reviewers** (yourself). Now every deploy to production waits for a manual "Approve" click in the Actions tab.

### 2.11 First deploy — to staging

Test the pipeline before touching production:

1. Actions tab → **Deploy to SiteGround** → **Run workflow** → choose `develop`.
2. Watch the run. Expect green checkmarks at each step.
3. SSH into staging and confirm `oomph-child` theme files arrived:

```bash
ssh -i ~/.ssh/oomph_deploy_key -p <stage-port> <stage-user>@<stage-host> \
    "ls -la ~/www/staging-oomphtravel.com/public_html/wp-content/themes/oomph-child"
```

4. In staging WP admin → **Appearance → Themes** → Activate **Oomph Child**.
5. Visit the staging URL — the theme should load. (Empty styles for now; we fill it in §6–§9.)

### 2.12 What can go wrong, and how to recover

| Symptom | Fix |
|---|---|
| `Permission denied (publickey)` on rsync step | Public key not in SiteGround SSH Keys Manager, or in the wrong environment (prod vs stage). |
| `Host key verification failed` | The workflow already runs `ssh-keyscan` — but if it fails, run it manually from your Mac once to refresh known_hosts: `ssh-keyscan -p 18765 -H your-host >> ~/.ssh/known_hosts` |
| Files arrive but theme doesn't show in WP admin | Check `style.css` header — needs `Theme Name`, `Template: kadence`, `Version`. See §6.1. |
| `wp sg purge` step fails with "could not find WordPress" | The `cd` in the cache-purge step uses three `..` to reach `public_html/` from the theme dir. If SiteGround changes directory structure, adjust the path in `.github/workflows/deploy.yml`. |
| Deploy succeeds but site shows old content | SiteGround Dynamic Cache. Force purge: Site Tools → Speed → Caching → Flush Cache, then hard-refresh browser. |
| Workflow runs but nothing changed on host | Check the rsync `--exclude` list — your changes may be excluded. Common offender: a file ending in `.md` (excluded by default). Move the change out of an excluded path. |

---

## 3. CI/CD pipeline walkthrough

The workflow in `.github/workflows/deploy.yml` does this on every push:

1. **Determines target environment.** `main` → production secrets. `develop` → staging secrets.
2. **Installs the SSH key.** Writes the private key from secrets to a temp file with `chmod 600`. Runs `ssh-keyscan` to trust the host.
3. **Rsyncs the child theme.** `wp-content/themes/oomph-child/` → SiteGround's theme directory. The `--delete` flag means files removed from git are removed on the host. Excludes git metadata, docs, dotfiles, node_modules.
4. **Purges SiteGround cache.** SSHs in and runs `wp sg purge && wp cache flush`.
5. **Cleans up.** Removes the temp SSH key.
6. **Writes a summary.** GitHub Actions run summary shows branch, commit, host.

The workflow uses `concurrency` to cancel an in-flight deploy when a newer push lands — prevents two deploys racing on the same branch.

### 3.1 What this pipeline does NOT do

- **Does not deploy the WordPress database.** Use SiteGround's **Deploy Staging to Live** for that when the staging content is ready.
- **Does not deploy plugins.** Plugins are installed via WP admin and documented in `docs/plugins.md` (you'll generate this in §5).
- **Does not deploy uploads** (`/wp-content/uploads/`). Those live on the host and are backed up by SiteGround daily.
- **Does not back up before deploy.** SiteGround does daily automatic backups. For high-stakes deploys, manually trigger an on-demand backup first: Site Tools → Security → Backups → **Create Backup**.

### 3.2 The day-to-day flow

```bash
# Start a feature branch
git checkout develop
git pull
git checkout -b feat/home-page-hero

# Work locally with Claude Code in the loop
# ... edits in wp-content/themes/oomph-child/ ...

# Commit and push
git add wp-content/themes/oomph-child/
git commit -m "Build home hero pattern"
git push -u origin feat/home-page-hero

# Open PR feat/home-page-hero → develop on GitHub
# After merge, GitHub Actions deploys to staging automatically
# Validate on staging URL

# When staging looks good, open PR develop → main
# After merge (and optional approval), GitHub Actions deploys to production
```

### 3.3 Rolling back a bad production deploy

If a production deploy breaks the site:

```bash
# Find the previous good commit
git log --oneline main

# Reset main to it
git checkout main
git reset --hard <previous-good-sha>
git push --force-with-lease origin main
```

The force-push triggers a redeploy. The previous theme state returns within ~60 seconds. If the issue is **not** in theme code (e.g., a plugin update or DB migration), restore from SiteGround's daily backup instead: Site Tools → Security → Backups → Restore.

---

## 4. Local development environment

Since you have Claude Code working, you likely have most of this. Skim and confirm.

### 4.1 Stack

| Tool | Why |
|---|---|
| Local by Flywheel **or** wp-env | Local WordPress instance for fast iteration |
| Node 20 LTS | In case we add a build step later |
| Git | Already installed |
| Claude Code | Already installed |
| iTerm2 / Warp | Terminal |

### 4.2 Symlink the child theme into your local WordPress

Your git repo lives in `~/code/oomph-site/`. Your local WordPress install (via Local) lives in `~/Local Sites/oomph-local/app/public/`. Symlink the child theme into the WordPress install so Claude Code edits show up instantly:

```bash
# Replace paths with yours
ln -s ~/code/oomph-site/wp-content/themes/oomph-child \
      ~/Local\ Sites/oomph-local/app/public/wp-content/themes/oomph-child
```

In local WP admin → Appearance → Themes → activate **Oomph Child**. Now you can edit in `~/code/oomph-site/`, save, refresh the local site, and see the change.

### 4.3 Claude Code session startup

When you `cd ~/code/oomph-site/` and run `claude`, Claude Code automatically reads `CLAUDE.md` and any `@`-imported docs. Verify with `/memory` inside Claude Code — you should see the project file and the four imports listed.

If imports are missing, the `@` paths in `CLAUDE.md` are wrong or the files don't exist. Fix and re-run `/memory`.

---

## 5. WordPress foundation

These steps happen in the WordPress admin on the **staging** environment.

### 5.1 Theme activation

You already own Kadence Pro. Confirm both parts are installed and activated:

1. Appearance → Themes → **Kadence** (parent) installed and activated. (The Oomph Child theme replaces it once we have child styles — keep Kadence as the activated parent for now while we scaffold.)
2. Plugins → **Kadence Blocks Pro** installed and activated.
3. Plugins → **Kadence Pro Theme Add-on** installed and activated.
4. License key entered: Settings → **Kadence License**.

We do **not** activate the Kadence-shipped child theme — we're building our own.

### 5.2 Plugin install list (in order)

| Plugin | Why | Required config |
|---|---|---|
| **Rank Math (free)** | SEO, schema, sitemap | §10 |
| **SG Optimizer** | Speed (SiteGround installs automatically) | §5.3 |
| **Site Kit by Google** | GA4 + GSC + PageSpeed inside WP | §10.5 |
| **Microsoft Clarity** | Heatmaps, recordings, frustration signals | §11.5 |
| **Fluent Forms (free)** | Discovery Call inquiry form | §11.2 |
| **Header Footer Code Manager** | Inject Flodesk + Calendly scripts cleanly | §11.4 |
| **Kadence Conversions** *(part of Kadence Pro)* | Native popups + announcement bars | §11.6 |

That's it for v1. No all-in-one security plugins beyond SiteGround Security Optimizer. No analytics aggregators. No page builders.

### 5.3 SG Optimizer baseline

Site Tools → Speed → SuperCacher (and the SG Optimizer plugin):

| Setting | Recommendation |
|---|---|
| Dynamic Cache | Enabled |
| Memcached | Enabled |
| NGINX Direct Delivery | Enabled |
| Frontend Optimization → HTML | Minify |
| Frontend Optimization → CSS | Minify + Combine |
| Frontend Optimization → JS | Minify + Combine (test carefully — disable if conflicts) |
| Media Optimization → WebP | Generate WebP copies |
| Media Optimization → Lazy Load | Enabled, exclude class `.hero-image` (we set `fetchpriority="high"` manually) |
| Environment → PHP version | 8.2 or latest stable |

Lighthouse before/after to confirm no regressions.

### 5.4 WordPress core settings

**Settings → General:**
- Site title: `Oomph Travel`
- Tagline: `Premium and luxury cruises, and custom European journeys`
- Timezone: `America/Los_Angeles`
- Date format: `F j, Y`

**Settings → Reading:**
- Your homepage displays: A static page → Home page (we'll create in §9)
- Posts page → Journal (we'll create in §9)
- Search engine visibility: **unchecked** on production, **checked** on staging

**Settings → Permalinks:**
- Custom structure: `/journal/%postname%/`

**Settings → Discussion:**
- Allow comments: **off**
- Allow link notifications: **off**

### 5.5 Document the plugin set

```text
**Prompt → Claude Code**
Create `docs/plugins.md` documenting the WordPress plugin stack.

For each installed plugin in BUILD-PLAN.md §5.2:
- Plugin name + version installed
- Free or paid (with license source)
- One sentence on what it does for Oomph
- Non-default settings we changed (just deltas — not the full config)

Use §5.2 as the canonical list. For settings, ask me where you're uncertain rather than guessing.
```

---

## 6. Child theme scaffold

### 6.1 `style.css` header

```css
/*
Theme Name:   Oomph Child
Theme URI:    https://oomphtravel.com
Description:  Custom child theme for Oomph Travel, built on Kadence. Brand-locked tokens, FSE patterns, schema and CRO baked in.
Author:       Oomph Travel LLC
Author URI:   https://oomphtravel.com
Template:     kadence
Version:      1.0.0
License:      Proprietary
Text Domain:  oomph-child
*/
```

That's all `style.css` needs — actual styles get enqueued from `assets/css/` (next section).

### 6.2 `functions.php` skeleton

```php
<?php
/**
 * Oomph Child — functions.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'OOMPH_CHILD_VERSION', '1.0.0' );
define( 'OOMPH_CHILD_PATH', get_stylesheet_directory() );
define( 'OOMPH_CHILD_URI',  get_stylesheet_directory_uri() );

require_once OOMPH_CHILD_PATH . '/inc/enqueue.php';
require_once OOMPH_CHILD_PATH . '/inc/schema.php';
require_once OOMPH_CHILD_PATH . '/inc/kadence-overrides.php';
require_once OOMPH_CHILD_PATH . '/inc/block-patterns.php';
require_once OOMPH_CHILD_PATH . '/inc/helpers.php';
```

Everything beyond one-liners goes into `/inc/*.php`. Keeps `functions.php` readable and makes it obvious to Claude Code which file to edit.

### 6.3 Scaffold prompt

```text
**Prompt → Claude Code**
Scaffold the oomph-child theme inside wp-content/themes/oomph-child/.

Create:
1. style.css with the header from BUILD-PLAN.md §6.1.
2. functions.php with the skeleton from §6.2.
3. Stub files in /inc/ — each with the ABSPATH guard at the top:
   - enqueue.php
   - schema.php
   - kadence-overrides.php
   - block-patterns.php
   - helpers.php
4. Asset folders (with .gitkeep): /assets/css/, /assets/js/, /assets/fonts/, /assets/images/.
5. Folder stubs: /patterns/, /parts/, /templates/.
6. Blank theme.json (`{ "version": 3 }`) — fills in §7.
7. screenshot.png placeholder (1200×900) — make a simple Bone-colored PNG with "Oomph Child" centered. If you can't generate a PNG, note that I need to add manually.

After scaffolding: `git status`, show diff. Do NOT commit.
```

### 6.4 Asset enqueue

```text
**Prompt → Claude Code**
Implement inc/enqueue.php for the oomph-child theme.

Hook into wp_enqueue_scripts at priority 20 (after Kadence parent).

Enqueue stylesheets in this order:
1. Parent theme style (Kadence's style.css) — use get_template_directory_uri() to reference parent
2. assets/css/tokens.css
3. assets/css/base.css
4. assets/css/components.css

Enqueue scripts with defer:
- assets/js/sticky-cta.js
- assets/js/form-events.js

Versioning: use filemtime() for cache busting (not OOMPH_CHILD_VERSION), so cache busts on every file edit.

Preload critical fonts via wp_head priority 1:
- Fraunces 300 woff2
- Inter 400 woff2

Add fetchpriority="high" to hero LCP images via a filter on wp_get_attachment_image_attributes that checks for the .is-hero-lcp class.

Remove WordPress emoji scripts/styles (they hurt CWV).

Docblock every function.
```

---

## 7. Brand token implementation

### 7.1 `theme.json` — block-editor source of truth

`theme.json` constrains the block editor to brand-locked colors, fonts, and spacing. No author can pick a random color from a wheel.

```text
**Prompt → Claude Code**
Generate theme.json (schema version 3) for the oomph-child theme.

Source of truth: docs/brand-tokens.md.

Requirements:
- settings.color.palette: all primary + neutral + secondary colors. Slugs: peacock-ink, terracotta-warm, true-ink, paper, bone, mist, stone, slate, charcoal, deep-peacock, muted-brick, warm-ochre, soft-sage, champagne, dusty-rose.
- settings.color.custom: false (lock authors to the palette)
- settings.color.duotone: false
- settings.typography.fontFamilies: Fraunces (slug: fraunces) + Inter (slug: inter), both referencing self-hosted woff2 in assets/fonts/.
- settings.typography.fontSizes: eyebrow, body-sm, body, lead, h3, h2, h1, display — matching the scale in brand-tokens.md.
- settings.typography.customFontSize: false
- settings.spacing.spacingScale: disabled
- settings.spacing.spacingSizes: the 11-step scale (--space-1 through --space-11)
- settings.layout.contentSize: "740px" (~72ch at 17px body); wideSize: "1280px"
- styles.color.background: "var:preset|color|bone"
- styles.color.text: "var:preset|color|true-ink"
- styles.typography.fontFamily: "var:preset|font-family|inter"
- styles.typography.lineHeight: "1.55"
- styles.elements.h1, h2: fontFamily fraunces, fontWeight 300 (h1), 400 (h2)
- styles.elements.link.color.text: "var:preset|color|peacock-ink"
- styles.elements.button:
  - color.background: "var:preset|color|terracotta-warm"
  - color.text: "var:preset|color|paper"
  - typography.fontFamily: inter
  - typography.fontWeight: 500
  - border.radius: "4px"  (NEVER pill — see docs/cro-rules.md)
  - spacing.padding: top/bottom 12px, left/right 24px

Validate against the WordPress block editor schema. Output the full theme.json.
```

### 7.2 `tokens.css` — CSS custom properties for everything else

```text
**Prompt → Claude Code**
Generate assets/css/tokens.css.

Output a single :root block declaring all tokens from docs/brand-tokens.md as CSS custom properties. Use the exact variable names: --color-peacock-ink, --space-1, --text-h1, etc.

Include:
- All colors (primary, neutrals, secondary, semantic)
- All spacing (--space-1 through --space-11)
- All radii (--radius-xs, sm, md, lg)
- Shadow tokens (--shadow-hairline, sm, md, lg)
- Font family tokens
- Font size tokens, mobile-first, with media queries that bump to desktop values at 768px
- --container-max: 1280px, --prose-max: 72ch

Also output a tokens.json at the repo root containing the same tokens — lets us share with Figma plugins, build tools, etc. without parsing CSS.
```

### 7.3 `base.css` — reset, typography, prose

```text
**Prompt → Claude Code**
Generate assets/css/base.css.

Include:
1. Modern reset (border-box, margin reset, line-height base, responsive images).
2. @font-face for Fraunces (variable font, opsz 9-144, wght 100-900) and Inter (variable, wght 100-900), self-hosted from /assets/fonts/. font-display: swap.
3. Body defaults:
   - background: var(--color-bone)
   - color: var(--color-true-ink)
   - font-family: var(--font-text)
   - font-size: var(--text-body)
   - line-height: 1.55
4. Heading styles using --font-display. Mobile-first scale; desktop bumps at min-width: 768px.
5. .prose utility class — constrains long-form content to var(--prose-max). Styles p, ul, ol, blockquote, hr inside .prose.
6. Link styles: Peacock Ink at rest, Terracotta Warm on hover. Underline with 4px offset.
7. :focus-visible: 2px solid Peacock Ink outline, 3px offset.
8. ::selection: Champagne background, True Ink text.

No Tailwind, no preprocessor. Pure CSS.
```

### 7.4 `components.css` — buttons, cards, forms, sticky CTA

```text
**Prompt → Claude Code**
Generate assets/css/components.css.

Components:
1. .btn (base): inline-flex, gap var(--space-2), padding 12px 24px, Inter 500, --text-body, border-radius var(--radius-sm) — 4px — never pill.
2. .btn--primary: background Terracotta Warm, color Paper. :hover slightly darker.
3. .btn--secondary: outlined Peacock Ink.
4. .btn--ghost: transparent, underline on hover only.
5. .btn-microcopy: under-button text, Slate, --text-body-sm.
6. .card: Paper bg, --radius-md, --shadow-sm at rest, --shadow-md on hover, padding var(--space-6).
7. .trust-strip: hairline top/bottom (Stone), flex centered, --text-eyebrow uppercase tracked +0.08em, Slate.
8. .sticky-cta: position fixed bottom, True Ink bg, Paper text, hidden on min-width 768px, safe-area-inset-bottom padding for iOS, z-index 100.
9. Form fields (.field, .field__label, .field__input, .field__error): single-column, 44px min-height, Stone border, focus state Peacock Ink with 2px outline, inline validation styles, privacy microcopy slot.
10. .scrim: absolute inset 0, linear-gradient(180deg, rgba(20,23,26,0.05), rgba(20,23,26,0.55)). Never glassmorphism. Never blur.

Verify WCAG AA 4.5:1 on every text/background combination before finalizing.
```

---

## 8. Information architecture

### 8.1 Site map (v1)

```
oomphtravel.com/
├── /                                    [Home]
├── /about/                              [Eric — the advisor]
├── /luxury-cruise-planning/             [Service hub — cluster pillar]
├── /custom-europe-travel/               [Service hub — cluster pillar]
│   └── /destinations/italy/
│   └── /destinations/puglia/
│   └── /destinations/uk/
├── /multi-generational-travel/          [Service hub — cluster pillar]
├── /group-cruises/                      [Index]
│   └── /group-cruises/mediterranean-2027/   [Example single]
├── /how-i-work/                         [Fees + process]
├── /journal/                            [Blog index]
│   └── /journal/<post-slug>/
├── /client-stories/                     [Testimonials + case studies]
├── /discovery-call/                     [Calendly inline]
├── /cruise-cabin-guide/                 [Lead magnet LP]
├── /italy-planning-guide/               [Lead magnet LP]
├── /privacy/
├── /terms/
└── /accessibility/
```

### 8.2 URL principles

- Lowercase, hyphenated, descriptive.
- No date-based blog URLs.
- Service hubs at root level (flatter URLs read more authoritative).
- Destinations under `/destinations/`.
- Group cruises under `/group-cruises/[destination]-[year]/`.

### 8.3 Primary navigation

| Label | URL |
|---|---|
| Cruise | `/luxury-cruise-planning/` |
| Custom Europe | `/custom-europe-travel/` |
| The Journal | `/journal/` |
| About | `/about/` |
| Start a conversation → | `/discovery-call/` *(CTA-styled, Terracotta button)* |

Five items, period. Multi-Gen lives one click deep from Custom Europe and from a card on the homepage. The Discovery Call CTA is styled as the brand's primary button, not a regular nav item.

### 8.4 Footer navigation

Four columns desktop / stacked mobile. **Footer ground color: Deep Peacock.** Type color: Paper. This is the Editorial Inversion combination.

| Col 1 | Col 2 | Col 3 | Col 4 |
|---|---|---|---|
| Cruise | The Journal | About Eric | hello@oomphtravel.com |
| Custom Europe | Client stories | How I work | +1 (360) 555-0184 |
| Multi-Gen | Destinations | Credentials | Port Angeles, WA |
| Group Cruises | Lead magnets | Privacy / Terms / Accessibility | |

### 8.5 Build sequence

Build in this order. Each page complete + validated before the next:

1. Global templates and parts (header, footer, trust strip)
2. Home
3. About
4. Luxury Cruise Planning (service hub)
5. Discovery Call
6. How I Work (fees)
7. Custom Europe Travel
8. Multi-Generational Travel
9. Journal index + single post template
10. Client Stories
11. Group Cruises index + single template
12. Lead magnet landing pages
13. Destinations (start as Journal posts under category Destinations; promote to standalone once each crosses 1,500 words + traffic floor)
14. Legal pages

---

## 9. Page build playbooks

The pattern is identical for each page: anatomy → keyword + schema → Claude Code prompt. Each prompt assumes Claude Code has read `CLAUDE.md`, `docs/brand-tokens.md`, `docs/voice-guide.md`, `docs/schema.md`, and `docs/cro-rules.md` first.

### 9.1 Global — Header

**Anatomy:** Container 1280px, padding `--space-4` vertical. Logo left (120px min). Five nav items right; last item is the primary CTA button. Sticky on scroll (Kadence Pro feature) with subtle Bone fade-in + hairline border-bottom. Mobile: logo + hamburger; drawer in Bone, Fraunces 300 nav links. Click-to-call link visible on mobile only.

```text
**Prompt → Claude Code**
Build parts/header.html.
Spec: BUILD-PLAN.md §9.1.

Requirements:
- Block markup (HTML comments wrapping core blocks)
- Site logo block from site identity
- Navigation block referencing primary nav menu (slug: primary-nav)
- Last nav item ("Start a conversation →") styled as .btn .btn--primary
- Sticky on scroll via Kadence's sticky header feature
- Mobile: Kadence mobile nav blocks
- Mobile-only click-to-call link (tel:+13605550184), right side near hamburger, 44pt tap target, fires GA4 event "phone_click"

Verify in WP admin: logo correct size, five items in order, CTA item styled as Terracotta button, mobile drawer opens, header sticks past 100px scroll.
```

### 9.2 Global — Footer

**Anatomy:** Deep Peacock background, Paper text. Top row: logo (mark only, inverse) + tagline "Life is short — travel with Oomph." in Fraunces italic 300. Middle: 4 columns from §8.4. Credentials strip below: CLIA · Nexion · Silversea Ultra-Luxury Specialist · BritAgent Pro in eyebrow caps, Stone color. Bottom: © + Port Angeles, WA + Privacy/Terms/Accessibility.

```text
**Prompt → Claude Code**
Build parts/footer.html.
Spec: BUILD-PLAN.md §9.2. Editorial Inversion combination per docs/brand-tokens.md.

Requirements:
- Group block, backgroundColor "deep-peacock", textColor "paper"
- Top row: 2 cols — left logo mark + tagline italic Fraunces 300; right empty (or small inquiry CTA at desktop)
- Middle: 4-column row from §8.4. Column headers Inter 500 eyebrow caps, Champagne, tracked +0.08em
- Credentials strip: centered, --text-eyebrow, Stone, middot separators
- Hairline divider: 1px solid rgba(217,197,166,0.2)
- Bottom row: © left, legal links right
- Link colors: Champagne at rest, Paper on hover
- Mobile: stack columns with --space-6 gap

Show rendered output before committing.
```

### 9.3 Home page

**Primary keyword:** "luxury cruise and custom Europe travel advisor"
**H1:** *Travel that's worth the trip.*
**Subhead:** *Premium and luxury cruises, and custom European journeys, planned by one named advisor who stays with you from the first call to the last flight home.*

**Anatomy top-to-bottom:**

1. **Hero** — The Hero combination (Bone · Peacock Ink · Terracotta Warm). Full-bleed real photograph from Eric's travels. Scrim gradient. H1 + subhead + primary CTA + microcopy.
2. **Trust strip** — Five credential lockups in eyebrow caps + "Port Angeles · WA"
3. **Who I help** — Three ICP cards. "Affluent couples planning a milestone" / "Multi-generational families" / "Returning cruisers ready for ultra-luxury"
4. **What I plan** — Three service tiles in The European Itinerary combination (one accent each — Soft Sage, Muted Brick, Champagne)
5. **Founder mini-bio** — Editorial Inversion. Portrait + name + role + two sentences in Fraunces italic 300 + link to /about/
6. **How it works** — Three steps (Discovery Call → Plan → Travel). Numeral + 4-word title + two sentences each
7. **Featured lead magnet** — Champagne background. 3D guide mockup left, headline + single-email form + CTA right. Privacy microcopy below
8. **Testimonials** — Two long-form, side by side. Photo + name + trip + date. Champagne background, Quiet Premium type
9. **Fees teaser** — One sentence ("Planning fees fund my undivided attention to your trip. Commissions don't change your price.") + ghost CTA → /how-i-work/
10. **From the Journal** — Three latest-post cards
11. **Final CTA block** — Editorial Inversion. Italic Fraunces callout "An evening in port, quietly." Primary CTA repeats
12. Footer

**SEO essentials:** Title `Luxury Cruise & Custom Europe Travel Advisor | Oomph Travel` (60). Meta `Premium and luxury cruises and custom European journeys, planned by one named advisor who stays with you from the first call to the last flight home.` (155). Schema: Organization + TravelAgency + Person + WebSite + AggregateRating. LCP image: hero photo, WebP <250KB, fetchpriority="high", explicit dimensions, never lazy. Word count: 700–1,000 visible.

**CRO essentials:** Primary CTA "Book a Discovery Call →" appears 3× (hero, lead magnet block, final block). Lead magnet form is only competing capture. Mobile sticky CTA from first scroll. No popups on initial visit from search.

```text
**Prompt → Claude Code**
Build templates/front-page.html.
Spec: BUILD-PLAN.md §9.3. Brand: docs/brand-tokens.md. Voice: docs/voice-guide.md (apply No List ruthlessly). Rules: docs/cro-rules.md R1–R15.

Approach:
1. Generate full block markup for the front-page template.
2. For each numbered section in the anatomy:
   - Use the appropriate signature combination
   - Real placeholder copy in Eric's voice (Fraunces italic for editorial lines, Inter for UI)
   - Image placeholders with descriptive alt text
   - Eyebrow text where applicable
3. Schema: output JSON-LD via the schema.php helper for Organization + TravelAgency + Person + WebSite. Reference docs/schema.md.
4. Hero image markup: AVIF+WebP+JPEG <picture> fallback, fetchpriority="high", explicit width/height, no loading="lazy".
5. Build the lead magnet block as a reusable pattern in patterns/lead-magnet-block.php.
6. Build testimonial-card.php and service-tile.php as reusable patterns.

After writing:
- List every alt text used
- List every copy block (headline, subhead, microcopy) so I can review for voice
- List every brand token reference so I can confirm we're not introducing one-off values

Do NOT commit. Show me a screenshot once rendered locally.
```

### 9.4 About — Eric

**Primary keyword:** "Eric Hempel travel advisor"
**H1:** *Hi, I'm Eric.*

**Anatomy:** Hero (Quiet Premium — Paper + Peacock Ink + Stone hairlines, portrait right) → "Why I do this" (Fraunces italic 300, Terracotta hairline rules above/below, specific origin hook) → "Who I work with" (three ICP paragraphs with negative qualifiers) → "What I know" (credentials list with logos + how each earned) → "Where I've been" (real Oomph photography grid, captions name place + date) → "How I work" (three-step inline summary linking to /how-i-work/) → Pull-quote testimonial about character → Final CTA.

**SEO:** Title `About Eric Hempel | Cruise & Europe Travel Advisor | Oomph Travel`. Person schema with full hasCredential, sameAs LinkedIn. 1,000–1,500 words.

```text
**Prompt → Claude Code**
Build the About page (templates/page-about.html OR page.html with conditional logic for /about/).
Spec: BUILD-PLAN.md §9.4. Voice: docs/voice-guide.md — "use 'I' not 'we', specifics beat adjectives, name the place, no superlatives."

Approach:
1. Generate block markup for all 8 sections.
2. Draft placeholder copy in Eric's voice. For credentials, use the exact list from docs/voice-guide.md. DS-Italy: "Currently completing" or omit.
3. "Where I've been" markup with photo+caption placeholders.
4. Schema: Person with full hasCredential, sameAs, memberOf. Pull from docs/schema.md.
5. Pull-quote testimonial: Fraunces italic 300 on Champagne.

When done:
- List every credential and whether currently earned
- List every adjective for No List audit
- List every photo caption to confirm none describe the photo literally
```

### 9.5 Service hub — Luxury Cruise Planning

This is the single most important SEO page — the cluster pillar for everything cruise. Every cruise post links here; this page links to all of them.

**Primary keyword:** "luxury cruise travel advisor"
**H1:** *Cruise planning, one cabin and one decision at a time.*

**Anatomy (11 sections):** Hero (The Hero) → Trust strip (Silversea + CLIA + Nexion only) → Who this is for (3 paragraphs with negative qualifiers) → What I actually do (concrete deliverables, not adjectives) → Why an advisor matters for cruises → Cruise lines/ships I know (logo row + one-line specialization per line) → How I work (3-step inline → /how-i-work/) → What it costs (honest fee block) → From the Journal — Cruise (6 related cluster posts) → Two cruise-specific testimonials → FAQ (8 question-formatted entries + FAQPage schema) → Final dual CTA.

**SEO:** Title `Luxury Cruise Travel Advisor | Silversea Specialist | Oomph Travel` (60). Schema: Service + FAQPage + BreadcrumbList. Word count: 1,800–2,500. Bidirectional cluster linking mandatory.

```text
**Prompt → Claude Code**
Build the Luxury Cruise Planning service hub.
This is the most important SEO page on the site — a content cluster pillar.

Spec: BUILD-PLAN.md §9.5. Voice: docs/voice-guide.md. Schema: docs/schema.md (Service + FAQPage + BreadcrumbList combined into one graph).

Approach:
1. Create patterns/service-hub.php — parameterized so it can serve Custom Europe and Multi-Gen with different copy.
2. Generate page content as block markup. Aim 1,800–2,200 visible words.
3. Draft all 8 FAQ entries in Eric's voice. Real questions Eric gets:
   - Why work with an advisor for a cruise when I can book directly?
   - Do you charge a planning fee?
   - Which cruise lines do you specialize in?
   - Can you get me a cabin that's sold out online?
   - What if I want a pre- or post-cruise extension?
   - Do you handle group cruises?
   - What's the difference between premium and ultra-luxury?
   - Do you book river cruises?
4. Schema: single JSON-LD graph combining Service + FAQPage + BreadcrumbList with @id references.
5. Trust strip: Silversea + CLIA + Nexion only (BritAgent on Custom Europe page).
6. List every blog post URL slug that should backlink to this page.

When done:
- Show FAQ entries in draft (I want to review voice before publish)
- Confirm Lighthouse mobile profile passes
```

### 9.6 Discovery Call — `/discovery-call/`

Single most important conversion page. Calendly inline, never modal.

**Primary keyword:** "book a travel advisor consultation"
**H1:** *Tell me about the trip.*
**Subhead:** *A free 20-minute call. We'll talk about what you have in mind, whether we're the right fit, and what comes next. No obligation.*

**Anatomy:** Hero (Bone, no image — form is the visual) → Calendly inline (700px desktop / 600px mobile) → "What to expect" (4 type-only bullets, 2 cols) → "Who I work best with" (pre-qualification block) → "If you're not ready" (soft alternative: download a guide) → FAQ (5 entries) → Two testimonials + credentials strip.

```text
**Prompt → Claude Code**
Build templates/page-discovery-call.html.
Spec: BUILD-PLAN.md §9.6.

Approach:
1. Custom page template (not default) — uses parts/header-slim.html with logo only, no nav.
2. Build parts/header-slim.html.
3. Embed Calendly inline via the official widget script. Pull script URL from Header Footer Code Manager, don't hardcode.
4. Generate page sections per the anatomy.
5. Calendly intake configuration is done in Calendly's UI. Document required questions in docs/calendly-config.md:
   - Name (required, single line)
   - Email (required)
   - What kind of trip? (radio: Cruise / Custom Europe / Multi-gen / Group cruise / I'm not sure)
   - When? (radio: Next 3 months / 3-6 / 6-12 / Over a year / Flexible)
   - Budget range (radio, optional: <$10K / $10-25K / $25-50K / $50K+ / Prefer not to say)
6. FAQ schema for the 5 FAQs.
7. Calendly scheduled-event callback fires GA4 generate_lead event with lead_source: 'discovery_call' and a Microsoft Clarity custom tag. Add to assets/js/form-events.js.

When done:
- Confirm Calendly inline loads on mobile (test viewports 320, 375, 414, 768)
- Confirm GA4 event fires on test booking
- Confirm Lighthouse mobile profile passes
```

### 9.7 How I Work — `/how-i-work/`

**H1:** *How I work, and what it costs.*

**Anatomy:** Hero (Quiet Premium) → "The problem the fee solves" (lead with this, not the fee) → Fee table (Cruise from $300, Custom Europe from $500, Multi-Gen from $750, Group cruise $0 to traveler) → "What's included" → "Commissions" (one paragraph explaining they don't change traveler price) → "The process, step by step" (7 numbered steps from Discovery Call through "Welcome home call") → "Things I don't do" (disqualifying honesty) → FAQ (6 entries) → Final CTA.

```text
**Prompt → Claude Code**
Build /how-i-work/.
Spec: BUILD-PLAN.md §9.7. Tone: candid, plainspoken.

1. Standard page template.
2. Generate content per the anatomy. Fee numbers are placeholders for Eric to confirm.
3. R45: lead with the PROBLEM the fee solves, not the fee. Eric's voice.
4. "Things I don't do" — keep specific. Disqualifying honesty builds trust.
5. Schema: Service + FAQPage.

Voice check: read aloud — if it sounds like a brochure, rewrite.
```

### 9.8 Custom Europe Travel — service hub

Same structure as §9.5, different copy. European Itinerary combination. FAQ entries: rental cars vs. drivers, summer vs. shoulder season, multi-country pace, restaurant access, multi-country itineraries, combining with cruise.

```text
**Prompt → Claude Code**
Build /custom-europe-travel/.
Reuse patterns/service-hub.php from §9.5. Structure identical — only copy, photography placeholders, schema specifics, FAQs change.

Spec: BUILD-PLAN.md §9.8. Signature combination: The European Itinerary.

8 FAQ entries to draft (60-90 words each in Eric's voice):
1. Why work with an advisor for Europe when I can book hotels directly?
2. Do you charge a planning fee for Europe?
3. Which countries do you specialize in?
4. Do you arrange private drivers and guides?
5. Can you book restaurants and private experiences?
6. What's the best time to visit Italy/UK/France?
7. Do you do multi-country itineraries?
8. What if I want to add a cruise before or after?

The last one is a soft cross-link to the Cruise hub. Confirm bidirectional pillar-cluster linking.
```

### 9.9 Multi-Generational Travel — service hub

**H1:** *Family trips that work for everyone.*

High-empathy page. FAQ does most of the work — anticipates real planning pain.

```text
**Prompt → Claude Code**
Build /multi-generational-travel/.
Reuse patterns/service-hub.php. Differentiator: empathy. FAQs answer genuine pain points, not marketing speak.

Spec: BUILD-PLAN.md §9.9.

FAQ entries (each acknowledges the problem is real, describes Eric's approach, avoids jargon):
1. What if grandma can't keep up with the pace?
2. How do you handle dietary restrictions across three generations?
3. Can teens have independence on the trip?
4. What if family members live in different cities and need different flights?
5. Multi-gen cabins on cruises — are they real?
6. What's the typical group size you plan for?
7. How do you handle disagreements about itinerary?
8. Do you plan multi-gen Europe and multi-gen cruises differently?

Schema additions: Service.audience = "Multi-generational families planning a trip together." Named entity mentions: Silversea Royal Suites (multi-bedroom), Borgo Egnazia, Inverlochy Castle.

Hero: real Oomph photograph of a multi-gen moment if available. Avoid "happy family" stock per docs/voice-guide.md.
```

### 9.10 Journal — index + single

**Index:** Page title + subhead → featured post (full-width, real photograph, eyebrow "Field Note · NN") → 3-column grid of cards (paginated) → right sidebar (newsletter, category filters, credentials).

**Single post:** Eyebrow → H1 → byline (photo + name + role + date published + last updated) → TL;DR block (Champagne bg, Fraunces italic 300) → featured image full-bleed → prose constrained to 72ch → mid-post lead magnet (after first major section) → end-of-post author bio + primary CTA + 3 related posts → schema (Article + Person + BreadcrumbList).

```text
**Prompt → Claude Code**
Build the Journal index (templates/archive.html for the journal category or templates/home.html) and templates/single.html.

Spec: BUILD-PLAN.md §9.10.

Index:
- 3-column grid with featured top card (full-width)
- Each card: 16:9 thumbnail, eyebrow, Fraunces 400 title, 2-line dek, byline + date
- Right sidebar: Flodesk newsletter, category filters, credentials

Single:
- Standard prose template with the anatomy
- TL;DR block: Champagne bg, Fraunces italic 300 lead with "TL;DR" eyebrow
- patterns/post-byline.php (reusable, includes Person schema reference)
- Mid-post lead-magnet uses patterns/lead-magnet-block.php from §9.3
- Related posts: 3 in same category by recency, exclude current

Schema: Article + Person + BreadcrumbList in single graph.
Prose: .prose class constrains to --prose-max 72ch.

Show me a rendered example with placeholder content — verify type hierarchy, line length, byline pattern.
```

### 9.11 Client Stories — `/client-stories/`

**H1:** *What clients say.*
**Anatomy:** Hero (aggregate stat + featured testimonial in Fraunces italic 300 on Champagne) → testimonials grouped by trip type (Cruise / Europe / Multi-Gen / Milestone) in 2-column grid → third-party review links → "Earn a place here" soft CTA → final Discovery Call CTA.

```text
**Prompt → Claude Code**
Build /client-stories/.
Spec: BUILD-PLAN.md §9.11.

1. Page markup per the anatomy.
2. patterns/testimonial-card.php — reusable, accepts: photo URL, name, trip description, date, quote.
3. Output Review + AggregateRating schema extending the existing TravelAgency Organization graph (don't create new graph). Pull from docs/schema.md.
4. Featured testimonial: Fraunces italic 300 on Champagne.
5. Third-party review buttons: outlined, platform name + arrow.
6. "Earn a place here" is the only place we use "you" not "I."

Testimonials are real — don't fabricate. Use placeholders with structured slots so Eric swaps in real content before launch.
```

### 9.12 Group Cruises — index + single

Single highest conversion-intensity page type. Event schema is the most undervalued schema in the niche.

```text
**Prompt → Claude Code**
Build group cruise system. Custom post type + index + single template.

CPT: register in inc/post-types.php
- Slug: group_cruise
- has_archive: true, rewrite slug: group-cruises
- Supports: title, editor, thumbnail, custom-fields
- Custom fields: ship_name, line_name, region, depart_date, return_date, depart_port, return_port, cabins_remaining, starting_price, itinerary_pdf

Single template: templates/single-group_cruise.html
- Hero: ship+destination+dates headline, "Hosted by Eric Hempel," real scarcity bar reading cabins_remaining, dual CTA (Reserve / Download Itinerary PDF)
- Trip overview paragraph
- Day-by-day accordion (Kadence Blocks Pro Accordion block, not third-party)
- Cabin tier pricing table with single supplement
- 3-column What's Included / Not Included
- Group exclusive perks
- Inline mini-bio of Eric
- Trip protection / deposit policy
- FAQ section (10–15 entries — see below)
- Past group testimonials
- Final CTA + scarcity reminder

FAQ entries in priority order: single supplement, cancellation, included/not, host onboard time, mandatory group activities, insurance, deposit schedule, pre/post extensions, first-time cruiser fit, solo traveler fit, dress code, air, transfers, group dining, special occasions.

Schema: Event + Offer + Place as a graph. Validate in Rich Results Test. Event @type triggers rich SERP features.

Index: list upcoming group cruises (depart_date in future) ascending. Past cruises in "Past trips" section at the bottom.

Generate example page "Mediterranean 2027" with placeholder content. Confirm Event schema validates.
```

### 9.13 Lead magnet landing pages

Two magnets in v1: `/cruise-cabin-guide/` and `/italy-planning-guide/`.

```text
**Prompt → Claude Code**
Build templates/page-lead-magnet.html.
Spec: BUILD-PLAN.md §9.13.

1. Uses parts/header-slim.html — logo only, no nav (per docs/cro-rules.md R33-ish — no main nav on lead-magnet pages).
2. Form: Flodesk inline embed. Placeholder script ID Eric replaces per page.
3. Hero: 3D guide cover mockup placeholder (PNG slot with CSS perspective transform) + outcome headline + subhead + single email field + CTA "Send me the guide" (per R43, single-field only).
4. "What's inside": 3-5 specific outcomes (not features). "How to spot the noisy cabins on Silver Nova" beats "Cabin selection tips."
5. Mini-bio of Eric (3 sentences + photo).
6. "Who this is for" — 2 sentences.
7. Single testimonial.
8. Second form at bottom with privacy reassurance.

Thank-you redirect configured in Flodesk per magnet — document URL pattern in docs/lead-magnets.md.

Create two page instances after the template: /cruise-cabin-guide/, /italy-planning-guide/. Each with page-specific copy and Flodesk form ID placeholder.

List the two thank-you page URLs so we can build them.
```

### 9.14 Legal pages

```text
**Prompt → Claude Code**
Build /privacy/, /terms/, /accessibility/ pages.

Each: standard page template with structural sections only.
- /privacy/: Information We Collect, How We Use It, Cookies, Third-Party Services (Flodesk, GA4, Clarity, Calendly), Your Rights (GDPR, CCPA, opt-out), Contact.
- /terms/: Services Offered, Booking Process, Fees and Refunds, Limitation of Liability, Governing Law (Washington State), Contact.
- /accessibility/: Our Commitment, Conformance Standard (WCAG 2.1 AA), Known Issues, Feedback Contact.

Add "Last updated: <date>" stamp at the top.

At the bottom of each, HTML comment:
<!-- LEGAL REVIEW REQUIRED. Placeholder content. Have an attorney review before launching publicly. -->

After publishing, set each to noindex via Rank Math per-page setting.
```

---

## 10. SEO implementation

### 10.1 Rank Math setup wizard

| Setting | Value |
|---|---|
| Site type | Other Business Site |
| Business type | LocalBusiness → **Travel Agency** |
| Logo | Upload Oomph Travel logo |
| Social profiles | LinkedIn, Instagram |
| Title separator | `|` |
| Sitemap | Enabled, exclude attachments + noindex pages |
| Schema | Article default; we override per page type |
| Indexable post types | Pages, posts, group_cruise |
| Noindex | Attachments, author archives, search results, 404 |

### 10.2 Schema implementation

```text
**Prompt → Claude Code**
Implement schema output via inc/schema.php.

1. Hook wp_head priority 5 (before Rank Math).
2. Detect page type: is_front_page(), is_singular() with slug/post-type checks.
3. For each page type, output the JSON-LD graph from docs/schema.md.
4. Absolute URLs (home_url()) in @id fields.
5. Single source of truth helpers: ERIC_PERSON_SCHEMA array, OOMPH_CREDENTIALS array.
6. dateModified from the_modified_date('c'); datePublished from get_the_date('c').
7. Debug HTML comment showing which graph emitted (only when WP_DEBUG is true), then <script type="application/ld+json">.

Validate every page type in Google's Rich Results Test.

Document in docs/schema-mapping.md: Page Type → Schema Types → Source File.
```

### 10.3 robots.txt

```text
**Prompt → Claude Code**
Set robots.txt via Rank Math's editor.

Production:

User-agent: *
Disallow: /wp-admin/
Disallow: /?s=
Allow: /wp-admin/admin-ajax.php

Sitemap: https://oomphtravel.com/sitemap_index.xml

# AI crawlers — explicitly allowed
User-agent: GPTBot
Allow: /
User-agent: ClaudeBot
Allow: /
User-agent: PerplexityBot
Allow: /
User-agent: Google-Extended
Allow: /

Staging (different file):

User-agent: *
Disallow: /
```

### 10.4 Site Kit + Google Search Console + Bing

1. **Site Kit** plugin → setup wizard → connect Search Console, GA4, PageSpeed Insights. Skip AdSense.
2. **GA4 Key Events:** mark `generate_lead`, `lead_magnet_download`, `newsletter_signup`, `phone_click`, `email_click`, `sticky_cta_click`.
3. **Cross-domain measurement:** include `calendly.com` so Discovery Call bookings attribute correctly.
4. **Bing Webmaster Tools** (critical — 87% of ChatGPT Search citations come from Bing top-10):
   - Sign up at bing.com/webmasters → add site.
   - Verify via meta tag (add to wp_head via the theme).
   - Submit `https://oomphtravel.com/sitemap_index.xml`.

### 10.5 Google Business Profile

1. business.google.com → create profile **Oomph Travel**.
2. Primary category: Travel Agency. Secondary: Cruise Agency.
3. Add up to 20 service areas (West Coast cities + Seattle, Portland, SF, LA, NYC).
4. Real address (hidden allowed for SAB).
5. Solicit Google reviews post-trip — target 5–10 quality reviews in Year 1.

---

## 11. CRO implementation

### 11.1 Sticky mobile CTA bar

```text
**Prompt → Claude Code**
Implement sticky mobile CTA bar.

CSS: .sticky-cta already in components.css.
JS: assets/js/sticky-cta.js

Requirements:
1. Show after 100px scroll on viewport < 768px.
2. Hide when hero CTA is in viewport (IntersectionObserver — don't show both).
3. Hide when inline form is in viewport.
4. Tap fires GA4 sticky_cta_click with from_page parameter, then navigates to /discovery-call/.
5. iOS safe-area-inset-bottom respected.

PHP in inc/enqueue.php: enqueue sticky-cta.js everywhere EXCEPT /discovery-call/ and lead-magnet landing pages.

Markup in parts/footer.html (aria-hidden until JS reveals):
<aside class="sticky-cta" aria-hidden="true">
  <a class="btn btn--primary" href="/discovery-call/">Book a Discovery Call →</a>
</aside>

Confirm:
- Visible on iPhone, Pixel, iPad simulated viewports
- Doesn't obscure form submit buttons
- Tap target ≥44pt including padding
```

### 11.2 Discovery Call inquiry form (Fluent Forms — alternate to Calendly)

```text
**Prompt → Claude Code**
Configure 3-step Discovery Call inquiry form in Fluent Forms.

Admin task — document in docs/forms.md, not code.

Step 1 — What kind of trip?
- Radio: Cruise / Custom Europe / Multi-generational / Group cruise / Not sure yet
- Radio: When? Next 3 months / 3-6 / 6-12 / 12+ / Flexible

Step 2 — Who's traveling?
- Number: How many travelers?
- Radio (optional, not required per R39): Budget — <$10K / $10-25K / $25-50K / $50K+ / Prefer not to say
- Yes/No: Worked with an advisor before?

Step 3 — Where to reach you?
- Text: Your name (required)
- Email: Email address (required)
- Phone: Phone number (optional — phone reduces CVR ~5% per R39)
- Textarea: Anything else? (optional)

Single column, inline validation with ✓ confirmations.

Confirmation: "Thanks. I'll be in touch within one business day. — Eric"
Email notification: hello@oomphtravel.com
Flodesk integration: tag "Inquiry — discovery_call_form"

GA4 event on submit: generate_lead with lead_source: 'inquiry_form'.
Clarity custom tag: inquiry_form_submitted.

Privacy microcopy under form: "We respect your inbox. Used solely to plan your trip."

Generate patterns/discovery-form.php — shortcode wrapper for embedding on service pages.
```

### 11.3 Calendly inline (on Discovery Call page)

Implemented as part of the Discovery Call page template — see §9.6.

### 11.4 Flodesk newsletter

```text
**Prompt → Claude Code**
Build reusable Flodesk newsletter blocks.

patterns/newsletter-inline.php — for long-form content (single column, full-width within prose container).
patterns/newsletter-block.php — section block (image + headline + larger form, Champagne background).

Both:
- Single email field
- CTA: "Get the dispatch →" (a few times a year)
- Microcopy: "A few dispatches a year on cruises and Europe. Nothing else."
- Privacy: "We respect your inbox."

Embed mechanism: Header Footer Code Manager loads Flodesk script globally once. Pattern outputs:
<div class="ff-form-embed" data-ff-formid="<id>"></div>

Document the form ID slot in docs/forms.md.
```

### 11.5 Microsoft Clarity

1. Install plugin → enter project ID → save.
2. Link Clarity to GA4: Clarity Settings → Setup → Google Analytics → Connect. Adds Clarity Playback URL as a GA4 custom dimension. Watch any conversion's recording from inside GA4.
3. Set custom tags on form submits, sticky CTA fires, lead magnet downloads.
4. Review heatmaps weekly. Watch the first 10 session recordings of any page that just launched.

### 11.6 Kadence Conversions popups (v1: one popup only)

```text
**Prompt → Claude Code**
Configure exit-intent lead-magnet popup in Kadence Conversions.

Admin task — document in docs/cro-popups.md.

Configuration:
- Trigger: Exit intent
- Devices: Desktop only (≥768px viewport) per R36
- Pages: Display on home, all service pages, journal index. EXCLUDE /discovery-call/, /cruise-cabin-guide/, /italy-planning-guide/.
- Frequency: Once per visitor per 30 days
- Goal: Email capture via embedded Flodesk form
- Content: Small modal (≤500px wide), Champagne bg, 3D guide mockup left, single-field email form right, microcopy below

Verification:
- After config, test in incognito on desktop — confirm popup on exit
- Test on mobile 375px — confirm popup does NOT appear
- Test on /discovery-call/ desktop — confirm popup does NOT appear
- Confirm no intrusive interstitial violation per R33-R37
```

---

## 12. Validation & launch

### 12.1 Performance budget

| Metric | Budget | Tool |
|---|---|---|
| LCP (mobile) | < 2.5s, target < 2.0s | PageSpeed Insights |
| INP | < 200ms | PageSpeed Insights |
| CLS | < 0.1 | PageSpeed Insights |
| Total page weight (mobile) | < 1.5MB | WebPageTest |
| Number of requests | < 50 | WebPageTest |
| Hero image | < 250KB | Manual |
| Third-party scripts | ≤ 4 (GA4, Clarity, Calendly, Flodesk) | Manual |

### 12.2 Pre-merge QA checklist

```text
**Prompt → Claude Code**
Generate docs/qa-checklist.md — checklist for any page changed in a PR.

PERFORMANCE
- [ ] Lighthouse mobile run on staging URL
- [ ] LCP < 2.5s (target < 2.0s)
- [ ] INP < 200ms
- [ ] CLS < 0.1
- [ ] Performance score ≥ 90

ACCESSIBILITY
- [ ] Lighthouse accessibility score ≥ 95
- [ ] WCAG AA contrast on every text/bg pair (WebAIM Contrast Checker)
- [ ] Tab order logical
- [ ] All interactive elements keyboard-reachable
- [ ] Focus styles visible
- [ ] Form labels associated
- [ ] Image alt text (decorative = alt="")
- [ ] Tap targets ≥ 44×44pt mobile

SEO
- [ ] Title < 60 chars, unique, keyword in first 30
- [ ] Meta description 150–160 chars, unique
- [ ] One H1 with primary keyword
- [ ] Schema validates in Rich Results Test (zero errors)
- [ ] Canonical link present and correct
- [ ] No console errors

CONTENT / VOICE
- [ ] No words from docs/voice-guide.md No List
- [ ] First-person voice ("I" not "we")
- [ ] No superlatives
- [ ] Specific entities mentioned
- [ ] dateModified visible on content pages

CTA / CRO
- [ ] One primary CTA above the fold
- [ ] Sticky mobile CTA bar present (except excluded pages)
- [ ] Primary CTA appears 3× per page
- [ ] Microcopy below every CTA
```

### 12.3 Cross-browser / device matrix

iPhone Safari (latest iOS + 1 back) · Android Chrome (latest) · Desktop Chrome · Desktop Safari · Desktop Firefox · iPad Safari. Test back 2 major iOS versions — clientele skews 50–75 and older Safari is common.

### 12.4 Pre-launch checklist

```text
**Prompt → Claude Code**
Generate docs/pre-launch-checklist.md.

CONTENT
- [ ] All page-type pages have real (not placeholder) copy
- [ ] Real photography swapped for all hero images
- [ ] Real testimonials swapped in
- [ ] Real fees confirmed on /how-i-work/
- [ ] DS-Italy credential verified before display

SEO
- [ ] Every page has unique title and meta
- [ ] Schema validates on 5 representative pages
- [ ] /sitemap_index.xml renders
- [ ] robots.txt allows AI crawlers per §10.3
- [ ] 301 redirect map prepared (old URLs → new URLs)

PERFORMANCE
- [ ] Every page hits CWV budget on mobile
- [ ] Total page weight < 1.5MB on every page
- [ ] WebP/AVIF on all hero images

ANALYTICS
- [ ] GA4 firing, Key Events configured
- [ ] GSC verified, sitemap submitted
- [ ] Bing Webmaster verified, sitemap submitted
- [ ] Microsoft Clarity firing
- [ ] Clarity-GA4 integration confirmed

CONVERSION
- [ ] Calendly inline tested on mobile + desktop
- [ ] Discovery Call form tested end-to-end
- [ ] Lead magnet delivery confirmed via Flodesk
- [ ] Sticky CTA visible on mobile
- [ ] Exit-intent popup tested

LEGAL
- [ ] Privacy, Terms, Accessibility pages reviewed by attorney
- [ ] Cookie consent banner active
- [ ] GDPR + CCPA compliance verified

INFRASTRUCTURE
- [ ] SSL active on production
- [ ] HSTS header configured
- [ ] SG Optimizer baseline applied
- [ ] On-demand backup created before launch
- [ ] DNS lowered TTL 48 hours before cutover
```

### 12.5 Launch sequence (cutover day)

```text
Production deploy day — run in order:

1. Final on-demand backup of production WP install (Site Tools → Security → Backups)
2. Final on-demand backup of staging
3. SiteGround: Deploy Staging to Live (copies staging DB + uploads → production)
4. Confirm production WP admin: theme activated, plugins activated, settings correct
5. Push develop → main on GitHub. Approve production deploy in Actions.
6. Confirm GitHub Actions deploy completes green
7. SSH into production, run `wp sg purge && wp cache flush`
8. Visit production URL in incognito — smoke test every page-type page
9. Run Lighthouse on home, About, Cruise hub, Discovery Call
10. Validate schema on 5 pages with Rich Results Test
11. Submit sitemap to Search Console + Bing
12. Test Calendly booking, lead magnet form, sticky CTA, click-to-call
13. Confirm GA4 + Clarity firing
14. Send announcement email via Flodesk
15. Watch Clarity recordings for first 50 visitors — pay attention to mobile
```

---

## 13. Post-launch operating cadence

### 13.1 Daily (5 min)
- GSC errors check
- Uptime check
- Clarity frustration-flag recordings

### 13.2 Weekly (30–60 min)
- Form submissions / source
- GA4 conversion trends
- Top 10 landing pages
- Ranking changes (GSC top 20 queries)

### 13.3 Monthly (2–4 hrs)
- Content performance review
- Refresh 2–3 underperforming pages
- Backlink profile (Ahrefs Webmaster Tools)
- Core Web Vitals 28-day CrUX rolling
- Email metrics (open rate, CTR)
- Lead-to-booking funnel %
- Looker Studio commentary

### 13.4 Quarterly (1 day)
- Full technical SEO audit (Screaming Frog crawl, schema validation, broken links, redirect chains)
- Competitor SERP review
- User testing with 3–5 real users
- Google Business Profile audit
- CRO test backlog review

### 13.5 Annual (2–3 days)
- YoY strategic review
- Full ROI by channel and content cluster
- Brand positioning review
- Persona vs. actual booked clients audit
- Refresh strategic keyword targets

### 13.6 KPIs to track

**TOF (leading):** impressions, avg ranking, AI Overview citations, organic traffic by landing page, direct traffic, brand search MoM.
**MOF:** engagement rate, scroll depth, time on service pages, lead-magnet downloads, email opt-ins, open rate (target 25–35%).
**BOF (lagging):** **Discovery calls booked is the #1 KPI** · inquiries · proposals sent · call→proposal rate · proposal→booking rate (target 25%+) · avg booking value · inquiry-to-booking time (luxury avg 90 days, sometimes 6–12 months).
**Loyalty:** repeat booking rate (target 35%+), referral rate (~25%), NPS, CLV.
**Efficiency:** CAC by channel, LTV:CAC ≥ 3:1.

---

## Appendix A — Claude Code prompt quick reference

Every prompt assumes Claude Code has read `CLAUDE.md` and the four `docs/*.md` files.

| Phase | Prompt location | What it builds |
|---|---|---|
| Setup | §2.7 | Initial repo scaffold |
| Setup | §5.5 | docs/plugins.md |
| Theme | §6.3 | Child theme directory tree |
| Theme | §6.4 | inc/enqueue.php |
| Tokens | §7.1 | theme.json |
| Tokens | §7.2 | tokens.css + tokens.json |
| Tokens | §7.3 | base.css |
| Tokens | §7.4 | components.css |
| Page | §9.1 | parts/header.html |
| Page | §9.2 | parts/footer.html |
| Page | §9.3 | templates/front-page.html + 3 patterns |
| Page | §9.4 | templates/page-about.html |
| Page | §9.5 | service hub + patterns/service-hub.php + 8 FAQs |
| Page | §9.6 | templates/page-discovery-call.html + Calendly config doc |
| Page | §9.7 | /how-i-work/ |
| Page | §9.8 | /custom-europe-travel/ + 8 FAQs |
| Page | §9.9 | /multi-generational-travel/ + 8 FAQs |
| Page | §9.10 | Journal index + single template |
| Page | §9.11 | /client-stories/ + testimonial pattern |
| Page | §9.12 | Group cruise CPT + index + single + example page |
| Page | §9.13 | Lead-magnet template + two instances |
| Page | §9.14 | Legal pages |
| SEO | §10.2 | inc/schema.php + docs/schema-mapping.md |
| SEO | §10.3 | robots.txt |
| CRO | §11.1 | Sticky CTA bar (JS + markup) |
| CRO | §11.2 | Fluent Forms 3-step inquiry form + docs/forms.md |
| CRO | §11.4 | Newsletter patterns |
| CRO | §11.6 | Exit-intent popup + docs/cro-popups.md |
| QA | §12.2 | docs/qa-checklist.md |
| QA | §12.4 | docs/pre-launch-checklist.md |

That's **30+ copy-paste-ready prompts**.

---

## Appendix B — Reference URLs

**Anthropic / Claude Code**
- Claude Code overview: https://docs.claude.com/en/docs/claude-code/overview
- CLAUDE.md memory docs: https://docs.claude.com/en/docs/claude-code/memory

**WordPress / Kadence**
- Kadence theme: https://www.kadencewp.com
- WordPress block-theme docs: https://developer.wordpress.org/themes/block-themes/
- theme.json reference: https://developer.wordpress.org/themes/global-settings-and-styles/

**SiteGround**
- SSH Keys Manager tutorial: https://www.siteground.com/tutorials/ssh/
- WordPress staging: https://www.siteground.com/tutorials/wordpress/staging/

**SEO / Schema**
- Rank Math: https://rankmath.com/kb/
- Google Rich Results Test: https://search.google.com/test/rich-results
- schema.org: https://schema.org

**Performance**
- PageSpeed Insights: https://pagespeed.web.dev
- WebPageTest: https://www.webpagetest.org
- web.dev (CWV): https://web.dev/vitals/

**Analytics**
- Site Kit: https://sitekit.withgoogle.com
- Microsoft Clarity: https://clarity.microsoft.com

**Accessibility**
- WCAG 2.2: https://www.w3.org/TR/WCAG22/
- WebAIM Contrast Checker: https://webaim.org/resources/contrastchecker/

---

*The brand is the sum of these — not the parts in isolation.*

*— end of Volume I —*
