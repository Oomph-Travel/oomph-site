# Structured Data — Division of Responsibility

Who owns what between **`oomph-travel-core`** (the custom plugin) and **Rank Math Pro**.
Single rule: **the plugin is the sole source of JSON-LD; Rank Math never emits structured data.**

---

## `oomph-travel-core` owns ALL structured data (JSON-LD)

Emitted as one combined `@graph` from `includes/class-schema.php` (`Schema::output()` on `wp_head`, priority 5), `@context: "https://schema.org"`:

| `@type` | Where |
|---|---|
| `Organization` / `TravelAgency` | Every page (`#organization`) |
| `Person` | Every page (`/about/#advisor`) |
| `Service` | Service hub pages |
| `Event` | Cruise CPT (`oomph_cruise`) |
| `BreadcrumbList` | Singular pages + front page |
| `BlogPosting` | Journal posts |
| `FAQPage` | Service pages with real Q&A |
| **Future:** `Review` + `AggregateRating` | When `/client-stories` ships (real, attributable, on-page reviews only — `docs/cro-rules.md` R31) |

## Rank Math owns everything EXCEPT structured data

- Page titles + meta descriptions
- Canonical URLs
- OpenGraph + Twitter Card tags
- XML sitemap (`/sitemap_index.xml`)
- `robots.txt` directives
- Google Search Console integration
- Analytics, rank tracking, Content AI, link counter, instant indexing

**Rank Math emits NO JSON-LD.** Its Schema / Rich Snippets module stays disabled.

---

## Why

The schema audit on 2026-05-26 (`docs/schema-audit-2026-05-26.md`) found that Rank Math's
Schema module (`rich-snippet`) is **disabled** on staging and production, so it emits no
JSON-LD today — the plugin is already the only source. But Rank Math's settings are
**pre-configured to collide** the instant that module is enabled (`knowledgegraph_type` set,
posts and pages defaulting to `Article` rich snippets). A single dashboard toggle or a re-run
of the setup wizard would silently produce duplicate `Person`, duplicate `Organization`, and
`Article` on every page — bad for SEO and confusing to AI engines.

To make the safe state durable rather than dependent on a UI toggle, `Schema::init()` registers:

```php
add_filter( 'rank_math/json_ld', '__return_empty_array', 99 );
```

This empties Rank Math's JSON-LD graph unconditionally, so even if the Schema module is turned
on, Rank Math still emits no structured data. The plugin remains the single source — by code,
not by configuration.

### If we ever want Rank Math to own a schema type

Don't just enable the module (that re-arms all the collisions above). Instead, make the filter
*selective* — unset only the plugin-owned `@type`s from the `$data` array and let the desired
type through — and remove that type from `Schema::output()` so the two never overlap.
