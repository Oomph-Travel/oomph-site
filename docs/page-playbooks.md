# Page Playbooks

Populated incrementally as pages are built. Each entry documents what was actually shipped — copy that survived voice review, schema that validated, decisions made during build, gotchas encountered.

Canonical specs are in [`source/build-plan-v2.docx`](source/build-plan-v2.docx) §10. This file is the **as-built** record, not the spec.

---

## Status

| Page | URL | Status | Notes |
|---|---|---|---|
| Home | `/` | Not started | First page to build (Phase 10.3) |
| About | `/about` | Not started | Phase 10.4 |
| Luxury Cruise Planning | `/luxury-cruise-planning` | Not started | Phase 10.5 — cluster pillar |
| Custom Italy Travel | `/custom-italy-travel` | Not started | Phase 10.6 — cluster pillar |
| Multi-Generational Travel | `/multi-generational-travel-planning` | Not started | Phase 10.7 — cluster pillar |
| Discovery Call | `/discovery-call` | Not started | Phase 10.8 — primary conversion |
| Lead Magnet — Italy Guide | `/italy-planning-guide` | Not started | Phase 10.9 |
| Lead Magnet — Cabin Guide | `/cruise-cabin-guide` | Not started | Phase 10.9 |
| Fees | `/how-i-work` | Not started | Phase 10.10 |
| Client Stories | `/client-stories` | Not started | Phase 10.11 |
| Group Cruise template | `/group-cruises/[slug]` | Not started | Phase 10.12 — template only in v1 |
| Journal index | `/journal` | Not started | Phase 10.13 |
| Legal | `/privacy`, `/terms`, `/accessibility` | Not started | Phase 10.14 |

---

## Template per page

When a page ships, add an entry below with:

- **Final title / meta description** (with character counts)
- **Final H1**
- **Schema types injected** (Organization sitewide, plus page-specific)
- **Word count** (visible body)
- **CTAs** (which appear where)
- **Internal links** sent + received
- **Verification results** — Lighthouse scores, Rich Results Test status, axe DevTools count
- **Voice review notes** — anything rewritten to clear the No List
- **Deviations from playbook** — and why
