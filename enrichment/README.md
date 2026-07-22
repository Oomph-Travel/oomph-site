# enrichment/ — prepared sailing payloads

Each `enrich-<slug>.json` maps to the Group Cruise post whose slug matches
(e.g. `enrich-silver-nova-2026-07-26.json` → `/group-cruises/silver-nova-2026-07-26/`).

**How they get applied:** WP admin → **Group Cruises → Enrichment Sync** pulls
this folder from GitHub `main` and applies new/changed files with one click
(SiteGround blocks inbound GitHub Actions runners, so the site pulls instead
of CI pushing). Also applicable individually via
`wp oomph enrich-sailing <id> --file=<file>`.

**Guarantees** (enforced by `class-enrich-engine.php`): applying never
publishes a draft and never overwrites a human-written "Why this sailing".
Files are tracked by blob SHA — editing a file makes it re-apply; unchanged
files stay quiet.

**Workflow:** payloads are prepared in Cowork/Claude sessions from the cruise
lines' published schedules, added here via PR (or GitHub's web editor), then
applied from the admin screen. Payload shape is documented in
`class-enrich-engine.php`.
