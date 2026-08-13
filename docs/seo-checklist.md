# SEO Checklist — Per-page-type verification

Run before declaring any page done. Pair with `cro-rules.md` (the R1–R66 ruleset) and `cro-backlog.md` (the justifications). If a page can't pass every applicable check in this file, it isn't ready to merge.

---

## Universal — every page

1. Unique meta title <60 chars, primary keyword in first 30, ends ` | Oomph Travel`
2. Unique meta description 150–160 chars, primary keyword + benefit + soft CTA
3. Exactly one `<h1>` containing the primary keyword
4. `<link rel="canonical">` self-referencing, no parameters
5. JSON-LD schema present and validating in Google Rich Results Test (zero errors, zero warnings on required fields)
6. LCP image: WebP (AVIF source if available), under 250KB, explicit `width`/`height`, `fetchpriority="high"`, **never** `loading="lazy"`
7. All other images: WebP/AVIF, responsive `srcset`, `loading="lazy"` below the fold, explicit dimensions
8. Lighthouse mobile: LCP < 2.5s, INP < 200ms, CLS < 0.1, Performance ≥95, Accessibility 100, Best Practices 100, SEO 100
9. WCAG AA 4.5:1 contrast on every text/background pair (3:1 for large text ≥18pt)
10. Sticky mobile CTA visible at every scroll depth
11. Question-formatted H2s where they introduce content (AI-citation friendly)
12. 2–5 contextual internal links per 1,000 words, descriptive anchors (never "click here", "learn more")
13. Alt text on every image; empty `alt=""` only for decorative
14. No console errors, no failed network requests
15. No words from the No List ([`voice-guide.md`](voice-guide.md))

---

## Home page

URL `/`. Schema: Organization + TravelAgency + Person + BreadcrumbList. Word count: 600–1,000 visible.

1. Title under 60 chars, includes "Oomph Travel" + primary positioning
2. H1 is outcome-focused (e.g., "Travel that's worth the trip.")
3. Hero contains: headline · subhead · single primary CTA · one trust signal · real photography (never stock)
4. Trust strip below hero: CLIA · Silversea Ultra-Luxury Specialist · Nexion · BritAgent Pro · Port Angeles · WA
5. Three ICP cards ("Who I help") with negative qualifier optional
6. Three service tiles linking to `/luxury-cruise-planning`, `/custom-italy-travel`, `/multi-generational-travel-planning`
7. Founder mini-bio with portrait + 60-word bio + link to `/about`
8. Lead magnet block with single-field email form
9. Testimonials block: 2–3 named excerpts (never carousel; static grid)
10. Featured journal: 3 most-recent or most-important Journal Cards
11. Final CTA repeats primary CTA
12. Primary CTA appears in: hero, after mini-bio, lead magnet block, final block — exactly 3–4 placements

---

## About page (`/about`)

URL `/about`. Schema: Person with full hasCredential, memberOf, worksFor, sameAs. Word count: 800–1,500.

1. H1 in first person ("I plan the trips I'd take myself" or similar)
2. Hero with portrait + headline + short subhead
3. "Why I do this" section: one specific origin moment (not "passion for travel")
4. "Who I serve, and why" — three ICPs from the home page expanded
5. Credentials boxed with badges + 25-word description per credential
6. **Currently completing** treatment for unearned credentials (DS-Italy specifically)
7. First-hand experience list with dated specifics ("Sailed 14 Silversea voyages between 20XX and 20XX")
8. Personal touches paragraph that humanizes without kitsch (no "foodie", "wanderlust", "travel obsessed")
9. Pull-quote testimonial **about character**, not service quality
10. Final CTA section
11. Linked from every blog byline (`rel="author"` on author links)

---

## Service hub page (cluster pillar)

URL `/[service-slug]`. Schema: Service + FAQPage + BreadcrumbList. Word count: 1,200–2,500. Hub for 8–12 cluster blog posts.

1. H1 contains primary service keyword
2. Hero with dual CTA: primary "Start a conversation →" + secondary lead-magnet CTA
3. "Who this is for" + "Who this is NOT for" — negative qualifiers pre-qualify
4. "What I actually do" — concrete deliverables, not adjectives
5. "Why an advisor matters here" — lead with the problem, 4 short paragraphs
6. Trust strip with contextual credentials only (Silversea on cruise; BritAgent on UK; DS-Italy on Italy when earned)
7. "How I work" — three steps written for this service context
8. Fee transparency block with the standard line
9. Featured journal — 3 cluster-post cards (bidirectional internal linking mandatory)
10. Service-specific testimonials — 2 with trip + ship/region + year
11. FAQ section: 5–8 question-formatted entries with FAQPage schema
12. Final dual CTA
13. Primary CTA appears 4× across the page

---

## Blog pillar (cornerstone)

URL `/journal/[slug]`. Schema: Article + Person + FAQPage. Word count: 3,000–5,000.

1. TL;DR / direct-answer block in first 200 words (AI-citation gate)
2. Visible `dateModified` timestamp
3. Author byline linked to `/about` with Person schema
4. Original photography only (no stock)
5. First-hand experience marker in opening ("When I sailed the Silver Nova in March 2025…")
6. Table of contents for posts >2,000 words
7. FAQ section with 5–8 entries + FAQPage schema
8. Bidirectional pillar↔cluster linking (this links to every spoke; each spoke links back)
9. Content upgrade after first section (lead magnet)
10. Mid-post soft service CTA
11. End-of-post: author bio + Discovery Call CTA
12. Annual update calendar entry created

---

## Blog cluster (spoke)

URL `/journal/[slug]`. Schema: Article + Person. Word count: 1,500–2,500.

1. TL;DR / direct answer in first 200 words
2. One focused question per post (intent specificity)
3. Long-tail title format `[Keyword]: [Specific Promise / Benefit]`
4. Visible `dateModified`
5. Author byline + Person schema
6. First-hand experience marker
7. Links up to the relevant pillar
8. Links sideways to 2–3 sibling spokes
9. Original photography
10. Mid-post soft CTA

---

## Discovery Call (`/discovery-call`)

URL `/discovery-call` (not `/contact`). Schema: ContactPage + Person. The single most important conversion page.

1. Headline names what the call is and what it costs (free)
2. **Calendly embedded inline — never modal** (inline converts 20–40% better)
3. Calendly inherits page typography (use URL parameter `primary_color=1f4e5f`)
4. Fallback link below embed: "Calendar not loading? Email eric@oomphtravel.com"
5. "What to expect" — 3–4 bullets, type-only (no decoration)
6. "Who I work with / who I don't" pre-qualification block
7. "If you're not ready" alternative: lead-magnet download
8. FAQ section with 5 entries
9. Two testimonials + credentials strip below embed
10. Form submission fires GA4 `generate_lead` event with `form_id` parameter
11. INP < 200ms despite the Calendly embed (verify after build)
12. Calendly script enqueued only on this page (not globally)

---

## Lead magnet landing page

URL `/cruise-cabin-guide`, `/italy-planning-guide`. Schema: WebPage (or none beyond Organization). Word count: 600–1,000.

1. **No main navigation** — logo only in header
2. 3D cover mockup of the guide
3. Outcome headline (not feature headline)
4. **Single email field** + "Send me the guide" CTA
5. "What's inside" — 3–5 outcomes, not features
6. Mini-bio paragraph
7. "Who this is for" qualifier
8. Mini-testimonial
9. Second form near the bottom + privacy reassurance microcopy
10. Load time < 2s on mobile
11. Form submission fires GA4 `generate_lead` event
12. Thank-you page upsells Discovery Call

---

## Group cruise landing

URL `/group-cruises/[destination]-[year]`. Schema: **Event required** + BreadcrumbList. Word count: 1,500–2,500. Highest conversion intensity in the catalog.

1. Hero contains: ship + destination + dates + "Hosted by [Name]" + real scarcity + dual CTA
2. Scarcity is real and weekly-updated (cabins remaining, not "filling fast")
3. Trip in one paragraph (the lede)
4. Day-by-day accordion
5. Ship + cabin tiers with per-person + single-supplement pricing
6. "What's included / not included" — 3 columns
7. Group exclusive perks block
8. "About your host" paragraph + portrait
9. Trip protection / deposit schedule
10. FAQ section: 10–15 entries (priority order: single supplement, cancellation, what's included, host onboard time, mandatory activities, insurance, deposit schedule, pre/post extensions, first-time fit, solo fit, dress code, air, transfers, dining, special occasions)
11. Past testimonials from prior hosted trips
12. Final CTA + scarcity reminder
13. Event schema validates with offers.price, validFrom, availability

---

## Testimonials / Client Stories

URL `/client-stories`. Schema: Review + AggregateRating (triggers SERP star rating). Word count: 800+.

1. Aggregate stat hero ("5.0 average across 12 reviews")
2. One standout featured testimonial in long form
3. Testimonials grouped by trip type (cruise · Italy · multi-gen)
4. Each: photo + name (or first + last initial) + trip type + destination + date + quote
5. **3–4 long-form case studies beat 12 generic quotes**
6. Third-party review links (Google Business Profile)
7. "Earn a place here" CTA
8. Review + AggregateRating schema validates
9. Final CTA

---

## Fees (`/fees` or `/how-i-work`)

URL `/fees` (or `/how-i-work`). Schema: WebPage. Word count: 600–1,200.

1. H1 leads with the problem the fee solves — not the fee
2. Floor pricing visible ("from $300 for cruises, $500 for custom Europe")
3. "What's included" enumerated
4. Standard line: "Planning fees fund my undivided attention to your trip. Commissions don't change your price."
5. Hours comparison preempts "I can just Google it"
6. Linked from every service page and Contact page
7. Final CTA

---

## Legal pages (`/privacy`, `/terms`, `/accessibility`, `/cookies`)

1. Last-updated date visible
2. Linked from every footer
3. No marketing copy, no CTAs
4. Privacy policy specifies analytics tools (GA4, Microsoft Clarity), email tool (Plainsend,
   sending through Amazon SES), and lead-magnet fulfillment
