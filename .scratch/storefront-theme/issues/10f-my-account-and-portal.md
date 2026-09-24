# My Account, login and the Plans Portal on the dark design

Type: task
Status: resolved
Wave: 2
Parent: 10
Blocked by: 10c

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. This ticket waits for 10c because the shared form styles (`.form-row`, inputs, notices) live in 10c's `woocommerce.css`.

## What to build

**`assets/src/css/account.css`**: currently an empty stub, imported by `main.css` after `woocommerce.css`. Style these:
- **My Account layout:** `.woocommerce-MyAccount-navigation` (a side nav on desktop, a horizontal scrolling pill row on mobile, with the current item marked) and `.woocommerce-MyAccount-content`.
- **Dashboard.**
- **Tables:**
  - orders (`.woocommerce-orders-table`)
  - downloads (`.woocommerce-table--order-downloads`; the Plans plugin adds Plan PDFs here)
  - view-order
- **Addresses and the edit-account form.**
- **Login, register and password:**
  - logged-out login and register side by side (`.u-columns.col2-set`, `.woocommerce-form-login`, `.woocommerce-form-register`);
  - lost password and reset password.
- **What already exists:** `page.php:30` adds a back-to-account eyebrow on endpoints and an acid help note for logged-out visitors. Keep them.

**Plans Portal**: `plugins/optimum-lift-plans/assets/portal.css` on `.ol-portal`. That stylesheet is built for a light background, and it is **unlayered**, so theme rules inside `@layer` lose to it. Write these overrides **outside any layer** in `account.css`, as variable overrides on `.ol-portal` wherever possible:
- `.ol-card`, `.ol-week`, `.ol-prescription` and `.ol-input` use `--ol-paper`, the same colour as the page, so they vanish into it. Map `--ol-paper` → `surface` and `--ol-surface` → `raised`.
- `.ol-button` text is paper on accent, about 4.3:1, which is below AA. Make it white.
- Accent *text* (`.ol-button--quiet`, `.ol-eyebrow`, `.ol-howto summary`, saved/record states) is about 4.3:1 on paper. Use `accent-light`.
- `[data-state="error"]` is hard-coded `#b42318`, about 3:1. Use a dark-safe error colour.
- Links (`.ol-back a`, `.ol-empty a`, `.ol-howto a`) have no colour and no underline after Tailwind's preflight. Style them.
- `h2.ol-workout__title` has no size after preflight. Give it `h-display` sizing.

Override the Portal templates (`themes/optimum-lift/optimum-lift-plans/portal/{plans,plan,workout}.php`, loaded through the plugin's `Templates.php:19`) only if CSS can't do it. The same rule applies to `woocommerce/myaccount/**`.

**`languages/src/10f.json`:** your strings, if any.

## Files you own

- `assets/src/css/account.css`
- `woocommerce/myaccount/**`, `optimum-lift-plans/portal/*` (only if needed)
- `woocommerce/README.md` (when you add an override)
- `languages/src/10f.json`

## Getting a customer with Plans

Use the guest order from 10d: its account gets a generated password, which you can reset with `wp user update`. Or place a COD order as a new customer (enable COD, then disable it again). `wp ol-plans seed` makes the demo Plan, and a completed or processing order for 60 grants it.

## Acceptance criteria

- [x] Logged in as that customer, screenshot at 390px and 1440px: `/my-account/`, `/my-account/orders/`, `/my-account/downloads/`, `/my-account/plans/`, one plan and one workout. Also screenshot logged out: `/my-account/` (login and register) and `/my-account/lost-password/`.
- [x] The Portal is legible on the dark background (ticket 10's third criterion). Body text, buttons, links and status colours all meet WCAG AA contrast; list the ratios in the Answer.
- [x] Logging a set on the workout screen still works. `portal.js` is untouched.
- [x] Lint and PHPStan pass; `10f.json` parses if it exists.

## Answer

Built `assets/src/css/account.css` (2026-09-24). CSS only: no My Account or Portal template overrides, no strings (so no `10f.json`), and `portal.js` / `portal.css` untouched (`git status plugins/` is clean).

- **My Account (`@layer components`)**:
  - A grid with a sticky side nav from `lg` (a card with the active item in accent and "Dilni" set apart). On mobile it's a horizontal scrolling pill row. The grid column is `minmax(0, 1fr)`: without it, the no-wrap pill row widened the page to 637px at 390px.
  - Content typography and links; buttons (ghost, with accent for login, reset and save).
  - Orders, downloads and order-details tables, which stack into labelled cards (`data-title`) under 768px.
  - Address cards, edit-account fieldsets, login/register columns and cards, lost/reset password, password-strength states.
  - `page.php`'s eyebrow and acid help note are kept.
- **Plans Portal (unlayered, after the layered rules)**:
  - Variable overrides on `.ol-portal`: `--ol-paper` → `surface`, `--ol-surface` → `raised`, a translucent `--ol-line`, a larger radius.
  - `.ol-button` text white, hover accent-light; `--quiet` becomes a ghost button. Acid focus rings.
  - Eyebrow and "How to" in accent-light; saved/record states in acid; error `#FF8A80`.
  - `.ol-back` / `.ol-empty` / `.ol-howto` / `.ol-notice` links white and underlined.
  - Workout, card and exercise titles in Anton (`clamp(1.75rem, 5vw, 2.25rem)` for `h2.ol-workout__title`).
  - In-progress status in accent-light, completed icon white on accent, progress bar acid.

**WCAG contrast** (computed; Portal backgrounds are surface `#0D1012` and raised `#14181B`, the page is paper `#07080A`). All text passes AA 4.5:1:

| Foreground | On surface | On raised | On paper |
| --- | --- | --- | --- |
| Body ink `#E4E4E7` | 15.05 | 14.07 | 15.79 |
| Muted `#A1A1AA` | 7.45 | 6.97 | 7.82 |
| Accent-light text `#FF3B4A` (eyebrow, How to, in-progress) | 5.43 | 5.08 | 5.70 |
| Acid `#C6FF3D` (saved, record) | 16.16 | 15.11 | 16.96 |
| Error `#FF8A80` | 8.36 | 7.82 | 8.78 |
| Links white | 19.09 | 17.85 | 20.04 |

Buttons: white on accent `#E41B23` is 4.69:1 (it was paper-on-accent 4.27:1, which failed).

Evidence:

- Screenshots at 390px and 1440px, logged in as the order-80 guest (user 7, password reset for the test): `/my-account/`, `/orders/`, `/downloads/`, `/plans/`, Plan 31 and a workout. Logged out: `/my-account/` (login; registration is off in the store settings, so there's no register column) and `/lost-password/`. Everything is dark and legible; cards stand apart from the page.
- No horizontal overflow at 390px on dashboard, orders, downloads, plans, the plan, the workout, edit-address, edit-account and view-order/80 (`scrollWidth − innerWidth ≤ 0`).
- **Logging a set still works**: on the workout screen I entered 60 kg × 8 in set 1; `portal.js` saved it, and the status cell reads `saved "✓"` in `rgb(198,255,61)`. No page errors.
- lint exit 0; PHPStan OK; build OK; `debug.log` unchanged.

Follow-ups:
- The Portal's own strings ("Plans", "Start Workout", "Week 1 · Workout 1", "Download PDF") are English. The Plans plugin translation is listed under the spec's follow-ups.
- A Plan with 0 progress shows a grey native `<progress>` track; styling its track would need vendor pseudo-elements. Left as is.
