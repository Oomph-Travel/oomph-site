# Updating Group Cruise Sailings — Monthly Guide

**Who this is for:** anyone keeping the Group Cruises section of oomphtravel.com current.
**How often:** once a month, when Travel Leaders Network (TLN) sends the new sailings file.
**How long:** about 20–30 minutes.
**What you need:** a WordPress login for the site (Editor or Administrator), and the new TLN **All Sailings** file (an Excel `.xlsx`). You do **not** need to open, edit, or reformat the file.

---

## The short version

1. Log in to the website admin.
2. Go to **Group Cruises → Import Sailings**.
3. Upload the TLN file → **Preview** → check the numbers → **Import for real**.
4. Go to **Group Cruises → Drafts** → write each new sailing's short blurb and **Publish** the ones you want live.

That's it. The detailed steps are below.

---

## Step 1 — Log in

Go to **oomphtravel.com/wp-admin** and sign in.

## Step 2 — Open the Import screen

In the left-hand menu, click **Group Cruises**, then **Import Sailings**.

## Step 3 — Upload the file and Preview

1. Click **Choose File** and select the TLN **All Sailings** file (the `.xlsx` is fine exactly as it arrived — no need to save it as anything else).
2. Leave **"Move sailings that have already departed back to draft"** checked. (This quietly tidies up cruises that have already sailed.)
3. Click **Preview**.

> **Preview never changes anything.** It just reads the file and shows you what a real import *would* do.

## Step 4 — Check the preview

You'll see a small summary like:

| | |
|---|---|
| New sailings | e.g. 40 |
| Updated | e.g. 380 |
| Skipped | e.g. 3 |
| Past sailings that would be moved to draft | e.g. 12 |

**Quick sanity check:**
- **New + Updated** should be a believable number — usually tens to a few hundred.
- **Skipped** should be small (a handful, usually a row with a missing or odd date).
- If **New is 0**, or **Skipped is huge**, or you see a yellow warning about missing columns — **stop** and contact Eric. Something's off with the file, and nothing has been changed yet.

If it looks reasonable, continue.

## Step 5 — Import for real

Click **Import for real**. You'll get a confirmation summary showing what was added, updated, and retired.

**What happens automatically — you don't need to do anything for these:**
- **Existing sailings** are refreshed with the latest dates and details. **Their write-ups are never touched.**
- **Sailings that already departed** are moved to draft, so they drop off the site.

## Step 6 — Write blurbs and publish the new sailings

New sailings come in as **drafts** so nothing goes public before it's ready.

1. Click the **Group Cruises → Drafts** link (shown in the confirmation, or use the left menu → **Group Cruises**, then the **Drafts** filter at the top).
2. Open a new draft sailing. In the main text box you'll see a placeholder line:
   `WHY THIS SAILING: Eric writes 2–3 sentences here before publishing`
3. Replace it with **2–3 short sentences** on why this sailing is worth featuring. Keep it:
   - **First person** — "I", never "we".
   - **Specific** — name the ship, the place, what makes it special.
   - **Plain** — no marketing fluff or hype words.
   *(If you're unsure of the wording, save it as a draft and ask Eric to review before publishing.)*
4. *(Optional but nice)* On the right, set a **Featured Image** (this becomes the page's hero photo), and if it's a Distinctive Voyages sailing you can add a **Shore event image** in the Group Cruise panel.
5. Click **Publish**.

You only publish the sailings you actually want on the site — you don't have to publish every draft.

---

## Troubleshooting

| You see… | What to do |
|---|---|
| "Sheet not found" (and a list of sheet names) | Make sure you uploaded the TLN file that has the **All Sailings** tab. If it does and this still happens, send the file to Eric. |
| A yellow "columns were not found" warning | The file may be the wrong export. Don't import — check with Eric. |
| A large **Skipped** number | Dates probably didn't read correctly. Don't import — send the file to Eric. |
| You imported something by mistake | Nothing is deleted — sailings are only added, updated, or set to draft. Contact Eric and it can be sorted out. |

**Remember:** the **Preview** step never changes anything, so you can always upload and preview as many times as you like to check.

---

## One-page checklist

- [ ] Log in to oomphtravel.com/wp-admin
- [ ] Group Cruises → Import Sailings
- [ ] Choose the TLN All Sailings file
- [ ] "Move departed sailings to draft" is checked
- [ ] Preview — numbers look sensible? (New/Updated believable, Skipped small, no warnings)
- [ ] Import for real
- [ ] Group Cruises → Drafts → write blurb + Publish each new sailing you want live

---

## Deepening a sailing page (optional, any time)

A freshly imported sailing shows what the TLN file knows: ship, dates, route, amenities. Two things make the page match the big cruise-site experience, and both are one-time or prepared-for-you:

### The ship section and photos — enter each ship once

Admin → **Ships → Add New**. Title must exactly match the ship name that appears on sailings (e.g. "Silver Nova"). Add: a 2–4 sentence intro in the main editor (first person), the cruise line, 6–12 gallery photos from the supplier's media library (or your own), and 3–6 quick facts (Guests / Crew / Suites / Launched).

**Every sailing of that ship — current and future — picks the section up automatically.** Nothing to do per sailing.

Photos: use images you're licensed to run — the cruise line's trade/media portal or your own camera roll. Set the Photo credit field accordingly.

### The day-by-day itinerary — prepared payloads

The TLN file doesn't carry port-by-port days. An enrichment payload (a small JSON file prepared for each sailing, typically by Claude in a monthly working session) fills the itinerary, inclusions, and a draft "Why this sailing" blurb:

    wp oomph enrich-sailing <post-id> --file=enrich-<post-id>.json --dry-run   # preview
    wp oomph enrich-sailing <post-id> --file=enrich-<post-id>.json            # write

Run via SSH on staging first, same as any change. The command never publishes anything and never overwrites a "Why this sailing" you've already written (a `--force-why` flag exists for deliberate overwrites). Review the draft in admin, adjust the blurb, publish.

### Applying enrichment the easy way — Enrichment Sync (no command line)

If payload files have been added to the repo's `enrichment/` folder, you don't
need WP-CLI at all:

1. WP admin → **Group Cruises → Enrichment Sync**.
2. The screen lists every payload on GitHub and whether it's new, updated, or
   already applied.
3. Click **Apply new payloads**. Same safety rails as the command: drafts stay
   drafts, and a "Why this sailing" you've written is never overwritten.
4. Open each sailing, read it over, publish when happy.

Adding payload files from anywhere (phone included): open the repo on
github.com → `enrichment/` folder → **Add file → Upload files** → commit to a
branch and merge. The Sync screen sees them within about five minutes (or
immediately with **Refresh from GitHub**).
