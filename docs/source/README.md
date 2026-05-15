# Source documents

Original strategic and brand inputs for the Oomph Travel rebuild. Markdown derivatives are committed here so Claude Code can grep and quote from them during builds. Binary originals (PDFs, docx) are kept locally but gitignored — the repo is public and binaries bloat history.

## Inventory

| File | Status | Notes |
|---|---|---|
| `brand-book-2026.pdf` | **gitignored** | Oomph Travel — Brand Book MMXXVI. Voice, color, typography, geometry, photography direction. Sourced into `docs/brand-tokens.md` and `docs/voice-guide.md`. |
| `seo-cro-foundations-2026.pdf` | **gitignored** | Full SEO and CRO research report. Sourced into `docs/cro-rules.md` and `docs/schema.md`. |
| `seo-cro-condensed.md` | committed | Compressed markdown of the SEO/CRO report. Every section retained; benchmark statistics trimmed. The reference Claude Code reads during page builds. |
| `build-plan-v2.docx` | **gitignored** | Comprehensive 16-phase build plan (DDEV, custom plugin, Playwright tests, page playbooks). Treated as the canonical spec; `BUILD-PLAN.md` at the repo root is its in-repo working version. |

## When to read these

- **Page builds**: read `seo-cro-condensed.md` for the per-page-type checklist.
- **Brand questions** that aren't already in `docs/brand-tokens.md` or `docs/voice-guide.md`: open the Brand Book PDF locally.
- **Plan questions** not covered by `BUILD-PLAN.md`: open `build-plan-v2.docx` locally.

## When NOT to read these

Day-to-day work should rely on the derivative docs in `docs/` (brand-tokens, voice-guide, schema, cro-rules). The source documents are reference, not operating instructions.
