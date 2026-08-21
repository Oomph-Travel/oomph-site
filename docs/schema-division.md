# Structured Data — Division of Responsibility

Who owns what between **`oomph-travel-core`** (the custom plugin) and **Rank Math Pro**.
Single rule: **the plugin owns every JSON-LD type it can build itself; Rank Math emits only
the block-driven types the plugin cannot see** (`FAQPage`, `HowTo`).

---

## `oomph-travel-core` owns ALL structured data (JSON-LD)

Emitted as one combined `@graph` from `includes/class-schema.php` (`Schema::output()` on `wp_head`, priority 5), `@context: "https://schema.org"`:

| `@type` | Where |
|---|---|
| `Organization` / `TravelAgency` | Every page (`#organization`) |
| `Person` | Every page (`/about/#advisor`) — name + description read from the WP user record via `includes/class-advisor.php` |
| `Service` | Service hub pages |
| `Event` | Cruise CPT (`oomph_cruise`) |
| `BreadcrumbList` | Singular pages + front page |
| `BlogPosting` | Journal posts |
| `FAQPage` | Service pages with real Q&A |
| **Future:** `Review` + `AggregateRating` | When `/client-stories` ships (real, attributable, on-page reviews only — `docs/cro-rules.md` R31) |

## Rank Math owns block-driven schema, plus everything that isn't structured data

| `@type` | Where |
|---|---|
| `FAQPage` | Posts using the `rank-math/faq-block` (every journal post) |
| `HowTo` | Posts using the `rank-math/howto-block` |

Plus, as always:

- Page titles + meta descriptions
- Canonical URLs
- OpenGraph + Twitter Card tags
- XML sitemap (`/sitemap_index.xml`)
- `robots.txt` directives
- Google Search Console integration
- Analytics, rank tracking, Content AI, link counter, instant indexing

**Rank Math emits no other JSON-LD.** Its Schema module is enabled (as of 2026-08),
but `Schema::filter_rank_math_graph()` strips every type outside the table above.

---

## Why

The schema audit on 2026-05-26 (`docs/schema-audit-2026-05-26.md`) found that Rank Math's
Schema module (`rich-snippet`) is **disabled** on staging and production, so it emits no
JSON-LD today — the plugin is already the only source. But Rank Math's settings are
**pre-configured to collide** the instant that module is enabled (`knowledgegraph_type` set,
posts and pages defaulting to `Article` rich snippets). A single dashboard toggle or a re-run
of the setup wizard would silently produce duplicate `Person`, duplicate `Organization`, and
`Article` on every page — bad for SEO and confusing to AI engines.

To make the safe state durable rather than dependent on a UI toggle, `Schema::init()` originally
registered:

```php
add_filter( 'rank_math/json_ld', '__return_empty_array', 99 );
```

### Why that changed (2026-08)

Emptying the graph unconditionally also discarded the one thing Rank Math is genuinely better
placed to build: **schema derived from blocks in the post content.** Every journal post uses the
`rank-math/faq-block`, and the block rendered on the page while its `FAQPage` schema was silently
dropped at priority 99 — Rank Math's API reported the post's types as `["BlogPosting","FAQPage"]`,
but only `BlogPosting` (the plugin's) ever reached the page. The plugin cannot replace this: it
would have to parse post content for blocks, and the FAQ content lives in the block, not in a
field the plugin can read.

The filter is now **selective**, which is what the previous version of this section prescribed:

```php
add_filter( 'rank_math/json_ld', array( __CLASS__, 'filter_rank_math_graph' ), 99 );
```

`filter_rank_math_graph()` keeps only `RANK_MATH_ALLOWED_TYPES` (`FAQPage`, `HowTo`) and unsets
every other node, so the duplicate `Person` / `Organization` / `Article` collisions stay blocked
by code rather than by configuration. It also drops Rank Math's `FAQPage` on any page where the
plugin already emits one, so the two can never both claim it.

Consequence: posts with an FAQ block now render **two** `ld+json` blocks — the plugin's `@graph`
and Rank Math's `FAQPage`. That is valid; Google merges structured data across blocks.

### If we ever want Rank Math to own another schema type

Add it to `RANK_MATH_ALLOWED_TYPES` (or via the `oomph_rank_math_allowed_schema` filter) **and**
remove that type from `Schema::output()`, so the two never overlap.
