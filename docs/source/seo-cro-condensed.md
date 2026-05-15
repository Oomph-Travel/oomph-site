# Oomph Travel — SEO + CRO Foundations (Condensed)

*A compressed reference of the full 2026 research report. Every section retained; filler and benchmark statistics trimmed.*

---

## 1. SEO and CRO are now one discipline

Google's December 2025 and March 2026 Core Updates collapsed the gap between ranking signals and conversion quality. Dwell time, Core Web Vitals, E-E-A-T, helpful-content signals, and schema-driven entity clarity all reward exactly what CRO has always asked for: clear value above the fold, named credentials, original photography, fast load, focused conversion paths. UGC aggregators (Reddit, TripAdvisor, Expedia, Wikipedia) lost ground in March 2026; brand-owned, first-party content with clear authorship rose.

**What high-performing 2026 pages do:** lead with the answer in the first 200 words · display named credentials prominently · embed original photography · use question-formatted H2s · hit LCP < 2.5s mobile · maintain one primary CTA per page.

AI-search visitors convert roughly 23× better than traditional organic — they arrive mid-funnel, so the few clicks that come through must convert hard.

---

## 2. SEO essentials (every page)

**On-page**
- **Title tag:** 50–60 chars, primary keyword in first 30, format `Primary Keyword | Modifier | Oomph Travel`. Aim ≤46 chars to minimize Google rewrites.
- **Meta description:** 150–160 chars, unique, keyword + benefit + soft CTA.
- **Headers:** one H1; question-formatted H2/H3 favored for AI citation.
- **Internal links:** descriptive varied anchor text, 2–5 contextual per 1,000 words, total < 150, bidirectional pillar↔cluster, key pages within 3 clicks of home.

**Schema markup (JSON-LD in `<head>`)** — pages with structured data are 3.2× more likely cited in AI Overviews.
- Sitewide: `Organization`
- Home + About: `TravelAgency` (LocalBusiness subtype)
- Advisor bio: `Person` with `hasCredential` and `sameAs`
- Service pages: `Service`
- Guides/destination pages: `FAQPage`
- Every interior page: `BreadcrumbList`
- Testimonials: `Review` + `AggregateRating`
- Blog: `Article`/`BlogPosting` with author, datePublished, dateModified
- Group cruise pages: `Event` (most undervalued — triggers rich SERP)
- Destination guides: `TouristAttraction`/`TouristDestination`

**Image SEO**
- Format: AVIF → WebP → JPEG/PNG fallback via `<picture>`.
- Always set explicit `width`/`height` (prevents CLS).
- LCP hero: **never lazy-load**; use `fetchpriority="high"` and preload.
- Other images: `loading="lazy"` + `decoding="async"`.
- Alt text: 1–2 sentences; first ~16 words carry SEO weight.
- File naming: `lowercase-hyphenated-descriptive.webp`.
- Use responsive `srcset` + `sizes`. Add `ImageObject` schema for Google Images badges.

**Core Web Vitals thresholds (verified May 2026)**

| Metric | Good | Needs Improvement | Poor |
|---|---|---|---|
| LCP | ≤ 2.5s | 2.5–4.0s | > 4.0s |
| INP (replaced FID 3/2024) | ≤ 200ms | 200–500ms | > 500ms |
| CLS | ≤ 0.1 | 0.1–0.25 | > 0.25 |

Measured at 75th percentile of CrUX real-user data. Mobile-first indexing means mobile thresholds are what ranks.

**Other technical**
- HTTPS + HSTS, CSP, X-Frame-Options DENY, Referrer-Policy strict-origin-when-cross-origin, X-Content-Type-Options nosniff
- XML sitemaps ≤50,000 URLs/50MB, canonical 200-status URLs only, accurate `lastmod`, segmented by content type
- `robots.txt`: block `/wp-admin/` (except admin-ajax) and `/?s=`; do **not** block `/wp-content/uploads/`, GPTBot, ClaudeBot, PerplexityBot, Google-Extended
- Self-referencing canonical on every page
- URLs lowercase, hyphenated, no parameters, logical hierarchy, no dates in blog slugs
- Each paginated page self-canonical (rel=next/prev deprecated 2019)
- hreflang not needed for English-only US-centric content

**Content SEO**
- Start from buyer questions, not raw volume (GSC Queries, PAA, AnswerThePublic, ChatGPT/Perplexity prompts)
- Cluster by intent; prioritize commercial-investigation and transactional queries
- **Hub-and-spoke architecture is non-negotiable** since Dec 2025 — niche expertise now weighted comparably to backlinks
- Pillar pages 3,000–5,000 words; cluster pages 1,500–2,500
- Topic clusters get 3.2× more AI citations; pillar-cluster sites see 30–43% more organic traffic
- Quality test: "Would the reader hit the back button?" Pages >2,500 words cited 1.6× more in AI Overviews than pages under 800

**E-E-A-T for Oomph (treat entire site as YMYL-adjacent)**

| Pillar | Signal for Oomph |
|---|---|
| Experience | Original photos/videos from sailings + Italy trips, dated trip reports, case studies |
| Expertise | Visible credentials (CLIA, Nexion, Silversea Ultra-Luxury, BritAgent Pro), Person schema, deep About page |
| Authoritativeness | Press citations, awards, brand mentions, podcast/article appearances |
| Trustworthiness | HTTPS, complete contact info, transparent fees, verified address, privacy policy, real-name reviews |

**AI search optimization (GEO)**
1. TL;DR / direct answer in first 200 words
2. Question-formatted H2/H3 headers
3. Self-contained 200–500-word citable chunks
4. Statistics with specific numbers + attribution
5. Lists, tables, comparison grids (78% of AI answers use list format)
6. FAQ sections + FAQPage schema
7. Named entities explicitly
8. Visible last-updated timestamps + `dateModified`
9. Author byline with credentials on every page
10. Original data, case studies, first-hand observations

Platform notes: Google AI Overviews correlate strongly with Google top-10 organic. ChatGPT Search aligns with Bing top-10 — register Bing Webmaster Tools. Perplexity rewards recency + structure. **`llms.txt`: nice-to-have, not required** — Google doesn't use it; add a minimal one for future-proofing.

**Local SEO** — Oomph is a Service-Area Business: real verifiable address required (can be hidden), no P.O. boxes/virtual offices. Primary GBP category Travel Agency; secondary Cruise Agency. NAP consistent across CLIA, BBB, LinkedIn, Yelp, Bing Places, Apple Maps. Aim for 5–10 quality Google reviews in Year 1.

---

## 3. CRO essentials (every page)

**Above the fold — the 5-second test**
The visitor must answer in 5 seconds: (1) What is this? (2) Who is it for? (3) What do I do next?

Hero must include: benefit-focused headline (beats feature-focused by ~31%) · subhead naming the target audience · single primary CTA · reinforcing visual · one trust marker. Desktop hero 60–100% viewport, mobile 50–70%.

Image generally beats video for luxury services. Hero videos add ~1.2s LCP and every 100KB = +1.8% bounce. If video used: short, muted, lazy-loaded background loop with static fallback, under 5MB.

**Oomph hero formula:** real photography (never stock) · headline naming the milestone · subhead naming the niche · primary CTA "Book a Discovery Call" · trust strip with CLIA · Nexion · Silversea Ultra-Luxury Specialist · BritAgent Pro.

**CTA copy** — single-word changes can swing CVR 10–30%. Never use Submit / Learn More / Click Here / Get Started.

| Generic | Oomph |
|---|---|
| Submit | Book My Discovery Call |
| Learn More | See How I Work |
| Get Started | Start Planning My Trip |
| Download | Send Me the Cabin Guide |
| Sign Up | Get Italy Trip Tips |

First-person beats second-person ("Start MY..." beat "Start YOUR..." by ~90%). Single brand-accent color for primary, ghost/outlined for secondary. **One primary CTA per page** (with 1–2 secondaries) — multiple equal-weight CTAs can drop CVR up to 266%; single-CTA emails boost clicks 371%. Microcopy under CTA adds 17–25% lift.

Mobile: 44×44pt tap targets, 8–12px spacing, primary in bottom thumb zone, sticky CTA bar.

**Trust signals — ranked for Oomph**
1. Personal authority — advisor photo, "Hi, I'm [name]"
2. Specific named testimonials with photos and trip details
3. Industry credentials (CLIA, Nexion, Silversea Ultra-Luxury, BritAgent Pro)
4. Video testimonials (34–80% lift; 60–150s ideal)
5. Real client outcomes / anonymized case studies
6. "As featured in" press strip
7. SSL/security badges (meaningful only on form pages)

For unearned credentials (e.g., DS-Italy): "Currently completing Italy Destination Specialist certification" — or omit.

**Form optimization**
- 3 fields → ~25% CVR; 6+ → ~15%
- Phone field alone drops CVR ~5%
- Multi-step converts **86% higher** than single-step
- Optimal: 3–4 steps, 12–15 fields total
- Single column; inline validation; mark optional not required; mobile keyboard hints (`inputmode`); privacy microcopy under form

**Oomph Discovery Call form (3 steps):**
1. Trip type + travel window (radio)
2. Travelers + budget bracket + advisor experience
3. Name + email + phone (optional) + notes

Discovery-call benchmark conversion: 5–15% for warm traffic.

**Layout** — F-pattern is a failure mode (emerges when content lacks hierarchy). Design for "layer-cake" with strong descriptive H2/H3. Z-pattern for image-light hero pages. Single-column body on mobile, max 2 columns desktop. Whitespace = luxury cue (luxury brands use 30–50% more whitespace).

**Speed as CRO factor** — CVR drops ~4.42% per second of load 0–5s. 53% of mobile users abandon at >3s. 1-second sites convert 3× better than 5-second sites. **Oomph target: LCP < 2.0s mobile.**

**Mobile** — 65–75% of traffic, but desktop converts higher (3.5–4% vs. 1.8–2.5%). Click-to-call (`<a href="tel:...">`) in mobile header — converts 200–400% better than form fills for high-trust service businesses.

**Friction audit checklist** — unclear value prop, vague CTAs, hidden fees, long forms with phone required, missing trust signals, slow load >3s mobile, no mobile click-to-call, About page about the business not the client, broken Calendly mobile widget, unclear next step after lead magnet.

### Luxury / high-ticket psychology (most differentiated)

Affluent buyers lean on trusted human expertise, are digitally savvy *and* high-touch, define luxury as time/access/ease (not ostentation), value privacy, are researchers not impulse buyers, are risk-averse for irreplaceable moments, are heavily influenced by peer referrals.

**Standard CRO tactics that backfire for luxury:** countdown timers · "only 2 left" popups · discount banners · aggressive exit-intent · "Buy Now" CTAs · stock photography of generic happy people · live chat bots.

**Trust-building timeline:** 3–12 months from first visit to inquiry. Email nurture is essential.

**Discovery-call funnel benchmarks**
- Inquiry → discovery call: 40–60% (qualified)
- Discovery call → planning fee paid: 30–50% (up to 70% from referrals)
- Planning fee → confirmed booking: 70–90%
- Overall inquiry-to-booking: 12–14% for luxury; ~25% from referrals

**Pricing transparency** — show ranges and floor pricing, not exact quotes. Hidden pricing reads as evasive. Publish planning fee structure ("from $300 for cruises, $500 for custom Europe").

**Legitimate scarcity (OK):** "4–6 custom Italy itineraries per quarter" · "Silversea Veranda Suites sold out 12+ months out" · "2026 calendar 70% booked through May." **Manipulative scarcity (avoid):** countdown timers, fake viewing widgets, vague "filling fast" claims.

**Multi-stakeholder decisions (multi-gen niche):** address primary buyer (often Gen X organizing for parents) + financial decision-maker + influencers + veto-holders (parent with mobility, teens). Surface mobility, dietary, pace, room-configuration FAQs.

**Planning fee psychology** — 78% of advisors charge fees in 2026. Frame on website: lead with the problem the fee solves, be explicit about what's included, reframe as a filter not a barrier, pre-empt "I can just Google it" with hours comparison, show commission transparency. Standard line: *"Planning fees fund my undivided attention to your trip. Commissions don't change your price."*

---

## 4. SEO/CRO conflicts and resolutions

| Tension | Resolution |
|---|---|
| Keywords vs. readability | Largely dissolved in 2026. Keyword in H1, title, first 100 words, URL, one H2. Rest written for humans. Semantic/entity coverage > repetition. |
| Long content vs. concise CTAs | Hub-and-spoke architecture · progressive disclosure (accordions, sticky ToC) · sticky CTAs · split on different intent only |
| Multiple CTAs vs. clean design | 1 primary + 1–2 secondaries, three placements (above fold, mid, end). Primary almost always Discovery Call; secondary "Download the [Topic] Guide" |
| Popups vs. CWV / interstitial penalty | Exit-intent on **desktop only** for lead magnets · mobile banners ≤20% viewport · time/scroll popups only after 60% scroll or 30s · never on initial mobile landing from search · cookie consent and legal banners always allowed |
| Internal links vs. conversion distraction | Mega-menus for SEO; body links 2–4 most relevant mid-paragraph not in CTA zone · hero free of competing links · footer as SEO link sink · end-of-content 3 related-link cards |
| Schema vs. design | Schema requiring visible content (Review, FAQ) gets tasteful UI (accordions, carousels). Invisible schemas used aggressively. Never schema content not visible. |
| Featured snippet vs. brand voice | Lead each H2 with a direct 40–60-word answer block · follow with brand voice · question-first H2s · E-E-A-T signals earn citations |
| Speed vs. rich media | WebP/AVIF, responsive srcset, explicit dimensions, lazy below fold (never LCP), `fetchpriority="high"` on hero, preload critical · video never autoplay on mobile, static poster, lazy-load, CDN · self-host fonts with `font-display: swap` |

---

## 5. Page-type playbooks

**Home** — Title `Brand | Positioning Specialty` ≤60 chars · H1 outcome-focused · schema TravelAgency + Person + Organization + BreadcrumbList + optional FAQPage · 600–1,000 visible words. **Section order:** Hero + trust strip → Who I help (3 ICPs) → What I plan (3 services) → Founder mini-bio → How it works (3 steps) → Lead magnet → Testimonials → Fees teaser → Featured blogs → Final CTA. Sticky nav with persistent Discovery Call.

**About** — URL `/about` · Person schema with hasCredential, memberOf, worksFor, sameAs · 800–1,500 words · every blog byline links here. **Section order:** Hero portrait + headline → Why I do this (specific hook) → Who I serve + why → Credentials boxed → First-hand experience list with photos → Process → Personal touches → Character testimonial → Final CTA. Facts over adjectives.

**Service pages — the #1 SEO opportunity.** Required in v1: `/luxury-cruise-planning`, `/custom-italy-travel`, `/multi-generational-travel-planning`. Phase 2: `/milestone-trips`, `/silversea-cruise-specialist`, `/europe-river-cruise-planning`. Title `Primary Keyword | Brand` · Service + FAQPage schema · 1,200–2,500 words · cluster hub for 8–12 blog posts. **Section order:** Hero outcome headline + dual CTA → Who this is for (with negative qualifiers) → What I actually do → Why an advisor matters here → Cruise lines/regions covered → How I work → Fee transparency → Featured related content → Service-specific testimonials → FAQ (5–8) → Final dual CTA. Contextual badges only.

**Blog / cornerstone**
- Pillar (3–5 in Year 1): 3,500–6,000 words, head-term, ToC, FAQ, Article + FAQPage schema, annual update
- Spoke (15–20 in Year 1): 1,200–2,500 words, long-tail, one focused question, links up to pillar + sideways to 2–3 siblings
- Title format `[Keyword]: [Specific Promise/Benefit]`
- Author byline linked to About
- First-hand experience markers required
- Original photography only
- In-post CTAs: content upgrade after first section · mid-post soft service CTA · end-of-post author bio + Discovery Call

**Lead magnet landing** — URL `/cruise-cabin-guide`, `/italy-planning-guide` · informational long-tail · 600–1,000 visible words. CRO is the priority: **no main navigation** (logo only) · 3D cover mockup + outcome headline + single-field email form + CTA "Send me the guide" · sections: hero+form → What's inside (3–5 outcomes not features) → Mini-bio → Who this is for → Mini-testimonial → Second form with privacy reassurance · load <2s · thank-you page upsells Discovery Call.

**Group cruise — highest conversion intensity.** URL `/group-cruises/[destination]-[year]` · **Event schema required** · 1,500–2,500 words. **Hero:** ship + destination + dates + "Hosted by [Name]" + real weekly-updated scarcity + dual CTA. **Sections:** Hero → Trip in one paragraph → Day-by-day accordion → Ship + cabin tiers with per-person + single-supplement pricing → What's included/not (3 columns) → Group exclusive perks → About your host → Trip protection/deposit → FAQ (10–15) → Past testimonials → Final CTA + scarcity. **FAQ priority order:** single supplement, cancellation, included/not, host onboard time, mandatory activities, insurance, deposit schedule, pre/post extensions, first-time fit, solo fit, dress code, air, transfers, dining, special occasions.

**Discovery call** — URL `/discovery-call` (not `/contact`) · ContactPage + Person schema · **Calendly embedded inline (never modal — inline converts 20–40% better)** · 20-minute call positioning · "Free 20-minute call. No pressure, no obligation." **Sections:** Headline → Inline Calendly → What to expect (3–4 bullets) → Who I work with / don't → Alternative contact for not-ready visitors → FAQ (5) → 2 testimonials + credentials bar. Calendly intake: name, email, what they want to plan, timeframe, optional budget.

**Testimonials** — URL `/reviews` or `/client-stories` · Review + AggregateRating schema (triggers SERP stars) · 800+ words. Aggregate stat hero + standout featured testimonial → grouped by trip type → each with photo + name + trip + date + quote → third-party review links → "Earn a place here" CTA → Final CTA. **3–4 long-form case studies beat 12 generic quotes.**

---

## 6. WordPress stack (SiteGround + Kadence Pro)

| Layer | Choice | Why |
|---|---|---|
| Hosting | SiteGround GrowBig ($4.99 intro / $29.99 renewal) | Staging, SG Optimizer, free CDN/SSL/backups, unlimited sites, 20GB, ~100k monthly visits |
| Theme | Kadence Pro bundle ($99–199/yr) | Fast, Gutenberg-native, Kadence Conversions for popups |
| SEO | Rank Math Free | 5-keyword vs Yoast's 1, 15+ schema types, llms.txt, AI search tracker |
| Caching | SG Optimizer (free) | Beats WP Rocket on SiteGround infrastructure |
| Analytics | Site Kit + Microsoft Clarity | GA4 + GSC + heatmaps, all free, no session caps |
| Forms | Flodesk-native + Fluent Forms free | Brand-matched newsletter; Fluent for inquiries |
| Booking | Calendly inline embed | Plug-and-play |
| CRO | Microsoft Clarity (free) | Qualitative insight without A/B infra |
| Email | Flodesk | Already in stack |

Skip schema plugins (Rank Math covers it). Skip A/B testing Year 1 (no traffic for significance). Skip OptinMonster (Kadence Conversions replaces it). Skip Yoast Premium and Semrush.

**Antigravity workflow tiers** — for non-developers:
1. Pure Kadence Pro (no Git, no AI) — **recommended for launch**
2. Kadence + Antigravity-generated CSS/JS snippets
3. Kadence + Antigravity child theme via SFTP/Git (medium risk)
4. Full custom theme — overkill Year 1

Antigravity is best as a paired developer for debugging, snippets, and content scripting — **not** a Kadence layout replacement.

**Simplest deploy (weekend launch):**
1. SiteGround GrowBig + domain
2. New WordPress install via Site Tools
3. Install Kadence (free) + Starter Templates
4. Buy + install Kadence Pro bundle
5. Import luxury travel starter template
6. Install Rank Math + Site Kit + Clarity + Fluent Forms + Header Footer Code Manager
7. SG Site Tools → Speed → Dynamic Cache + Memcached + NGINX Direct Delivery
8. Customize via Kadence Customizer
9. Use staging for experiments
10. Paste Flodesk header script
11. Submit `/sitemap_index.xml` to Search Console

---

## 7. Tool comparison (verified May 2026)

| Tool | Entry annual | Sweet spot | Free option | Best for |
|---|---|---|---|---|
| **Semrush** | $117/mo (Pro) | Guru $208/mo | 7-day trial | Overkill for Oomph Year 1 |
| **Ahrefs** | $108/mo (Lite) | Standard $199/mo annual | **Webmaster Tools — best free SEO tool of 2026** | Verified site owners free; Starter $29/mo for competitor data |
| **Mangools** | **$29.90/mo annual (Basic)** | Premium $44.90/mo | Yes + 10-day trial | **Best paid pick for solo travel advisor** |
| **SE Ranking** | $103.20/mo (Core) | Growth $223/mo | 14-day trial | Reasonable Mangools alternative |
| **Ubersuggest** | $29/mo or **$290 lifetime** | n/a | 3 searches/day | Budget option |
| **Microsoft Clarity** | **Free** | Free | Yes, no caps | Solo, any traffic |
| **Hotjar** | n/a | Plus $39/mo | Free 35 sessions/day | When you outgrow Clarity (surveys, interviews) |
| **VWO / Optimizely** | $314+/mo | Enterprise | Limited | Not for solos |

**Recommended Year 1 path**
- **Month 1:** SiteGround + Kadence Pro + Flodesk + all-free SEO/CRO stack (Rank Math, SG Optimizer, Site Kit, Clarity, Ahrefs Webmaster Tools, Fluent Forms, Calendly, Google Keyword Planner)
- **Months 2–3:** Add AnswerThePublic free + Ubersuggest free during keyword sprints
- **Months 4–6:** Add **Mangools Basic $29.90/mo annual** — best ROI paid SEO tool
- **Months 6–9:** Perfmatters $24.95/yr if CWV needs tuning; Rank Math Pro $59/yr if Content AI useful
- **Months 9–12:** A/B testing only if traffic >5,000 unique/mo

**Year-1 budget:** ~$60 hosting + $99–199 Kadence + Flodesk + ~$360 Mangools = **$520–$720/yr in tooling.**

**Avoid Year 1:** Semrush, Ahrefs Lite/Standard, WP Rocket (on SiteGround), VWO/Optimizely, Schema Pro, OptinMonster, Yoast Premium.

---

## 8. Measurement

**KPIs by funnel stage**
- **TOF (leading):** impressions, avg ranking position, AI Overview/featured snippet citations, organic traffic by landing page, direct traffic, brand search MoM
- **MOF:** GA4 engagement rate, scroll depth (50/75/100%), time on service pages, lead-magnet downloads, email opt-ins, open rate (luxury benchmark 25–35%), CTR
- **BOF (lagging):** **Discovery calls booked — #1 KPI** · contact form submissions · proposals sent · call→proposal rate · proposal→booking rate (target 25%+) · avg booking value · time-to-conversion (luxury averages 90 days, sometimes 6–12 months)
- **Loyalty:** repeat booking rate (target 35%+), referral rate (~25%), NPS, CLV
- **Efficiency:** CAC by channel, LTV:CAC ≥ 3:1, gross margin 15–25%

**Dashboard (all free):** GA4 (the what) · GSC (impressions/CTR/position/AI Overview clicks) · Microsoft Clarity (the why) · Looker Studio (unified). **Critical:** integrate Clarity with GA4 — adds a Clarity Playback URL custom dimension so you can watch the recording of any conversion or drop-off from GA4.

**Looker Studio tabs:** Visibility · Acquisition · Engagement · Conversion · Pipeline (manual: inquiries → calls → proposals → bookings → revenue).

**Audit cadence**
- **Daily (5 min):** GSC errors, uptime, Clarity frustration recordings
- **Weekly (30–60 min):** form submissions, conversion trends, top 10 landing pages, ranking changes
- **Monthly (2–4 hrs):** content performance, refresh 2–3 underperformers, backlinks, CWV 28-day CrUX, email metrics, lead-to-booking %, Looker commentary
- **Quarterly (1 day):** full technical audit (Screaming Frog, schema validation, broken links, redirects), competitor SERP review, user testing 3–5 real users, GBP audit, CRO test backlog
- **Annual (2–3 days):** YoY strategic review, full ROI by channel/cluster, brand positioning, persona vs. actual-booked-client audit, refresh keyword targets

---

## 9. CLAUDE.md format

CLAUDE.md is loaded as a user message after the system prompt at the start of every Claude Code session — **advisory, not enforced** (~80% adherence). For deterministic enforcement use Hooks. Claude walks up the directory tree concatenating every CLAUDE.md found.

**Scopes:** `./CLAUDE.md` (project, committed) · `~/.claude/CLAUDE.md` (user-wide) · `./CLAUDE.local.md` (personal, gitignored). `@path/to/file` imports up to 5 hops deep.

**Length:** Anthropic official ≤200 lines. Community consensus 60–80 ideal, 300 ceiling. Claude Code system prompt consumes ~50 of ~150–200 reliable instructions; CLAUDE.md competes with skills, plugins, user messages for the rest.

**Structure:**
1. Project overview (why) — one paragraph
2. Tech stack (what) — short list
3. Project structure / directory tree
4. Commands — exact bash Claude can't guess
5. Coding/workflow rules — `IMPORTANT:` / `YOU MUST` (under 15)
6. What NOT to do — negative rules
7. Pointers to deeper docs — `@docs/x.md` instead of inlining

**Tone**
- Imperative voice ("Use ES modules" not "We prefer ES modules")
- Specific over vague ("2-space indentation" not "format code properly")
- `IMPORTANT:` / `YOU MUST` reserved for 1–2 truly critical rules
- Markdown headers + bullets > paragraphs; code blocks for commands; tables for include/exclude lists

**For Oomph:** prescriptive on SEO/CRO/brand integrity, principle-based on creative/aesthetic choices. Treat CLAUDE.md as code — commit to Git, add a rule when Claude makes the same mistake twice, ruthlessly prune obsolete rules.

**Avoid:** too long, too vague, code-style rules a linter could enforce, inlined long docs, conflicting instructions, personality prompts, auto-generated `/init` files left unedited, `@`-mentioning large docs (embeds whole file every session).

---

## 10. Final rule set for the CLAUDE.md

### Universal page rules
1. ONE primary CTA per page: "Book a Discovery Call" → `/discovery-call` (Calendly inline)
2. Sticky mobile bottom bar with primary CTA at every scroll depth
3. Hero LCP image: WebP (AVIF source if available), <250KB, explicit `width`/`height`, `fetchpriority="high"`, **never** lazy-loaded
4. All other images: WebP/AVIF, responsive `srcset`, lazy below fold, explicit dimensions
5. Unique meta title <60 chars, primary keyword in first 30, ends `| Oomph Travel`
6. Unique meta description 150–160 chars, keyword + benefit + soft CTA
7. ONE H1 per page containing primary keyword
8. Question-formatted H2s where they introduce content
9. **Validate CWV before "done":** LCP <2.5s mobile, INP <200ms, CLS <0.1
10. Self-canonical `<link rel="canonical">`
11. Forms submit GA4 `generate_lead` event + Microsoft Clarity custom tag
12. Schema: Organization sitewide · TravelAgency on home · Service on service pages · Article + Person on blog · Review when testimonials · BreadcrumbList on interior pages · Event on group cruises
13. Every blog post: author byline linked to `/about` with Person schema
14. Every blog post: TL;DR / direct answer in first 200 words
15. Visible `dateModified` timestamp on content pages

### Required page architecture
- **Above fold:** hero image + headline + subhead + primary CTA + one trust signal
- **Mid-page:** one secondary CTA appropriate to content
- **End:** primary CTA repeated + 3 related content cards (whole block clickable, no "Read More")
- **Footer:** contact + credential badges + legal + full nav

### Content rules
16. Pillar 3,000–5,000 words; cluster 1,500–2,500
17. Pillar↔cluster bidirectional linking mandatory
18. 2–5 contextual internal links per 1,000 words, descriptive anchor (never "click here")
19. First-hand experience markers in every blog post
20. Original photography only — never stock for hero/featured
21. Every guide/cornerstone has FAQ section + FAQPage schema (5–8 Qs)
22. File names: `lowercase-hyphenated-descriptive.webp`

### CTA copy rules
23. Never as primary: Submit, Learn More, Click Here, Get Started, Contact Us
24. First-person CTAs: "Book My Discovery Call," "Send Me the Cabin Guide," "Plan My Italy Trip"
25. Microcopy under every CTA ("Free 20-minute call. No pressure, no obligation.")
26. WCAG AA contrast 4.5:1 minimum
27. Tap targets ≥44×44pt, 8–12px spacing

### Trust signal rules
28. CLIA + Nexion logos in trust strip below hero on home/About + in footer
29. Specialist badges shown contextually only (Silversea on cruise pages; DS-Italy on Italy; BritAgent Pro on UK)
30. **Never** display unearned credentials — use "Currently completing" or omit
31. Testimonials include: name (or first + last initial), trip type, destination, date, optional photo
32. Aim for 5–10 Google Business Profile reviews in Year 1

### Popup rules
33. No full-screen mobile popups on initial landing from search
34. Mobile popups/banners ≤20% viewport
35. Time/scroll popups only after 60% scroll or 30s
36. Exit-intent allowed on **desktop** for lead-magnet capture
37. Cookie consent + legal banners always allowed

### Form rules
38. Discovery Call intake: 3-step multi-step form
39. Mark optional, not required
40. Single-column on all devices
41. Inline validation with positive confirmation ("✓ looks good")
42. Privacy microcopy: "We respect your inbox. Used solely to plan your trip."
43. Lead magnet landings: single email field only

### Fee transparency
44. Dedicated `/fees` or `/fee-structure` page in v1, linked from every service + Contact page
45. Lead with the problem the fee solves, not the fee itself
46. Standard line: "Planning fees fund my undivided attention to your trip. Commissions don't change your price."

### Page-type specifics
- **Home hero:** dual CTA (Discovery Call primary + lead magnet secondary)
- **Service pages v1:** `/luxury-cruise-planning`, `/custom-italy-travel`, `/multi-generational-travel-planning` — each a cluster hub
- **Lead magnet pages:** no main nav · 3D cover + outcome headline + single-field email form + "Send me the guide" · thank-you upsells Discovery Call
- **Group cruise pages:** `/group-cruises/[destination]-[year]` · Event schema · weekly-updated real scarcity · per-person + single-supplement pricing · 10–15 FAQs · day-by-day accordion
- **Discovery Call:** Calendly inline (never modal) · 20-minute positioning · short intake (name, email, plan, timeframe, optional budget)

### Voice and brand (principle-based)
47. Tone: aspirational, intimate, advisor-not-salesperson — trusted concierge who has been there
48. Use "I" not "we" — solo practice is the strength
49. Specifics beat adjectives ("Sailed 14 Silversea voyages" > "experienced cruise expert")
50. Avoid superlatives (best, ultimate, amazing)
51. Sensory language for luxury (sun-warmed, candlelit, hand-poured, family-run, third-generation, vine-draped, marble-cooled)
52. Frame for gain not loss aversion
53. No countdown timers, no fake scarcity, no live chat bots, no aggressive popups

### Verification before "done"
54. Lighthouse mobile: LCP <2.5s, INP <200ms, CLS <0.1
55. Validate schema with Google Rich Results Test
56. Primary CTA renders above fold AND in mobile sticky bar
57. No console errors
58. Mobile + desktop screenshots compared to spec

### Ask before doing
59. Installing a new plugin
60. Changing primary CTA copy or destination
61. Modifying schema structure
62. Adding any popup/interstitial
63. Embedding any third-party script (~34ms load each)

---

## The unifying principle

In 2026 SEO and CRO are one discipline: **build pages that win one user — clearly, quickly, credibly.** Google's recent Core Updates penalized exactly what CRO has always discouraged (slow load, generic content, faceless brands, manipulative tactics, thin authority) and rewarded what CRO encourages (clear value propositions, named experts, original media, focused conversion paths, real trust signals).

For Oomph specifically, the advantages compound: a solo advisor with named credentials, original photography, transparent fees, and a tight niche is precisely what Google's HCU and AI search systems now favor — and exactly what affluent multi-gen buyers want to find. The biggest leverage points: **(1)** three service pages as cluster hubs, **(2)** 20+ blog posts in pillar–spoke clusters, **(3)** inline Calendly Discovery Call page, **(4)** Event-schema'd group cruise pages, **(5)** ruthless CWV adherence.

Launch on the near-zero-cost stack (SiteGround GrowBig + Kadence Pro + Rank Math + Microsoft Clarity + Ahrefs Webmaster Tools + Site Kit), add Mangools around month 4, resist premium SEO suites until traffic crosses 5,000 unique monthly. Keep the CLAUDE.md under 200 lines, imperative voice, `IMPORTANT:` markers only on the 1–2 most critical rules, link out to `@docs/schema.md`, `@docs/voice-guide.md`, `@docs/cro-backlog.md` rather than inlining. Update it as a living document — add rules when Claude makes the same mistake twice, prune ruthlessly.