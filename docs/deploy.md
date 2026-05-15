# Deploy — operational reference

Stub. Populated as the deploy pipeline evolves.

---

## Current pipeline (as of 2026-05-14)

GitHub Actions → rsync over SSH to SiteGround. Branch model:

- Push to `develop` → deploys to staging2.oomphtravel.com (automatic)
- Push to `main` → deploys to oomphtravel.com production (gated by `production` environment approval)

Workflow: [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml).

Branch ruleset on `main`: PR required, 0 approvals (solo dev), linear history, force-push blocked.

Cache purge uses `continue-on-error: true` and `||` fallbacks since SG Optimizer isn't installed on staging.

The pipeline does NOT deploy: WP database, plugins, uploads. Those flow via SiteGround Site Tools (DB) and WP admin (plugins/uploads).

---

## When to deploy

- Theme/plugin code changes: push and let CI deploy.
- Database / content changes: never via git. Use SiteGround's "Deploy Staging to Live" or pull production → local for development copies (script pending).
- Plugin installs: WP admin only, documented in `docs/plugins.md` (TODO).

---

## Post-deploy ritual

CI handles `wp cache flush` and SG Optimizer purge automatically. For manual verification after a release:

```bash
# SSH to staging or production (aliases TBD, see docx §8.3 if adopting)
ssh oomph-stage  "wp sg purge && wp cache flush && wp rewrite flush"
ssh oomph-prod   "wp sg purge && wp cache flush && wp rewrite flush"
```

---

## Rollback

```bash
# Find the previous good commit
git log --oneline main

# Reset main to it
git checkout main
git reset --hard <previous-good-sha>
git push --force-with-lease origin main
```

The force-push triggers a CI redeploy. The previous theme state returns within ~60 seconds. If the issue is **not** in theme code (e.g., a plugin update or DB migration), restore from SiteGround's daily backup instead.

---

## TODO

- Adopt docx §8.6 `scripts/pull-db.sh` and `scripts/pull-uploads.sh` adapted for Local by Flywheel paths (not DDEV) when first production-to-local mirror is needed
- Document plugin install list in `docs/plugins.md` per docx §5.5
- SSH config aliases (`oomph-stage`, `oomph-prod`) — set up when content-down workflow is needed
- WP-CLI aliases (`@stage`, `@prod`) — same trigger
