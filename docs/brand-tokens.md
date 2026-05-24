# Brand Tokens — Oomph Travel

Single source of truth for colors, typography, spacing, radii, and shadows. Pulled from the Brand Book (Volume I, MMXXVI). When CSS or `theme.json` references a token, the value lives **only** here. Update here first, then propagate.

---

## Colors

### Primary palette — three colors do most of the work

> **Palette evolution (2026-05):** the brand moved from Peacock Ink + Terracotta to **Palette A "Deep Marine"** — Marine Navy + Old Brass + Warm Bone — for a stronger premium-cruise register (chosen via the Claude Design exploration). The legacy CSS var / theme.json slug names (`--color-peacock-ink`, `--color-terracotta-warm`, `peacock-ink`, etc.) are retained as aliases so existing references keep working; only the values changed.

| Token | Hex | Role | CSS var |
|---|---|---|---|
| Marine Navy | `#0E3B5E` | H1s, links, primary nav, footer ground | `--color-marine-navy` (alias `--color-peacock-ink`) |
| Old Brass | `#B58A4A` | CTAs, hover, pull rules, the one warm note | `--color-old-brass` (alias `--color-terracotta-warm`) |
| True Ink | `#14171A` | Body type, masking, inverse hero ground | `--color-true-ink` |

**Pure white never appears. Pure black never appears.**

### Neutrals — the canvas

| Token | Hex | Role | CSS var |
|---|---|---|---|
| Paper | `#FEFCF8` | Off-white | `--color-paper` |
| Bone | `#FDFAF2` | Default background — warm cream | `--color-bone` |
| Mist | `#F1EADC` | Alt background | `--color-mist` |
| Stone | `#C5BBA8` | Hairlines | `--color-stone` |
| Slate | `#7C786C` | Meta type | `--color-slate` |
| Charcoal | `#2C2A26` | Body alt | `--color-charcoal` |

### Secondary accents — one per section, never two

| Token | Hex | Use case | CSS var |
|---|---|---|---|
| Midnight Deep | `#082939` | Footer, premium blocks, confirmations | `--color-midnight-deep` (alias `--color-deep-peacock`) |
| Bronze | `#8C6A36` | Old Brass hover, press / Puglia editorial | `--color-bronze` (alias `--color-muted-brick`) |
| Warm Ochre | `#C2873E` | Highlight | `--color-warm-ochre` |
| Soft Sage | `#8A9A85` | Provence, European itinerary | `--color-soft-sage` |
| Champagne | `#D4B98C` | Cabin notes, evening, premium documents | `--color-champagne` |
| Dusty Rose | `#C9A09A` | Considered solo use | `--color-dusty-rose` |

### Semantic — forms and feedback only

| Token | Hex | Role | CSS var |
|---|---|---|---|
| Success — Forest Sage | `#4A6B4F` | Form success | `--color-success` |
| Warning — Burnished Amber | `#B8893A` | Form warning | `--color-warning` |
| Error — Garnet | `#9A2A26` | Form error | `--color-error` |
| Info — Marine Info | `#1A4565` | Form info | `--color-info` |

### Color recipe — 60 · 20 · 10 · 6 · 4

The default proportion that keeps the brand legible:

- **60%** warm neutrals (Paper, Bone, Mist)
- **20%** Ink + Charcoal
- **10%** Peacock Ink
- **6%** Terracotta Warm
- **4%** one secondary accent

On editorial pages, push neutrals to 70 and Terracotta to 3. On inquiry flows, Peacock and Terracotta can climb to ~18 combined. Never depart further than that.

### The four signature combinations

1. **The Hero** — Bone · Peacock Ink · Terracotta Warm. The default web hero.
2. **The Editorial Inversion** — True Ink · Champagne · Bone. Footers, premium blocks, confirmations.
3. **The European Itinerary** — Bone · Soft Sage · Muted Brick · Champagne. Cereal-spread energy.
4. **The Quiet Premium** — Paper · Deep Peacock · Stone hairlines. Forms, advisor bio, contemplation.

---

## Typography

### Type families

| Family | Role | Source | CSS var |
|---|---|---|---|
| Fraunces | Literary register — H1, H2, italic display | Google Fonts (self-hosted) | `--font-display` |
| Inter | Credentialed register — body, UI, meta | Google Fonts (self-hosted) | `--font-text` |

Self-host both. `font-display: swap`. Preload the two weights used above the fold (Fraunces 300, Inter 400).

### Scale (mobile-first; desktop multipliers in parens)

| Token | Mobile | Desktop | Use |
|---|---|---|---|
| `--text-eyebrow` | 12px/16 | 13px/18 | Section eyebrows, all-caps Inter 500 tracked +0.08em |
| `--text-body-sm` | 14px/22 | 15px/24 | Captions, meta |
| `--text-body` | 16px/26 | 17px/28 | Body prose — Inter 400 |
| `--text-lead` | 18px/30 | 20px/32 | Subheads — Fraunces italic 300 |
| `--text-h3` | 22px/30 | 26px/34 | Fraunces 400 |
| `--text-h2` | 28px/36 | 36px/44 | Fraunces 400, italic permitted for emphasis |
| `--text-h1` | 36px/42 | 56px/62 | Fraunces 300 — display |
| `--text-display` | 48px/52 | 88px/92 | Hero only — Fraunces 300 italic |

### Long-form prose

Constrain to **66–72ch**. Generous whitespace inside the layout; never inside the image. Photographs are full-bleed.

### Pairing rules

- Fraunces sets the headline, Inter sets the body.
- Italic Fraunces is reserved for emphasis and editorial pull-quotes. Don't run italic for more than one sentence in body text.
- All-caps is for eyebrows and credentials only. Track Inter +0.08em when set in caps.

---

## Spacing — 8-point baseline

| Token | px |
|---|---|
| `--space-1` | 4 |
| `--space-2` | 8 |
| `--space-3` | 12 |
| `--space-4` | 16 |
| `--space-5` | 24 |
| `--space-6` | 32 |
| `--space-7` | 40 |
| `--space-8` | 56 |
| `--space-9` | 72 |
| `--space-10` | 96 |
| `--space-11` | 128 |

**Container max 1280px. Gutter 40px on desktop. Long-form prose constrains to 66–72ch.**

---

## Radii — squared geometry

| Token | px | Use |
|---|---|---|
| `--radius-xs` | 2 | Hairline accents |
| `--radius-sm` | 4 | Buttons |
| `--radius-md` | 8 | Cards |
| `--radius-lg` | 12 | Modals |

The brand favors **squared geometry**. Never use pill radius (`9999px`) except for filter chips in dense filtering UI. No exception for buttons.

---

## Elevation — prefer hairlines over shadow

| Token | Use | Spec |
|---|---|---|
| `--shadow-hairline` | Default — prefer over shadow | `0 0 0 1px var(--color-stone)` |
| `--shadow-sm` | Cards · resting state | `0 1px 2px rgba(20,23,26,0.04), 0 1px 1px rgba(20,23,26,0.06)` |
| `--shadow-md` | Cards · hover only | `0 4px 12px rgba(20,23,26,0.08)` |
| `--shadow-lg` | Modals · dropdowns only | `0 12px 32px rgba(20,23,26,0.12)` |

---

## Hero scrim (photography overlay)

```css
background-image: linear-gradient(180deg, rgba(20,23,26,0.05), rgba(20,23,26,0.55));
```

Never glassmorphism. Never blur. The photograph carries the atmosphere; the scrim carries the type.

---

## Logo

- Canonical asset: `logo-full.svg` — full lockup, sits only on Paper, Bone, or Mist
- Favicon (mark only): 32px+
- Min digital width: 120px
- Min print width: 28mm
- Clear space: at least 1× cap-height of the wordmark on every side
- Never: recolor, rotate, stretch, add shadow, set on brand color or gradient, photograph, swap the lockup

---

## When in doubt

Use the supplied SVG, on Bone or Paper, at a generous size, with clear space. Anything else needs sign-off.
