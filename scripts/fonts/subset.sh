#!/usr/bin/env bash
#
# Subset the brand variable fonts to the glyphs the site actually uses.
#
#   npm run fonts
#
# The files in src/ are the full faces as downloaded — Inter ships 2,849
# mapped glyphs (Greek, Cyrillic, Vietnamese, symbols) and Fraunces 624, which
# is why the four woff2s came to 1.16 MB and were the single heaviest payload
# on every page. Nothing outside Latin is ever rendered: the 2026-07 LCP audit
# measured 761 KB of fonts on /group-cruises/, a page with no images at all,
# against a 9.9s LCP.
#
# Output goes to the theme's assets/fonts/ under the same filenames, so the
# @font-face rules in tokens.css are untouched. src/ is repo-root tooling and
# is never rsynced to SiteGround — only the subsets ship.
#
# Re-run after replacing anything in src/. Requires: pip3 install fonttools brotli
#
set -euo pipefail

cd "$(dirname "$0")"
SRC="src"
DEST="../../wp-content/themes/kadence-oomph-child/assets/fonts"

if ! command -v pyftsubset >/dev/null 2>&1; then
  echo "pyftsubset not found — run: pip3 install fonttools brotli" >&2
  exit 1
fi

# What to keep. Beyond the usual Latin ranges this deliberately carries the
# twelve non-ASCII characters the brand uses — the → in every primary CTA
# ("Start a conversation →"), the · in credential lines and eyebrows, the em
# dash in the tagline, and the ✓ in form validation. Miss any of those and the
# browser silently renders them from a fallback face, which reads as a typo.
#
#   U+0000-00FF  Basic Latin + Latin-1  (covers é · § and the ASCII bulk)
#   U+0100-024F  Latin Extended-A/B     (European place names — Kraków, Đurđevac)
#   U+0300-036F  Combining diacriticals
#   U+2000-206F  General Punctuation    (— – ' ' " " … •)
#   U+2190-2193  Arrows                 (→)
#   U+2713       Check mark             (✓ — form inline validation)
UNICODES="U+0000-00FF,U+0100-024F,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,\
U+0300-036F,U+2000-206F,U+2074,U+20AC,U+2122,U+2190-2193,U+2212,U+2215,U+2713,U+FEFF,U+FFFD"

# Axes to pin before subsetting, per family. Subsetting glyphs alone only got
# Fraunces down 20% — for a 4-axis face the variation data, not the glyph
# count, is the bulk of the file.
#
# Fraunces: SOFT (softness) and WONK (wonky alternates) are pinned at their
# defaults. Nothing varies them — no rule in assets/css/ sets
# font-variation-settings, and browsers only auto-vary the registered `opsz`
# axis, never custom ones. So the rendered result is byte-identical while the
# file halves. `opsz` and `wght` stay live: font-optical-sizing defaults to
# auto, and the display sizes depend on it.
#
# Inter: nothing is pinned. Pinning `opsz` would save a further ~52 KB but
# optical sizing is applied automatically at every size, so that is a real
# typographic change, not a free one. Clamping `wght` to the 100-600 the CSS
# names would save ~8 KB and break <strong> (700) in journal prose.
pin_for() {
  case "$1" in
    fraunces-*) echo "SOFT=0 WONK=1" ;;
    *)          echo "" ;;
  esac
}

total_before=0
total_after=0
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

for src in "$SRC"/*.woff2; do
  name="$(basename "$src")"
  out="$DEST/$name"
  before=$(wc -c < "$src")

  # Pin first (if this family has dead axes), then subset the result. Keeping
  # all layout features preserves kerning and the ligatures Fraunces relies on
  # at display sizes.
  pins="$(pin_for "$name")"
  if [ -n "$pins" ]; then
    # shellcheck disable=SC2086
    python3 -m fontTools.varLib.instancer "$src" $pins -o "$tmp/$name.ttf" >/dev/null 2>&1
    subset_input="$tmp/$name.ttf"
  else
    subset_input="$src"
  fi

  pyftsubset "$subset_input" \
    --output-file="$out" \
    --flavor=woff2 \
    --unicodes="$UNICODES" \
    --layout-features='*' \
    --notdef-outline \
    --recommended-glyphs

  after=$(wc -c < "$out")
  total_before=$((total_before + before))
  total_after=$((total_after + after))
  printf '  %-42s %6s KB → %5s KB  (−%d%%)\n' \
    "$name" "$((before / 1024))" "$((after / 1024))" \
    "$(( 100 - (after * 100 / before) ))"
done

printf '\n  %-42s %6s KB → %5s KB  (−%d%%)\n' "total" \
  "$((total_before / 1024))" "$((total_after / 1024))" \
  "$(( 100 - (total_after * 100 / total_before) ))"
