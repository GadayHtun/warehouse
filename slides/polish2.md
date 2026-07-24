---
marp: true
---

# UI/UX Polish
### Responsive Design & Visual Refinement

A polished, mobile-ready inventory platform — built with Blade + Tailwind CSS, zero frontend build step.

---

## Design System

**Custom brand palette** extends Tailwind's defaults for consistent, meaningful color across every screen.

| Token | Hex | Usage |
|---|---|---|
| `brand-50` | `#eff6ff` | Light backgrounds, focus rings |
| `brand-500` | `#3b82f6` | Primary actions, active nav |
| `brand-600` | `#2563eb` | Hover states |
| `brand-700` | `#1d4ed8` | Pressed / active |

**Semantic color system** — every color communicates status:

| Color | Meaning | Badge Style |
|---|---|---|
| 🟢 Green | Success, stock-in, positive variance | `bg-green-100 text-green-700` |
| 🔴 Red | Error, critical, stock-out, negative variance | `bg-red-100 text-red-700` |
| 🟡 Yellow | Warning, pending review | `bg-yellow-100 text-yellow-700` |
| 🔵 Blue | Info, in-progress, informational findings | `bg-blue-100 text-blue-700` |
| 🟣 Purple | Under review, recount flagged | `bg-purple-100 text-purple-700` |
| 🟠 Orange | Low stock, large variance alerts | `bg-orange-100 text-orange-700` |
| ⚪ Gray | Neutral, draft, dismissed | `bg-gray-100 text-gray-600` |

---

## Typography System

**Text hierarchy** — consistent size, weight, and color across all screens.

| Level | Class | Usage |
|---|---|---|
| Page title | `text-2xl font-bold text-gray-900` | Every page heading |
| Section title | `text-lg font-semibold text-gray-700` | Sub-sections (e.g. "Recently Closed") |
| Card metric | `text-3xl font-bold` | Dashboard numbers, review summary |
| Label | `text-sm font-medium text-gray-700` | Form labels, table cell labels |
| Body | `text-sm` | Table cells, form inputs |
| Header | `text-xs text-gray-500 uppercase` | Table column headers |
| Helper | `text-xs text-gray-400` | Timestamps, empty states, hints |

**Monospace** — `font-mono text-xs` for SKUs, IDs, and numeric variance values.

---

## Spacing System

**Consistent rhythm** — every dimension follows a predictable scale.

| Level | Value | Usage |
|---|---|---|
| Page gap | `space-y-6` / `py-6` | Between major sections |
| Card padding | `p-4` (compact) / `p-6` (forms) | Content cards, form containers |
| Table cells | `px-4 py-2` (body) / `px-4 py-3` (header) | All data tables |
| Grid gap | `gap-4` (metrics) / `gap-3` (compact) / `gap-2` (buttons) | Grid layouts, button groups |
| Inline gap | `gap-1` / `gap-2` | Badge groups, action buttons |

---

## Responsive Layout

**Viewport-first** — `<meta name="viewport" content="width=device-width, initial-scale=1">` ensures correct rendering on all devices.

**Tailwind breakpoints** adapt every screen:

| Element | Mobile | Tablet | Desktop |
|---|---|---|---|
| Main container | `px-4` | `px-6` (`sm:`) | `px-8` (`lg:`) |
| Dashboard metrics | 1 column | 2 columns | 4 columns (`md:grid-cols-4`) |
| Reconciliation summary | 2 columns | 3 columns | 6 columns (`md:grid-cols-6`) |
| Reconciliation show | 2 columns | 4 columns | 4 columns (`md:grid-cols-4`) |
| Variance by direction | 1 column | 2 columns | 2 columns (`md:grid-cols-2`) |

**Max width container** — `max-w-7xl mx-auto` prevents content from stretching on ultrawide displays.

---

## Navigation

**Top bar** with brand, nav links, and user info — all in a single row.

```
┌──────────────────────────────────────────────────────────────┐
│  📦 Warehouse   Dashboard │ Stock │ Recon │ Findings   Admin │
│                                     🔴3              Logout  │
└──────────────────────────────────────────────────────────────┘
```

**Active state** — underline indicator (`border-b-2 border-brand-500`) on current page via `request()->routeIs()`.

**Role-based visibility** — Reconciliation and Findings links hidden from Agent role.

**Critical badge** — red pill with live count on Findings nav item when critical findings exist.

**User context** — name and role displayed in the top-right corner.

---

## Flash Messages

Session-based feedback for every user action — stacked above content.

| Type | Style | Trigger |
|---|---|---|
| ✅ Success | `bg-green-50 border-green-200 text-green-700` | Successful create/update/delete |
| ⚠️ Warning | `bg-yellow-50 border-yellow-200 text-yellow-700` | Validation warnings |
| ❌ Error | `bg-red-50 border-red-200 text-red-700` | Failed operations, auth errors |

**Consistent pattern** — `mb-4 p-3 rounded-lg text-sm` across all three types.

---

## Form Design

**Centered single-column layout** — `max-w-2xl mx-auto` for stock forms, `max-w-xl mx-auto` for reconciliation create.

**Every form follows the same structure:**

1. **Label** — `block text-sm font-medium text-gray-700 mb-1`
2. **Input** — `w-full px-3 py-2 border border-gray-300 rounded-lg`
3. **Focus ring** — `focus:ring-2 focus:ring-brand-500 focus:border-brand-500`
4. **Error state** — `@error` directive adds `border-red-500` to the input
5. **Error message** — `mt-1 text-sm text-red-600` below the field

**Input constraints** — `min`, `max`, `step`, `maxlength`, `required` enforced at HTML level.

**Input repopulation** — `old()` helper on every field preserves values after validation failure.

---

## Tables

**Consistent structure** across all 6 data views:

```
┌─────────────────────────────────────────────────┐
│  Table Header  (bg-gray-50, uppercase, xs)      │
├─────────────────────────────────────────────────┤
│  Row 1        (hover:bg-gray-50)                │
│  ─────────────────────────────────────────────  │
│  Row 2        (hover:bg-gray-50)                │
│  ─────────────────────────────────────────────  │
│  Row 3        (hover:bg-gray-50)                │
└─────────────────────────────────────────────────┘
```

**Design details:**
- `divide-y divide-gray-100` — subtle row separators
- `hover:bg-gray-50` — row highlight on hover
- `font-mono text-xs` — monospace for SKUs, IDs, and numeric values
- `text-right` — right-aligned numbers and currency
- `colspan` — full-width empty state cells

**Empty state** — centered message with `py-8` or `py-12` padding and a CTA link.

---

## Status Badges

**Color-coded pills** — `@switch` directive for consistent rendering across reconciliation and findings.

| Reconciliation Status | Style |
|---|---|
| `draft` | `bg-gray-100 text-gray-600` |
| `in_progress` | `bg-blue-100 text-blue-700` |
| `submitted` | `bg-yellow-100 text-yellow-700` |
| `under_review` | `bg-purple-100 text-purple-700` |
| `closed` | `bg-green-100 text-green-700` |

| Finding Severity | Style |
|---|---|
| Critical | `bg-red-100 text-red-700` |
| Warning | `bg-yellow-100 text-yellow-700` |
| Info | `bg-blue-100 text-blue-700` |

**Rounded-full** for severity badges, **rounded** for status — visual distinction between the two systems.

---

## Dashboard Metrics

**Four key widgets** in a `grid grid-cols-1 md:grid-cols-4 gap-4` — immediate operational awareness.

```
┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐
│ Pending  │  │ Agent    │  │ Variance │  │ Low      │
│ Recon    │  │ Findings │  │ 30-Day   │  │ Stock    │
│          │  │          │  │          │  │          │
│    2     │  │  3 crit  │  │  14.2u   │  │    5     │
│ sessions │  │ 12 total │  │ net      │  │ items    │
│          │  │ 5 warn   │  │ +2 over  │  │          │
│          │  │ 4 info   │  │ -3 under │  │          │
└──────────┘  └──────────┘  └──────────┘  └──────────┘
```

**Color-coded values** — red for critical/negative, orange for low stock, green for positive variance.

**Severity breakdown** — `text-xs` pills below findings metric: `bg-red-100`, `bg-yellow-100`, `bg-blue-100`.

---

## Filter Chips (Agent Findings)

**Pill-shaped filter buttons** with active/inactive states and per-severity coloring.

```
[Open (15)]  [Critical (3)]  [Warning (5)]  [Info (7)]  [Acknowledged (2)]  [Dismissed (1)]
```

**Active state** — filled background matching severity color (e.g. `bg-red-600 text-white` for critical).

**Inactive state** — white background with colored border and text (e.g. `bg-white border-red-200 text-red-700`).

**URL-driven** — `?status=` and `?severity=` query params, server-side filtering.

---

## Toggle / Inline Expand

**Vanilla JS toggle** for dismiss and reject forms — no framework needed.

```html
<button onclick="document.getElementById('dismiss-{id}').classList.toggle('hidden')">
  Dismiss
</button>
<form id="dismiss-{id}" class="hidden mt-1">
  <input name="review_note" placeholder="Reason (min 10 chars)" minlength="10" required>
  <button>Confirm Dismiss</button>
</form>
```

**Used in 2 places:**
- `agent-findings/index.blade.php` — dismiss finding with reason
- `reconciliation/review.blade.php` — reject large variance with reason

---

## Reconciliation Review

**Summary cards** — `grid grid-cols-2 md:grid-cols-6 gap-3` with key metrics.

| Card | Value |
|---|---|
| Total Lines | `text-2xl font-bold` |
| Pending | `text-yellow-600` |
| Resolved | `text-green-600` |
| Net $ Impact | Conditional green/red |
| Large Variances | Plain bold |
| Awaiting Approval | `text-orange-600` |

**Investigation priority** — lines sorted by dollar impact, largest first.

**Inline resolution** — accept/recount/defer with reason field, directly in the table row.

**Large-variance guard** — approve/reject buttons with confirmation reason, inline in the table.

---

## Login Page

**Centered card layout** — `min-h-[80vh] flex items-center justify-center`.

**Clean form** — email, password, remember me, sign in button.

**Focus states** — `focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition` on the submit button.

**Error display** — `@error` directive shows field-level validation messages with `border-red-500`.

**Brand presence** — 📦 Warehouse logo centered at the top of the card.

---

## Empty States

**Consistent pattern** across all list views with no data.

| View | Style | Message |
|---|---|---|
| Reconciliation | `border-dashed p-12 text-center` | "No active reconciliation sessions" + CTA link |
| Agent Findings | `px-4 py-12 text-center` | "No findings match these filters" |
| Stock | `px-4 py-8 text-center` | "No products found. Run seeders first." |
| Dashboard Activity | `p-4 text-sm` | "No recent stock movements." |

**Design rule** — centered text, muted color (`text-gray-400`), optional action link.

---

## Color-Coded Actions

**Stock movements** — green for IN, red for OUT, with pill badges.

```
🟢 IN   200   Main Warehouse   2m ago
🔴 OUT   15   Downtown Store   5m ago
```

**Variance display** — green for overage (`+`), red for shortage (`-`).

**Low stock alerts** — orange border + background on products below reorder point.

---

## Accessibility

**What exists:**

| Feature | Status |
|---|---|
| `<html lang="en">` | ✅ Present |
| Semantic HTML (`<nav>`, `<main>`, `<table>`) | ✅ Used throughout |
| Form labels (`for`/`id` pairs) | ✅ Every input labeled |
| Focus ring on login button | ✅ `focus:ring-2 focus:ring-brand-500` |
| `autofocus` on login email | ✅ Present |
| `colspan` for empty state cells | ✅ Used in all tables |

**What's next:**
- `aria-*` attributes for screen readers
- `aria-live` regions for flash messages
- `focus-visible` rings on all interactive elements
- Skip-to-content link
- `overflow-x-auto` wrapper on tables for mobile

---

## Summary

| Aspect | Implementation |
|---|---|
| **Framework** | Blade + Tailwind CSS (CDN, zero build step) |
| **Typography** | 7-level hierarchy, monospace for codes |
| **Spacing** | Consistent `space-y-6`, `p-4`/`p-6`, `gap-2`/`3`/`4` |
| **Responsive** | Mobile-first breakpoints (`sm:`, `md:`, `lg:`) |
| **Color system** | Custom brand palette + 7 semantic status colors |
| **Forms** | Consistent layout, validation, error states, repopulation |
| **Tables** | Hover states, dividers, monospace, empty states |
| **Badges** | Color-coded pills for status and severity |
| **Navigation** | Active states, role-based visibility, critical badge |
| **Flash messages** | Success/warning/error session feedback |
| **Filters** | URL-driven pill chips with active/inactive states |
| **Toggles** | Vanilla JS inline expand for dismiss/reject forms |
| **Empty states** | Centered message + CTA link pattern |
| **Accessibility** | Semantic HTML, labels, focus ring (login), lang attr |

> A polished, professional UI — built entirely with server-rendered Blade and utility-first Tailwind CSS. No JavaScript framework. No build step. Just clean, consistent design with a clear path forward for mobile and accessibility improvements.
