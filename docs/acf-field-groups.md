# ACF Field Groups — Oomph Travel

Source-of-truth document for the structured fields that drive page content. **Define each group via the ACF Pro UI in WP admin**; the JSON sync writes the saved configuration to `wp-content/plugins/oomph-travel-core/acf-json/`, which is version-controlled and deployed via the plugin rsync.

**Sync target redirected:** `plugins/oomph-travel-core/includes/class-acf-config.php` filters `acf/settings/save_json` and `acf/settings/load_json` so the JSON lives with the CPTs it describes, not in the theme. Don't put `acf-json/` inside the theme — the filter overrides theme-based detection.

---

## Workflow

1. Install ACF Pro on Local first (then staging + production).
2. WP admin → **ACF → Field Groups → Add New**.
3. Add the fields per spec below.
4. Set the Location rules per spec.
5. Save. ACF auto-writes `group_<id>.json` into `plugins/oomph-travel-core/acf-json/`.
6. `git add` the new JSON files, commit, push develop → staging → main → prod.
7. Repeat for each environment: after deploy, ACF detects the JSON and offers to sync. Sync to write the field groups into the local DB.

After sync, editing fields in the admin UI on any environment regenerates the JSON. **Treat the JSON as authoritative** — if it disagrees with the DB on staging or prod, sync from JSON.

---

## Group 1 — Page Hero

**Purpose:** structured hero content for any Page (Home, About, service hubs, Discovery Call, etc.). The template reads these fields and falls back to defaults when absent.

**Location rules:** `Post Type` is equal to `Page`.

**Position:** High (after title).
**Style:** Default.
**Label placement:** Top.

| Field name | Type | Required | Default | Notes |
|---|---|---|---|---|
| `hero_eyebrow` | Text | No | — | Max 40 chars. All-caps in the rendered output (Inter 500, tracked +0.08em). |
| `hero_headline` | Text | No | — | Max 80 chars. Renders as H1, Fraunces 300 italic. Leave empty to use the page template's built-in fallback (template never renders blank). |
| `hero_subhead` | Textarea | No | — | Max 200 chars. Fraunces italic 400. |
| `hero_image` | Image | No | — | Return format: Array. 1920×1080 WebP target. If empty, hero uses Bone canvas with no photograph. |
| `hero_cta_label` | Text | No | `Book a Discovery Call →` | Primary CTA copy. Don't change without updating `cro-rules.md` R1. |
| `hero_cta_url` | Text | No | `/discovery-call/` | Primary CTA destination. Accepts relative paths or fully-qualified URLs (Text type, not URL — ACF's URL type rejects relative paths). |
| `hero_trust_strip` | True/False | No | `true` | Whether to show the credentials strip below the hero. |

---

## Group 2 — Service Page

**Purpose:** structured content for the three service hub pages (Luxury Cruise Planning, Custom Italy Travel, Multi-Generational Travel). Drives the consistent section anatomy without per-page template duplication.

**Location rules:** `Post Type` is equal to `Page` AND `Page Template` is equal to `service-page.php`. (Create the template stub when we build the first service page in Phase 10.5.)

**Position:** Normal.

| Field name | Type | Required | Notes |
|---|---|---|---|
| `service_keyword` | Text | Yes | Primary SEO keyword. Used in title placeholder + meta. |
| `service_negative_qualifiers` | Repeater | No | 1–4 rows. Each row: `bullet` (Text). Renders as "Who this is NOT for" — pre-qualifies. |
| `service_what_you_do` | Repeater | No | 4–8 rows. Each row: `headline` (Text), `body` (Textarea). The deliverables grid. |
| `service_credentials_to_show` | Checkbox | No | Choices: `clia` · `silversea` · `nexion` · `britagent` · `ds_italy`. Contextual credential display per R29. |
| `service_faqs` | Repeater | Yes (5–8 rows) | Each row: `question` (Text), `answer` (Textarea). Feeds FAQPage schema. |

**Min/max on `service_faqs`:** 5 minimum, 8 maximum. Below 5 is too thin for FAQPage schema to matter; above 8 reads padded.

---

## Group 3 — Group Cruise

**Purpose:** structured fields for hosted group cruise landing pages. Drives the Event schema, the day-by-day accordion, and the scarcity display.

**Location rules:** `Post Type` is equal to `oomph_cruise` (registered by `oomph-travel-core` plugin).

**Position:** Normal.

| Field name | Type | Required | Notes |
|---|---|---|---|
| `cruise_ship_name` | Text | Yes | e.g., "Silver Nova" |
| `cruise_line` | Text | Yes | e.g., "Silversea" |
| `cruise_region` | Taxonomy | Yes | Field type: Taxonomy. Taxonomy: `oomph_region`. Single value. |
| `cruise_dates_start` | Date Picker | Yes | Display format: F j, Y · Return format: Y-m-d (for schema). |
| `cruise_dates_end` | Date Picker | Yes | Same formats. |
| `cruise_price_per_person` | Number | Yes | USD. No decimals. Drives Event `offers.price`. |
| `cruise_single_supplement` | Number | No | USD. |
| `cruise_cabins_remaining` | Number | Yes | Real scarcity (R53). Update weekly. |
| `cruise_itinerary` | Repeater | Yes (7–21 rows) | Each row: `day` (Number), `port` (Text), `activity` (Textarea). |
| `cruise_inclusions` | Textarea | No | Plain text, line-break separated. |
| `cruise_exclusions` | Textarea | No | Plain text. |
| `cruise_deposit_amount` | Number | No | USD. |
| `cruise_deposit_deadline` | Date Picker | No | |

---

## Rebuilding from scratch

If the `acf-json/` directory is ever wiped or corrupted, this document is the recipe to recreate the field groups via UI. Field names are exact; don't rename — the template reads them by name.

---

## Don't define field groups via PHP

ACF supports defining field groups via `acf_add_local_field_group()` in PHP. Tempting, but it breaks JSON sync — fields defined in PHP can't be edited in the UI. We chose the UI + JSON approach because:

- Eric can adjust labels and help text without touching code
- Field group changes ship via standard git diff
- The same JSON file works across local / staging / production
