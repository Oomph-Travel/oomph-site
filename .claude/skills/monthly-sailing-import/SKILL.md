---
name: monthly-sailing-import
description: Runs Oomph Travel's monthly group-cruise sailing update end to end — previewing and importing the Travel Leaders Network All Sailings file, isolating the month's new Distinctive Voyages drafts, writing each one's "Why this sailing" blurb in Eric's voice, shipping them as enrichment payloads through a PR, and handing back for deploy approval and Enrichment Sync. Use this whenever the user mentions the monthly sailings file, a TLN or Travel Leaders import, new group cruise drafts, "why this sailing" blurbs, enrichment payloads, or the Enrichment Sync screen — including partial asks like "I've got the new sailings list", "walk me through the import", or "write blurbs for the new drafts".
---

# Monthly sailing import + enrichment

Once a month Travel Leaders Network (TLN) sends an **All Sailings** `.xlsx`. Importing it
creates new Group Cruise posts: *Amenity Departures* publish themselves, while *Distinctive
Voyages* land as drafts holding a placeholder where a short "Why this sailing" blurb belongs.
This skill covers everything from that file arriving to the blurbs being live-ready.

The end state you're driving toward: **the month's new Distinctive Voyages have honest,
first-person blurbs applied to their drafts, and Eric decides which ones to publish.**

Eric handles the import clicks and the final deploy approval. You handle the data gathering,
the writing, and the git work. Say which step he's on and what you need from him.

## The eight phases

| Phase | Who | What |
|---|---|---|
| 1. Import | Eric clicks | Preview → sanity-check → Import for real |
| 2. Isolate | You | Filter the drafts to *this month's* new ones |
| 3. Gather | You | Pull slug, route, dates, and shore event for each draft |
| 4. Confirm | Eric answers | Which ships has he actually sailed? |
| 5. Write | You | One blurb per sailing |
| 6. Validate | You | JSON, slugs, voice, No List |
| 7. Ship | You | Commit → PR → squash merge |
| 8. Apply | Eric clicks | Approve deploy → Refresh → Apply new payloads |

---

## Phase 1 — The import

Walk Eric through it; don't try to do it for him. The full user-facing runbook lives in
[docs/updating-sailings.md](../../../docs/updating-sailings.md) — read it if he asks
anything the summary here doesn't cover.

1. **wp-admin → Group Cruises → Import Sailings**
2. Choose the TLN `.xlsx` exactly as it arrived. No reformatting, no CSV conversion.
3. **Sailing types: Both.** Leave the *"Unpublish sailings that have departed or are less
   than 3 months away"* box checked.
4. **Preview** first — it changes nothing, so it's free to run as often as needed.

Then read the preview numbers back to him with a verdict, not just a repeat:

- **Skipped should be 0 or a handful.** A large Skipped count means dates didn't parse —
  stop, don't import, send the file to Eric to check.
- **A yellow "columns were not found" warning** means it's probably the wrong export. Stop.
- **New = 0** is also a stop signal.
- New + Updated in the tens-to-hundreds is normal. (August 2026: 85 new / 1,195 updated /
  0 skipped / 9,452 rows read — a healthy shape to compare against.)

If it looks right, he clicks **Import for real**.

The confirmation screen reports a **total** draft count — several hundred, mostly a backlog
of prior months' unpublished sailings. That number is expected and is not this month's work.
Say so before he panics about it.

---

## Phase 2 — Isolate this month's new drafts

New posts are inserted with today's date; updated and retired ones keep their old dates. So
a date filter on the drafts list isolates exactly the new arrivals.

```
https://oomphtravel.com/wp-admin/edit.php?post_type=oomph_cruise&post_status=draft&m=YYYYMM
```

`m=202608` is August 2026. The count at the top should roughly match the preview's
"Distinctive Voyages (as drafts)" figure — a few extra is fine (a retired sailing that
happened to originate this month).

---

## Phase 3 — Gather the data

You need, for each draft: **post ID, slug, ship, route, dates, nights, and the shore event**
(name, date, duration, port). The shore event is the reason a sailing is featured, so a blurb
written without it is generic and not worth much.

Use the Chrome extension against Eric's logged-in session. **Read the slug from each post's
actual `post_name` — never derive it from ship + date.** The importer's slug pattern is
predictable, but WordPress appends `-2` on collisions, and a wrong filename means Enrichment
Sync silently skips that payload rather than erroring.

The extraction JavaScript, with the parsing traps already worked out, is in
[references/data-extraction.md](references/data-extraction.md). Read it before writing your
own — it will save you several rounds of trial and error.

---

## Phase 4 — Confirm which ships Eric has sailed

**This is the step that protects him, so don't skip it or infer around it.**

The voice guide asks for first-hand experience markers. You cannot verify which ships Eric
has been aboard, and a fabricated "when I sailed her last spring" goes onto a live page under
his name where a client can ask him about a voyage he never took. Credibility is the whole
product here.

Read [references/ships-sailed.md](references/ships-sailed.md) for the running list. Then ask
Eric only about the ships in this month's batch that aren't on it yet — a short, specific
question, not a re-interrogation of the whole fleet.

Whatever he confirms, **add to that file in the same PR** so next month's run doesn't ask again.

If he says "just add experience lines" or something similarly ambiguous, clarify before
writing rather than guessing. Ambiguity here has an asymmetric cost: getting it wrong means
publishing a false claim.

---

## Phase 5 — Write the blurbs

Two or three sentences each. First person, plain, specific. Full rules in
[docs/voice-guide.md](../../../docs/voice-guide.md) — the No List is a hard constraint, not a
style preference.

**For ships Eric has sailed**, a genuine first-hand line is the strongest thing available. Keep
it modest unless he's given you detail, and tell him you've written an inferred impression so
he can correct it before publishing.

**For every other ship**, use *advisory judgment* instead. This is the key move, and it works
better than people expect:

> "The Halifax morning is why this one's on my list."
> "Kotor on November 8 is the day I'd protect."
> "This is the itinerary I'd point you to."

These are true statements about how Eric chooses sailings, they're unmistakably his voice, and
they do the same persuasive work as a travel anecdote without claiming a trip he didn't take.
Reach for them by default.

Anchor every blurb in something real from the data — the season, the routing logic, the ship's
actual configuration, and the shore event with its date. Specifics beat adjectives; a blurb
that could describe any Caribbean sailing isn't finished.

**When a sailing has no shore event** — the field reads "No Tour", or the event block is absent
entirely — write it without one and tell Eric which ones those were. Promising an event that
doesn't exist is worse than a plainer blurb.

Worked examples, including the two-ships-one-route case, are in
[references/blurb-examples.md](references/blurb-examples.md).

---

## Phase 6 — Write and validate the payloads

One file per sailing at `enrichment/enrich-<slug>.json`:

```json
{
  "why": "Two or three sentences here."
}
```

`why` is the only field to fill from this workflow. The engine also accepts `itinerary` and
`inclusions`, but those need port-by-port data the TLN file doesn't carry — leave them out
rather than inventing them. Shape is documented in
[class-enrich-engine.php](../../../wp-content/plugins/oomph-travel-core/includes/class-enrich-engine.php).

Then validate before committing:

```bash
node .claude/skills/monthly-sailing-import/scripts/validate-payloads.mjs 2027
```

The argument is a year fragment matching this batch's filenames. The script checks each file
parses as JSON, carries a non-empty `why`, reads as first person, and contains no No List
words. Fix anything it flags — third person slips in easily when a sentence gets rearranged.

**The engine's safety rails**, worth stating plainly when Eric asks what could go wrong:
applying a payload never publishes a draft, and never overwrites a `why` a human has already
written. Re-running is safe.

---

## Phase 7 — Commit, PR, squash merge

Branch, commit, push, open a PR against `main`, then squash merge it.

Write the PR body for a reader who wasn't here: what's in the batch, which blurbs make
first-hand claims and why, which sailings had no shore event, and anything ambiguous you
resolved by assumption.

**Verify the merge actually contains what you think it does.** Check the merged file list
rather than trusting the merge to have worked — see the "Ordering" warning below for why.

If a push or merge is blocked by a permission classifier, say so plainly and hand Eric the
exact command. Don't try to route around it.

---

## Phase 8 — Hand back

Eric approves the deploy and applies the payloads. Give him this, in order:

1. **Approve the deploy** if the GitHub Actions run is sitting in `waiting` — that's the
   production environment's protection rule, not a failure. Payloads alone don't need the
   deploy, but any plugin change does.
2. **Group Cruises → Enrichment Sync**
3. **Refresh from GitHub** — the listing caches for five minutes, so a stale one is likely
   right after a merge.
4. **Apply new payloads** → expect *N applied, 0 refused*.
5. Read a couple of drafts, add featured images, publish the keepers.

He publishes only what he wants live. There's no expectation of clearing every draft.

---

## Things that will bite you

These are real failures from previous runs, not hypotheticals.

**Ordering.** Everything must be on `main` *before* Apply runs — Enrichment Sync reads from
`main`, not from a branch. If a fix and the payloads are in flight together, confirm both
landed. A PR merged while a later commit was still being pushed will silently ship without it,
and the merge reports success either way.

**Repeated titles are usually not duplicates.** Two sailings can share a title and differ in
date or direction — a Sydney→Auckland and an Auckland→Sydney run, or two round-trips a
fortnight apart. Compare sail dates before calling anything a double-import.

**"Refused: unexpected download host"** on every payload means the sync guard's repo allowlist
no longer matches what the GitHub API returns. This happens after a repo transfer or rename:
the API follows the redirect and lists files fine, but returns `download_url` under the
*canonical* owner. Fix `REPO_ALIASES` in
[class-enrich-sync.php](../../../wp-content/plugins/oomph-travel-core/includes/class-enrich-sync.php).
Nothing about the payloads is wrong when this happens.

**Sailings inside the 3-month window** stay as drafts by design — cruise final payment falls
due around 90–120 days out, so a nearer departure usually can't be booked. Don't try to
publish them.

**The draft backlog is not the batch.** Several hundred drafts is normal; the date filter is
what tells you this month's work.
