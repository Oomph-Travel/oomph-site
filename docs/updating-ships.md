# Updating the Ship Library — Batch Guide

**Who this is for:** anyone adding ships to oomphtravel.com's Ship Library.
**How often:** in batches of 3–6 ships, until the checklist (`docs/ship-library-checklist.md`) is done; then only for new ships or refreshed photos.
**How long:** about 10 minutes per batch (after the photos are collected).
**What you need:** a WordPress login (Editor or Administrator) and a **ship pack** — a `.zip` built from the Dropbox Ship Library folder.

Every sailing page automatically grows a **"Life on board"** section once a Ship record exists whose title matches the ship name exactly. Enter a ship **once** — every current and future sailing of it benefits.

---

## The short version

1. Collect photos + approve text in Dropbox (`Oomph Travel / Website / Ship Library`).
2. Build the batch zip (usually done in a Claude working session).
3. WP admin → **Ships → Import Ships** → upload → **Preview** → check → **Import for real**.
4. Open one live sailing page per ship, check the section, set the workbook Status to **Imported**.

---

## What a ship pack looks like

```
ship-pack-2026-08.zip
├── ship-library.xlsx          ← the master workbook (all 49 ships; only ships
│                                 with folders in this zip are imported)
├── Celebrity Reflection/
│   ├── 01-pool-deck-at-dusk.jpg
│   ├── 02-the-retreat-sundeck.jpg
│   └── … 6–12 photos
└── Celebrity Beyond/
    └── …
```

Rules the importer enforces (and warns about in Preview):

- **Folder name = exact ship name** as it appears on sailings ("Silver Nova"). A folder with no matching workbook row is skipped with a warning.
- **6–12 photos** per ship, `.jpg` / `.png` / `.webp`. More than 12: only the first 12 are used. Numbered prefixes (`01-`, `02-`) set the gallery order; the first image leads the band.
- **Licensed media only** — the line's trade/media portal or Eric's own photos. Set the workbook's Photo credit column accordingly.
- **Alt text** comes from the workbook's **Photos** tab. A photo with no row there gets alt text derived from its filename (`02-the-retreat-sundeck.jpg` → "The retreat sundeck aboard Celebrity Beyond.") — descriptive filenames matter.

## Step by step

1. **Log in** at oomphtravel.com/wp-admin.
2. **Ships → Import Ships.**
3. **Choose the zip** and click **Preview**. Nothing changes yet — you'll see one row per ship: create or update, photo count, fact count, and any warnings.
4. **Sanity-check:** every ship you packed is listed, photo counts look right, no "no matching row" warnings.
5. Click **Import for real.** Sideloading photos takes a moment — don't close the tab.
6. **Spot-check:** open one live sailing page per ship (search the ship name on `/group-cruises/`) and confirm the "Life on board" section renders: intro, facts table, gallery, credit.
7. In the workbook, set each ship's **Status → Imported** (the Read me tab's progress counters update).

## Text-only updates (no photos)

Upload the bare `ship-library.xlsx` instead of a zip. In this mode only rows whose **Status is "Approved" or "Imported"** are written — so a half-drafted intro never leaks onto the site. Galleries are untouched.

## Re-importing / fixing a ship

Safe and encouraged. The importer is idempotent:

- Re-importing an unchanged pack changes nothing (photos are recognized by content hash — no duplicate media, ever).
- Swap a photo in the Dropbox folder, re-pack, re-import: the gallery is replaced to match the folder exactly.
- Reword an intro or a fact, re-import: the text updates in place.
- Empty workbook cells never blank out existing content.

## Troubleshooting

| You see… | What to do |
|---|---|
| "No matching row in the workbook" | The folder name doesn't exactly match a Ship row. Fix the folder name in Dropbox (check spelling — e.g. "Millennium", two n's), re-pack. |
| "Sheet not found" | The zip's workbook isn't the ship-library one, or the Ships tab was renamed. |
| Upload fails / times out | The zip is too big for the server. Split the batch (3 ships per zip) or ask Claude to shrink the images (they should be ≤1600px WebP). |
| Section missing on a sailing page | The Ship title and the sailing's ship name differ — compare them character for character. The section renders only when they match. |
| Imported the wrong photos | Fix the folder in Dropbox, re-pack, re-import — the gallery is replaced to match. |

**Remember:** Preview never changes anything. When in doubt, preview again.

---

## One-page checklist

- [ ] Photos in each ship's Dropbox folder (6–12, licensed, numbered)
- [ ] Workbook row approved (intro + facts + Photos-tab alt text)
- [ ] Batch zip built
- [ ] Ships → Import Ships → Preview — every ship listed, no warnings
- [ ] Import for real
- [ ] Spot-check one sailing page per ship
- [ ] Workbook Status → Imported
