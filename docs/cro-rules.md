# SEO + CRO Rules

The unified rule set. These are the rules Claude Code MUST follow on every page. Numbering preserved from the source research so the team can reference rules by ID in commit messages and PR reviews.

---

## Universal page rules

**R1** · Every page MUST have ONE primary CTA: **"Start a conversation →"** linking to `/discovery-call/`.

**R2** · Every page MUST include a sticky mobile bottom bar with the primary CTA, visible at all scroll depths.

**R3** · Hero LCP image MUST be WebP (with AVIF source if available), under 250KB, with explicit `width` and `height` attributes, `fetchpriority="high"`, and NEVER lazy-loaded.

**R4** · All other images MUST be WebP/AVIF, responsive `srcset`, lazy-loaded below the fold, with explicit `width`/`height` (prevents CLS).

**R5** · Every page MUST have a unique meta title under 60 characters containing the primary keyword in the first 30 chars, ending with ` | Oomph Travel`.

**R6** · Every page MUST have a unique meta description 150–160 characters with primary keyword + benefit + soft CTA.

**R7** · Every page MUST have ONE H1 containing the primary keyword.

**R8** · All H2 headers SHOULD be question-formatted where they introduce content (improves AI Overview citation).

**R9** · Every page MUST validate Core Web Vitals before declaring "done": LCP < 2.5s mobile, INP < 200ms, CLS < 0.1.

**R10** · Every page MUST self-canonical via `<link rel="canonical">`.

**R11** · All forms MUST submit a GA4 event named `generate_lead` (with custom parameter `lead_source`) and a Microsoft Clarity custom tag.

**R12** · Schema markup on every page: Organization sitewide; TravelAgency on home; Service on service pages; Article + Person on blog; Review when testimonials present; BreadcrumbList on every interior page; Event on group cruise pages.

**R13** · Every blog post MUST have an author byline linked to `/about/` with Person schema.

**R14** · Every blog post MUST include a TL;DR / direct answer in the first 200 words.

**R15** · Visible `dateModified` timestamp on all content pages.

---

## Required page architecture (every page)

**Above the fold:** hero image + headline + subhead + primary CTA + one trust signal (CLIA, Nexion, or Silversea badge).

**Mid-page:** one secondary CTA appropriate to content (e.g., "Send me the cabin guide").

**End-of-page:** primary CTA repeated + 3 related content cards (whole block clickable, no "Read More" buttons).

**Footer:** contact info, credential badges, legal links, full nav, Person + Organization schema reference.

---

## Content rules

**R16** · Pillar pages: 3,000–5,000 words. Cluster posts: 1,500–2,500 words.

**R17** · Pillar ↔ cluster bidirectional linking is mandatory (pillar links to every cluster; each cluster links back).

**R18** · 2–5 contextual internal links per 1,000 words with descriptive anchor text. Never "click here" or "learn more."

**R19** · First-hand experience markers required in every blog post ("When I sailed the Silver Nova in March 2025…").

**R20** · Use original photography from Eric's travels. Never use generic stock for hero or featured imagery.

**R21** · Every guide/cornerstone post MUST have an FAQ section with FAQPage schema (5–8 questions).

**R22** · File names: `lowercase-hyphenated-descriptive.webp`. Never `IMG_4823.jpg`.

---

## CTA copy rules

**R23** · Never use as primary CTA: "Submit," "Learn More," "Click Here," "Get Started," "Contact Us."

**R24** · Use first-person CTAs: "Book My Discovery Call," "Send Me the Cabin Guide," "Plan My Italy Trip."

**R25** · Every CTA includes microcopy below it (e.g., "Email, text, or a quick call — whatever's easiest for you.").

**R26** · WCAG AA contrast: 4.5:1 minimum on every button.

**R27** · Tap targets minimum 44×44pt with 8–12px spacing.

---

## Trust signal rules

**R28** · CLIA + Nexion logos visible in trust strip below hero on home/About; display in footer.

**R29** · Supplier specialist badges displayed contextually only: Silversea Ultra-Luxury Specialist on cruise pages; BritAgent Pro on UK pages.

**R30** · NEVER display unearned credentials as earned. Use "Currently completing" or omit entirely.

**R31** · Testimonials include: name (or first name + last initial), trip type, destination, date, optional photo.

**R32** · Aim for 5–10 quality reviews on Google Business Profile in Year 1.

---

## Popup / email capture rules

**R33** · NEVER use full-screen popups on mobile on initial landing from search (intrusive interstitial penalty).

**R34** · Mobile popups/banners limited to ≤20% viewport.

**R35** · Time/scroll-based popups only trigger after 60%+ scroll depth or 30+ seconds.

**R36** · Exit-intent popups are allowed on desktop for lead-magnet capture only.

**R37** · Cookie consent and legal banners always allowed (Google exempts these).

---

## Form rules

**R38** · Discovery Call intake is a **3-step multi-step form** (multi-step converts 86% higher than single-step).

**R39** · Mark optional fields, not required. Reduces visual clutter.

**R40** · Single-column form layout on all devices.

**R41** · Inline validation with positive confirmation ("✓ looks good").

**R42** · Privacy microcopy under every form: "We respect your inbox. Used solely to plan your trip."

**R43** · Lead-magnet landing pages: single email field only — no name, no phone.

---

## Fee transparency rules

**R44** · Dedicated `/how-i-work/` page lives in v1, linked from every service page and Contact page.

**R45** · Lead with the problem the fee solves, not the fee itself.

**R46** · Standard fee value line: "Planning fees fund my undivided attention to your trip. Commissions don't change your price."

---

## Voice and brand rules (principle-based)

**R47** · Tone: aspirational, intimate, advisor-not-salesperson. Write like a trusted concierge who has personally been to the destination.

**R48** · Use "I" not "we." Oomph is a solo advisor — that's the strength.

**R49** · Specifics beat adjectives. "Sailed 14 Silversea voyages" beats "experienced cruise expert."

**R50** · Avoid superlatives ("the best," "ultimate," "amazing").

**R51** · Sensory language for luxury allowed when earned: sun-warmed, candlelit, hand-poured, family-run, third-generation, vine-draped, marble-cooled.

**R52** · Frame for gain, not loss aversion. "Make this 50th anniversary the one they'll talk about for 30 years."

**R53** · No countdown timers, no fake scarcity, no live chat bots, no aggressive popups. These erode the luxury positioning.

**See `voice-guide.md` for the full No List.**

---

## Verification block — run before declaring a task done

**R54** · Run Lighthouse on the changed page (mobile profile). Confirm LCP < 2.5s, INP < 200ms, CLS < 0.1.

**R55** · Validate schema with [Google Rich Results Test](https://search.google.com/test/rich-results).

**R56** · Confirm primary CTA renders correctly above the fold AND in the mobile sticky bar.

**R57** · Confirm no console errors and no failed network requests.

**R58** · Take mobile + desktop screenshots and compare against the spec.

**R59** · Confirm WCAG AA contrast on every text/background pair.

**R60** · Confirm internal links open in same tab; external links in new tab with `rel="noopener"`.

---

## Things Claude Code MUST ASK before doing

**R61** · Installing a new WordPress plugin (flag for review first).

**R62** · Changing the primary CTA copy, destination URL, or microcopy.

**R63** · Modifying any schema markup structure.

**R64** · Adding any popup or interstitial element.

**R65** · Embedding any third-party script (each adds ~34ms load time).

**R66** · Modifying anything in `wp-config.php` or `.htaccess` on production (always staging first).

---

## Hierarchy when rules conflict

When two rules pull in different directions, this is the order:

1. **Legal / privacy** (cookie consent, GDPR, accessibility) — non-negotiable.
2. **Core Web Vitals** — speed wins over visual richness. If a hero video hurts LCP, replace it with a still.
3. **Conversion** — the primary CTA stays above the fold. The trust strip stays visible. Form length stays minimal.
4. **SEO** — title, H1, schema, canonical, internal links.
5. **Voice and brand** — the No List, the color recipe, the squared geometry.
6. **Visual delight** — additive only, never at the expense of 1–5.

When in doubt: ask before shipping.
