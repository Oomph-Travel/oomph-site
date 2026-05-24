#!/usr/bin/env bash
#
# Seed the Oomph Travel DB content/config that the git deploy does NOT carry:
# the Fluent Forms intake, the /discovery-call/ page, and Rank Math SEO meta.
#
# Idempotent — safe to re-run. Run AFTER the code has deployed to the target.
#
# Where to run:
#   • SiteGround (staging/prod): SSH in, transfer this scripts/ dir to the
#     server, cd to the WP root (…/public_html), then:
#         bash scripts/seed-content.sh
#     or pass the WP path explicitly:
#         bash scripts/seed-content.sh /home/USER/www/SITE/public_html
#   • Local by Flywheel: open the site shell (Local → right-click site → Open
#     Site Shell), cd to this repo, then: bash scripts/seed-content.sh
#
# Requires WP-CLI (`wp`) on PATH for the target site.

set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SEED="$DIR/seed-content.php"
WP_PATH="${1:-${WP_PATH:-$PWD}}"
WP=( wp --path="$WP_PATH" )

if [ ! -f "$SEED" ]; then
	echo "ERROR: cannot find seed-content.php next to this script ($SEED)" >&2
	exit 1
fi

echo "==> Target WP path: $WP_PATH"

# 1. Fluent Forms (free) — install + activate if needed.
if ! "${WP[@]}" plugin is-installed fluentform >/dev/null 2>&1; then
	echo "==> Installing Fluent Forms..."
	"${WP[@]}" plugin install fluentform --activate
elif ! "${WP[@]}" plugin is-active fluentform >/dev/null 2>&1; then
	echo "==> Activating Fluent Forms..."
	"${WP[@]}" plugin activate fluentform
else
	echo "==> Fluent Forms already active."
fi

# 2. Seed form + page + meta (idempotent).
echo "==> Seeding content..."
"${WP[@]}" eval-file "$SEED"

# 3. Flush caches.
"${WP[@]}" cache flush >/dev/null 2>&1 || true
"${WP[@]}" rewrite flush >/dev/null 2>&1 || true

echo "==> Done. Verify /discovery-call/ resolves and the form renders."
