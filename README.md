# Oomph Travel

This is the WordPress rebuild of oomphtravel.com on SiteGround + Kadence Pro. All custom work lives in two places: the `kadence-oomph-child` theme at `wp-content/themes/kadence-oomph-child/` (presentation), and the `oomph-travel-core` plugin at `plugins/oomph-travel-core/` (CPTs, schema, environment guards). Deployment runs through GitHub Actions over SSH; Rank Math handles SEO and Microsoft Clarity covers CRO measurement.

See [BUILD-PLAN.md](BUILD-PLAN.md) for the working plan and [docs/source/build-plan-v2.docx](docs/source/build-plan-v2.docx) for the canonical 16-phase spec.

## Getting started

Local dev runs on WP Local (Local by Flywheel) at `~/Local Sites/oomph-local/`. The theme and plugin are symlinked from this repo into the WP install so edits propagate live.
