# E-LIKAS staff dashboard: design system

The one written source of truth for how the staff dashboard looks. Every staff
page (everything that extends `layouts/app.blade.php`) uses these tokens and
component classes. The public resident pages (home, find-evacuation-centers,
community-alerts, hotlines) are out of scope for now.

Where it lives in code:

- **Tokens:** `tailwind.config` in `resources/views/layouts/app.blade.php`
  (`brand`, `navy`, `field` colors; `Public Sans` as `font-sans`).
- **Component classes:** the `<style type="text/tailwindcss">` block in the same
  layout (`@layer base` + `@layer components`). Pages use these class names
  instead of re-typing long utility strings.

## Direction

A civic ledger, not a SaaS dashboard kit. Staff read headcounts under stress and
copy them into DSWD/DROMIC forms, so the dashboard borrows the vocabulary of
those forms: ruled figure strips, sentence-case labels, a heavy rule above a
total. EC Board (`ec-board/show.blade.php`) is the reference page.

1. **Numbers first.** Figures sit in ruled strips, not colored tiles. Columns of
   numbers are right-aligned and use tabular figures.
2. **Color means something.** Blue means action or current selection. Green,
   amber, red and gray mean state. Everything else is ink on white. No
   decorative tints and no colored accent borders.
3. **One primary action per view.** Everything else is secondary. Destructive
   actions are red-outlined, never solid. The only solid red button in the app
   is *Send emergency alert*.
4. **Accessible by construction.** Every text pairing below meets WCAG AA
   (4.5:1, or 3:1 for large text). Form-field borders meet the 3:1 non-text
   rule. Focus is always visible. Motion respects `prefers-reduced-motion`.

The navy sidebar with the illustrated emblem is the one bold element. Everything
around it stays quiet.

## 1. Color tokens

### Brand and surfaces

| Token | Hex | Tailwind | Use |
|---|---|---|---|
| Navy | `#1F3A6E` | `navy` | Sidebar only (identity) |
| Brand | `#2563EB` | `brand`, `brand-600` | Primary buttons, links, active nav, focus |
| Brand dark | `#1D4ED8` | `brand-dark`, `brand-700` | Hover on brand; text on brand tints |
| Brand 800 | `#1E40AF` | `brand-800` | Strong text on brand tints |
| Brand light | `#DBEAFE` | `brand-light`, `brand-100` | Selected-state fills |
| Brand 50 | `#EFF6FF` | `brand-50` | Icon chips, active filter chip |
| Canvas | `#F9FAFB` | `gray-50` | Page background, table headers |
| Surface | `#FFFFFF` | `white` | Cards, modals, inputs |
| Hairline | `#E5E7EB` | `gray-200` | Card borders, dividers |
| Field border | `#868E9C` | `field` | Input/select/textarea borders (3.30:1) |

### Neutral text scale

Only these four grays carry text. `gray-400` and lighter are never text; they are
decorative icon and border colors only.

| Role | Tailwind | On white | On gray-50 | On gray-100 |
|---|---|---|---|---|
| Ink (headings, figures) | `gray-900` | 17.74 | ✓ | ✓ |
| Body | `gray-700` | 10.31 | ✓ | 9.37 |
| Secondary (labels) | `gray-600` | 7.56 | ✓ | 6.87 |
| Caption (help, meta) | `gray-500` | 4.83 | 4.63 | **4.39 ✗** |

**Rule:** `gray-500` text only on white or `gray-50`. On `gray-100` (neutral
badges, disabled fields) use `gray-600` or darker. This is the same class of
bug as the `text-gray-400` captions fixed earlier: don't reintroduce it.

### Semantic colors

Reused from conventions already established in the app. The Alerts severity
colors match `severityStyles` on the Alerts page. Amber for "pending / needs
attention" matches the EC Board convention.

| Meaning | Tint (badge / callout bg) | Text on tint | Ratio | Solid (fills, meters) |
|---|---|---|---|---|
| Success / active / all clear | `green-50` | `green-800` | 6.81 | `#15803D` |
| Warning / pending / monitoring / near full | `amber-50` | `amber-800` | 6.84 | `#EDA100` |
| Danger / mandatory / at risk / failed | `red-50` | `red-700` | 5.91 | `#DC2626` |
| Info | `blue-50` | `blue-700` | 6.16 | `#2563EB` |
| Advisory (alert severity only) | `orange-50` | `orange-800` | 6.88 | `#EA580C` |
| Neutral / closed / standby / no data | `gray-100` | `gray-700` | 9.37 | `#9CA3AF` |

Text colors that are **not allowed** on white, because each failed contrast in
the audit: `red-500` (3.76), `green-600` (3.30), `amber-600` (3.19),
`orange-600` (3.35), `text-brand` on `brand-100` (4.24). Use the `-700` or
`-800` step instead: `red-700` 6.47, `green-700` 5.02, `amber-700` 5.02.

Status is never color alone. Every status color ships with a text label, and
with an icon where the label is short.

### Chart palette

Validated with the dataviz skill's `validate_palette.js` (OKLab ΔE; CVD target
8 or more, normal-vision floor 15).

- **Status series** (occupancy buckets, SMS delivery, event status):
  `#15803D` success, `#EDA100` warning, `#DC2626` danger, `#9CA3AF`
  neutral/no data. All-pairs: worst CVD ΔE 8.6, worst normal-vision ΔE 24.7.
  Amber is below 3:1 against the surface, so every status chart keeps its
  labelled legend with counts next to it.
- **Categorical series**, in this fixed order and never cycled: `#2563EB` blue,
  `#EB6834` orange, `#1BAF7A` aqua, `#EDA100` yellow, `#4A3AA7` violet.
  Adjacent pairs pass (worst CVD ΔE 9.1). The first three pass all-pairs
  (worst ΔE 9.2).
- **Single-series bars and meters:** brand `#2563EB` on a `gray-100` track.
- **Sex split:** male `#2563EB`, female `#DB2777` (CVD ΔE 18.7).
- **Grid lines** `#E5E7EB`, axis text `gray-600`. Values and legends always use
  text tokens, never the series color.

### Avatars

Initials on a colored circle, white text. The previous set had four failing
colors. The replacements all pass: `#1D4ED8` 6.70, `#15803D` 5.02, `#B45309`
5.02, `#BE185D` 6.04, `#6D28D9` 7.10, `#0E7490` 5.36.

## 2. Typography

**Typeface:** Public Sans (400/500/600/700), loaded from Google Fonts with
`display=swap`, falling back to the system UI stack. It is the open-source face
built for government interfaces: neutral, very legible at small sizes, with
real tabular figures.

| Role | Class | Size / line height | Weight | Color |
|---|---|---|---|---|
| Page title (h1) | `.page-title` | 22 / 28 px | 600 | `gray-900` |
| Page description | `.page-subtitle` | 14 / 20 | 400 | `gray-600` |
| Section / card title | `.card-title` | 14 / 20 | 600 | `gray-900` |
| Modal title | `.modal-title` | 16 / 24 | 600 | `gray-900` |
| Stat figure | `.stat-value` | 24 / 32 | 600 | `gray-900` |
| Body | (default) | 14 / 20 | 400 | `gray-700` |
| Form label | `.label` | 14 / 20 | 500 | `gray-700` |
| Compact label | `.label-sm` | 12 / 16 | 500 | `gray-600` |
| Stat label, table header | `.stat-label`, `th` | 12 / 16 | 500 / 600 | `gray-600` |
| Caption, help, meta | `.help` | 12 / 16 | 400 | `gray-500` (white/gray-50 only) |

Rules:

- **Sentence case everywhere.** No `uppercase tracking-wide` labels and no
  all-caps status words. The badge text is the label.
- **No italics** for captions. Small italic gray text is the hardest thing on
  the page to read.
- **Tabular figures** (`tabular-nums`) for numbers in columns: tables, the EC
  Board strip, right-aligned counts. Standalone stat figures keep proportional
  figures.
- Minimum text size is 12px. The one exception is the unread-count bubble on
  the notification bell: 11px semibold, white on `red-600` (4.83:1).

## 3. Spacing

A 4px base, using only these steps (Tailwind units in brackets):

| Step | px | Use |
|---|---|---|
| 1 | 4 | Label to helper text, tight icon gaps |
| 2 | 8 | Button groups, badge groups, icon to label |
| 3 | 12 | Card title to content, list-item gaps |
| 4 | 16 | Card padding, gaps between cards, form-field gaps |
| 5 | 20 | Modal padding (header, body, footer) |
| 6 | 24 | Page padding (≥ 640px), page header to content, main/aside gutter |

Page skeleton:

```
main (p-4 sm:p-6, gray-50)
├── .page-header (mb-6)      title + description ............ [primary action]
├── .stat-strip (mb-6)       ruled row of 3–5 figures, one card
└── grid lg:grid-cols-3 gap-6
    ├── lg:col-span-2        filter row (mb-4) → list or table
    └── aside                summary cards, flex-col gap-4
```

Everything is left-aligned except numeric table columns, which are
right-aligned.

## 4. Components

All classes live in the layout's `@layer components`, so a utility on the same
element still wins (`btn btn-primary w-full` works). None of these classes set
`display` where a page toggles `hidden`/`flex` itself: modals, empty states,
callouts.

### Buttons

Base `.btn`: 38px tall, `rounded-lg`, 14px medium. Add `.btn-sm` for 28px
in-row actions (12px text).

| Class | Look | When |
|---|---|---|
| `.btn-primary` | Solid brand, white text | The page's or modal's one main action: Create event, Save, Send alert, Generate |
| `.btn-secondary` | White, `gray-300` border, `gray-700` text | Cancel, Back, Export, Edit, Reset view, anything that isn't the main action |
| `.btn-neutral` | Solid `gray-800` | A committing action that must not compete with the page's primary (EC Board's *Mark as departed*, *Check out*) |
| `.btn-danger` | Solid `red-600` | Send emergency alert, and the final confirm inside a delete dialog |
| `.btn-danger-secondary` | White, `red-200` border, `red-700` text | Destructive or closing actions in lists: Close event, Deactivate, Remove, Delete |
| `.btn-attention` | `amber-50` fill, amber border | "Add details" on a pending (placeholder) record |
| `.btn-ghost` | No border, gray text | Low-emphasis toolbar actions |
| `.btn-icon` | 32px square, gray icon | Modal close, per-row icon actions. Always has an `aria-label`. |

Text links: `.link` (brand, underline on hover) and `.link-danger` (`red-700`).

Buttons whose click handler reads `e.target.classList` (member rows, center
cards, report Generate buttons) must contain **text only**, with no icon child,
or the handler sees the icon instead of the button.

### Cards

- `.card`: white, `gray-200` hairline border, `rounded-xl` (12px), no shadow.
  Padding is `p-4`, or `p-5` for a hero card.
- `.card-header` (flex row, `mb-3`) holds a `.card-title` and an optional
  `.link` such as "View all".
- `.icon-chip`: a 36px `brand-50` square with a `brand-700` icon. It is the only
  icon-chip style. No purple, orange, teal or pink chips.
- Only overlays get a shadow (dropdowns, modals). Radius follows hierarchy:
  cards 12px, controls 8px, badges 6px.

### Stat strip (KPI row)

The EC Board figure strip, generalized. One bordered card, cells divided by
hairlines, wrapping cleanly at any column count.

```html
<div class="stat-strip grid-cols-2 lg:grid-cols-4 mb-6">
  <div class="stat">
    <p class="stat-label">Total evacuees</p>
    <p class="stat-value">54</p>
    <p class="stat-note">Currently displaced, active events</p>
  </div>
  …
</div>
```

When a figure is in an alert state (for example centers at risk > 0), add
`.stat-alert` to the `.stat` cell. The label gains a red dot and the value
turns `red-700`. At 0 it stays calm and ink-colored.

### Tables

`.data-table` on the `<table>`:

- **Header:** `gray-50` fill, 12px semibold `gray-600`, sentence case, `gray-200`
  rule below.
- **Rows:** 14px `gray-700`, `gray-100` rule between rows. `tr.row-link` adds a
  hover fill and a pointer cursor for clickable rows.
- **Numbers:** `.num` on both `th` and `td` right-aligns and uses tabular
  figures.
- **Total / summary row:** `<tfoot>` rows, or `tr.row-total`. They get a 2px
  `gray-300` rule above plus bold `gray-900`, like the printed DSWD board's
  total line. EC Board's age and sex table is the live example.
- **Footer meta** ("Showing 12 of 40") uses `.table-meta`.

### Form inputs

- `.input` on every `input`, `select` and `textarea`. It has a white fill, a
  3:1 `field` border, `rounded-lg`, 14px text, and a `gray-500` placeholder
  (4.83:1).
  - Focus: brand border plus a 2px brand ring at 25% opacity.
  - Disabled: `gray-100` fill, `gray-600` text.
- `.input-sm` is the compact version for dense panels (EC Board, GIS filters).
- Each field has a `.label` above it (`.label-sm` in dense panels) and optional
  `.help` below.
- Checkboxes and radios use `accent-color: brand`.
- Errors go in a `.callout .callout-danger` box above the form (see
  `showFormErrors()` in `public/js/api.js`).

### Badges (status pills)

`.badge` plus one tone: `.badge-success`, `.badge-warning`, `.badge-danger`,
`.badge-info`, `.badge-advisory`, `.badge-neutral`. All are 12px medium,
`rounded-md`, with a tinted fill and a faint inset ring.

| Domain | Mapping |
|---|---|
| Event status | active → success · monitoring → warning · closed → neutral |
| Center status | active → success · on standby → neutral · full → danger · closed → neutral (matches the GIS map legend and the ≥ 90% at-risk rule) |
| Alert severity | mandatory → danger · advisory → advisory · info → info · all clear → success |
| User status | active → success · inactive → neutral · suspended → danger |
| Records | details pending → warning · legacy bulk entry → danger · sectoral tags (4Ps, PWD, senior…) → neutral |
| Roles (Users page only) | CSWD personnel → info · barangay official → advisory · administrator → teal |

Sectoral tags are categories, not states, so they are neutral. That keeps the
amber *pending* tag the only thing in the Evacuees table that asks for
attention.

### Callouts

`.callout` (block, bordered, `rounded-lg`, 14px) plus `.callout-danger`,
`.callout-warning`, `.callout-info` or `.callout-success`. Add
`flex items-start gap-2` on the element when it leads with an icon.

### Filter chips (tabs)

`.chip` for each option. `.chip-active` marks the selected one with a brand
border, `brand-50` fill and `brand-700` text (6.16:1), and the markup carries
`aria-pressed`.

### Modals

- `.modal-backdrop`: fixed, `gray-900/50`. It sets no display; pages toggle
  `hidden`/`flex`.
- `.modal`: white, `rounded-xl`, scrolls at 90vh. Pair it with a `max-w-*`.
- `.modal-header` holds a `.modal-title`, a `.help` line and a `.btn-icon`
  close button.
- The body is `p-5`. `.modal-footer` puts Cancel (secondary) left of the
  primary.

### Meters

`.meter` (6px `gray-100` track) wraps a `.meter-fill` whose background carries
the state color: green below 75%, amber from 75%, red at 90% and above. That
matches the dashboard's at-risk rule.

### Empty states

A 40px `gray-300` decorative icon, a 14px `gray-700` medium line, a 14px
`gray-500` explanation, and optionally a secondary action. Keep them inside a
`.card`.

### Sidebar navigation

- `.nav-link` rows: 40px tall, 18px icon, 14px medium `#C7D7F0` text on navy
  (7.63:1).
- Hover: `white/10` fill.
- Active: `white/15` fill, white semibold text and a 3px `blue-300` bar on the
  left edge, so the active item reads by shape as well as by fill.
- The illustrated art is faded out behind the link list, so link text always
  sits on solid navy.

## Checking your work

- Contrast: check new pairings against the tables above, or with the browser
  devtools contrast checker or axe. The rule of thumb: `-700`/`-800` text on
  `-50` tints, and `gray-500` or darker on white.
- Buttons: one `.btn-primary` per view (per card, where a card is its own
  independent task such as the Reports generators).
- New status colors: extend the badge table above first, then use it.
