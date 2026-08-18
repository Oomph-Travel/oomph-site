# Content plan — Q3/Q4 2026

**Written:** 2026-08-09
**Cadence:** one post per week
**Thesis:** The slow-travel angle is the only non-branded thing pulling search traffic. Own it.

---

## The evidence this plan rests on

Search Console, 28 days ending 2026-08-09: 270 impressions, 4 clicks across 1,051 indexed pages.

Of the 36 impressions Google could attribute to a specific query:

| Query | Impressions | Position |
|---|---|---|
| "oomph voyage" | 15 | 7.3 |
| "eric hempel" | 10 | 6.9 |
| "slow cruise" | 5 | 18.2 |
| "what cruise itineraries offer a slower pace and longer port stays?" | 3 | 11.0 |
| "luxury cruise planning" | 1 | 32.0 |
| "what are some luxury cruise lines that offer longer stays in ports and fewer days at sea?" | 1 | 8.0 |
| German-language query about Explora Journeys and time in destination | 1 | 2.0 |

25 of 36 are branded — people who already know Eric. The remaining 11 are a single coherent theme: **slower pace, longer port stays, fewer sea days.**

`/journal/the-slow-cruise/` (published 2026-05-26) is what those queries are landing on. It was written once, never promoted, and is the site's only organic discovery channel.

Meanwhile `/luxury-cruise-planning/` sits at position 32 for its own name. It is correctly built, indexed, and schema-valid — it simply has no external links. That term is not winnable from a standing start.

**Conclusion:** stop competing for "luxury cruise planning." Extend the slow-cruise cluster, which already ranks 8–18 with zero effort behind it.

---

## Fix first — two things before any new writing

### 1. `/journal/10-day-united-kingdom-itinerary/` violates the voice guide

Published 2026-06-16. Its opening reads: *"Planning a trip to the UK can be both exciting and overwhelming, especially when considering the multitude of historical sites, breathtaking landscapes, and vibrant cities to explore."*

`breathtaking` is on the No List in `docs/voice-guide.md`. The passage is also written in nobody's voice — no "I," no named place, no season, no milestone. It reads like generic filler next to the other three posts.

Eric has been to London, Edinburgh, and the Lake District. Rewrite it from what he actually saw. This is the single largest voice inconsistency on the live site.

### 2. That post is in the `Uncategorized` category

This is why `/category/uncategorized/` picked up 5 impressions — a default WordPress archive is competing with real pages. Assign it to `Cruise` or a new `Europe` category, then confirm Rank Math is set to noindex empty and default category archives.

---

## The cluster — eight weeks

Every title below is answerable only by someone who has actually sailed. That is the moat: a competitor can copy the topic, not the fourteen voyages.

Drawn from the field notes on `/about/`: 14 Silversea voyages 2018–2025 (Silver Nova · Western Mediterranean · March 2025; Silver Whisper · Northern Europe · September 2024; Silver Spirit · Caribbean · April 2024), multiple Regent Seven Seas and Seabourn voyages, four Italy trips 2020–2025 (Puglia, Sicily, Tuscany, the Lakes), two UK trips (London, Edinburgh, Lake District).

| Week | Working title | The query behind it | Eric's firsthand hook |
|---|---|---|---|
| 1 | Explora Journeys: does it actually give you enough time ashore? | Two German queries at **position 1 and 2** | Eric has sailed Explora. The site currently says nothing about it. |
| 2 | Which cruise lines actually stay longest in port | "luxury cruise lines that offer longer stays in ports and fewer days at sea" (position 8) | Direct comparison across Explora, Silversea, Regent, Seabourn — all four sailed |
| 3 | What an overnight in port actually buys you | "slower pace and longer port stays" (position 11) | Silver Whisper, Northern Europe, September 2024 |
| 4 | Sea days are not filler: how to read an itinerary's pacing | "slow cruise" (position 19.2 — page two, winnable) | Reading deck plans and port schedules as a planning habit |
| 5 | The Western Mediterranean at half speed | Extends the cluster into Europe | Silver Nova, March 2025 — a named sailing, a named season |
| 6 | Puglia is the argument for staying put | Bridges cruise cluster to Custom Italy | Four Italy trips; the Lecce restaurant already referenced on `/about/` |
| 7 | Fewer ports, better trip: the case against the 12-port itinerary | Reinforces the cluster's core claim | Contrarian, and defensible from 14+ voyages |
| 8 | The one question to ask before you book a slow itinerary | Converts the cluster toward the Discovery Call | Ties the cluster back to `/discovery-call/` |

### Why Explora moved to week 1

`/journal/the-slow-cruise/` ranks **position 2** and **position 1** for two German-language queries, both asking whether Explora Journeys allows enough time to explore each destination properly. It earned those positions while barely mentioning the line.

Explora Journeys appears nowhere in customer-facing content — not on `/about/`, not in any post. The only repo matches are ship-importer config files.

Eric has sailed Explora (confirmed 2026-08-09). Writing the post he already ranks for, on a line he has actually been on, is the highest-leverage single piece of content available.

Both queries are long conversational prompts with personal context — assistant-mediated searches, not keyword searches. Answer the question plainly in the first two sentences; that is the text that gets quoted.

### Also fix: `/about/` understates the experience

The field notes list Silversea, Regent, and Seabourn but omit **Explora Journeys**. A reader landing from an Explora query finds no evidence Eric has sailed it. Add it to the field-notes list on `/about/`.

---

## Rules for every post in this cluster

- Link back to `/journal/the-slow-cruise/`. It is the cluster's hub and already has the rankings — internal links pass authority to it and to the newer posts.
- One primary CTA, per `docs/cro-rules.md`. Discovery Call.
- Name the ship, the season, and the year. "Silver Nova, Western Mediterranean, March 2025" outperforms any adjective.
- Answer the question in the first two sentences. The conversational queries in the data are people asking a question, and AI summaries quote whoever answers it fastest.
- No No-List words. Check `docs/voice-guide.md` before publishing.
- Category: `Cruise`. Never `Uncategorized`.

---

## What this plan deliberately does not do

- **No LCP work.** `/links/` misses the 2.5s bar at 3.19s and Fluent Forms ships 43KB sitewide from the footer embed. Real, and worth fixing — but at 4 clicks a month there is no measurable payoff. Parked with a full plan already written. Revisit above ~500 clicks/month.
- **No chasing "luxury cruise planning."** Position 32, no backlinks, saturated field.
- **No new sailing pages.** The 338 already indexed pull impressions for ship-and-date queries — people who have already chosen. They don't need an advisor.

---

## How to know it worked

Check Search Console monthly, not weekly. The number that matters is **non-branded impressions** — total impressions minus anything containing "oomph" or "hempel."

Today that number is roughly 11 per month.

If the cluster works, it should be the fastest-growing line on the chart by October. Clicks will lag impressions by months; don't read weekly noise as signal.
