# CRO Backlog — Research findings that justify the rules

Companion to [`cro-rules.md`](cro-rules.md). The rules tell you what to do; this file tells you why, with the statistics from the 2026 SEO + CRO Foundations report. When a rule feels arbitrary, read the corresponding section here.

Full source: [`source/seo-cro-condensed.md`](source/seo-cro-condensed.md) (every section retained, benchmarks intact) and [`source/seo-cro-foundations-2026.pdf`](source/seo-cro-foundations-2026.pdf).

---

## 1. The 5-second test

The visitor must be able to answer in five seconds: **(1) What is this? (2) Who is it for? (3) What do I do next?** If the hero doesn't answer all three, redesign before tweaking anything else.

Benefit-focused headlines beat feature-focused by ~31%. Real-photography heroes beat stock by a margin that's hard to measure precisely but consistent across luxury verticals.

---

## 2. Hero anatomy

Required: benefit-focused headline · subhead naming the target audience · single primary CTA · reinforcing visual · one trust marker.

Desktop hero 60–100% viewport. Mobile 50–70%. Hero video adds ~1.2s LCP and every 100KB of media = +1.8% bounce. **Image beats video for luxury services** unless the video earns its weight (short, muted, lazy-loaded background loop, static fallback, <5MB).

**Oomph hero formula:** real photography · headline naming the milestone · subhead naming the niche · primary CTA "Start a conversation" · trust strip with CLIA · Nexion · Silversea Ultra-Luxury Specialist · BritAgent Pro.

---

## 3. CTA rules

Single-word CTA changes swing CVR 10–30%. The wrong words cost more than the wrong color.

**Never as primary:** Submit · Learn More · Click Here · Get Started · Contact Us · Book Now.

**First-person beats second-person** by ~90% ("Start MY trip" > "Start YOUR trip"). One primary CTA per page (with 1–2 secondaries) — multiple equal-weight CTAs can drop CVR up to 266%. Single-CTA emails boost clicks 371%. Microcopy under the CTA adds 17–25% lift.

Mobile: 44×44pt minimum tap targets, 8–12px spacing, primary CTA in the bottom thumb zone, sticky CTA bar at every scroll depth.

---

## 4. Form rules

| Fields | Conversion |
|---|---|
| 3 | ~25% |
| 6+ | ~15% |

Phone field alone drops CVR ~5%. **Multi-step converts 86% higher than single-step.** Optimal: 3–4 steps, 12–15 fields total.

Single column on all devices. Inline validation with positive confirmation ("✓ looks good"). Mark optional, not required. Mobile keyboard hints via `inputmode`. Privacy microcopy under the form.

---

## 5. Discovery Call form — the 3-step

1. **Trip type + travel window** (radios — Luxury cruise · Custom Italy · Multi-gen · Not sure yet)
2. **Travelers + budget bracket + advisor experience**
3. **Name + email + phone (optional) + notes**

Discovery-call benchmark conversion for warm traffic: **5–15%**. Full Oomph field spec in [`source/build-plan-v2.docx`](source/build-plan-v2.docx) §12.1.

---

## 6. Trust signal hierarchy

Ranked by impact for Oomph specifically:

1. **Personal authority** — advisor photo, "Hi, I'm [name]"
2. **Specific named testimonials** with photos, trip details, dates
3. **Industry credentials** (CLIA, Nexion, Silversea Ultra-Luxury, BritAgent Pro)
4. **Video testimonials** — 34–80% lift; 60–150s ideal length
5. **Real client outcomes / anonymized case studies**
6. **"As featured in" press strip**
7. **SSL / security badges** — only meaningful on form pages

For unearned credentials (e.g., DS-Italy): write "Currently completing Italy Destination Specialist certification" or omit. Never display as earned.

---

## 7. Popup rules

| Pattern | Allowed? |
|---|---|
| Full-screen popup on mobile initial landing from search | Never (Google interstitial penalty) |
| Mobile banner ≤20% viewport | OK |
| Time / scroll popup after ≥60% scroll or ≥30s | OK |
| Exit-intent on desktop for lead magnet | OK |
| Exit-intent on mobile | Never |
| Cookie consent / legal banner | Always allowed |

---

## 8. Fee transparency

78% of advisors charge planning fees in 2026. Framing on the website matters more than the number.

**Seven moves that work:**

1. Lead with the problem the fee solves — not the fee itself.
2. Be explicit about what's included.
3. Reframe as a filter, not a barrier.
4. Pre-empt "I can just Google it" with an hours comparison.
5. Show commission transparency.
6. Publish floor pricing ranges, not exact quotes ("from $300 for cruises, $500 for custom Europe").
7. Standard line: *"Planning fees fund my undivided attention to your trip. Commissions don't change your price."*

Hidden pricing reads as evasive to affluent buyers. Show ranges; never show exact quotes for custom work.

---

## 9. Anti-patterns for luxury

Standard CRO tactics that **backfire** for high-ticket service businesses:

- Countdown timers
- "Only 2 left" popups
- Discount banners
- Aggressive exit-intent on mobile
- "Buy Now" CTAs (service, not commerce)
- Stock photography of generic happy people
- Live chat bots
- Vague scarcity ("filling fast")

Affluent buyers define luxury as time, access, and ease — not ostentation. They value privacy, research extensively, and lean on trusted human expertise. Trust-building takes **3–12 months** from first visit to inquiry; email nurture is the rule, not the exception.

**Legitimate scarcity (use):** "4–6 custom Italy itineraries per quarter" · "Silversea Veranda Suites sold out 12+ months out" · "2026 calendar 70% booked through May."

---

## 10. Discovery-call funnel benchmarks

Use these to set targets and detect breakage, not as guarantees.

| Stage | Rate |
|---|---|
| Inquiry → discovery call | 40–60% (qualified) |
| Discovery call → planning fee paid | 30–50%; up to 70% from referrals |
| Planning fee → confirmed booking | 70–90% |
| Overall inquiry → booking | 12–14% luxury; ~25% from referrals |

Repeat booking rate target: 35%+. Referral rate: ~25%. Time-to-conversion averages 90 days, sometimes 6–12 months.

---

## 11. Speed as a CRO factor

- CVR drops ~4.42% per second of load between 0 and 5 seconds
- 53% of mobile users abandon at >3s
- 1-second sites convert 3× better than 5-second sites

**Oomph target:** LCP < 2.0s on mobile. The published CWV threshold is 2.5s; we aim under it.

---

## 12. Multi-stakeholder decisions (multi-gen niche)

Four decision roles, all of whom can veto:

1. **Primary buyer** — often Gen X organizing the trip
2. **Financial decision-maker** — may be the same person, may not
3. **Influencers** — spouse, siblings
4. **Veto-holders** — mobility-concerned parent, teens

Surface mobility, dietary, pace, and room-configuration FAQs explicitly. The page that addresses the slowest walker and the pickiest eater closes more than the page that talks about luxury.
