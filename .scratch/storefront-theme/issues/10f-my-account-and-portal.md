# My Account, login and the Plans Portal on the dark design

Type: task
Status: ready-for-agent
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

- [ ] Logged in as that customer, screenshot at 390px and 1440px: `/my-account/`, `/my-account/orders/`, `/my-account/downloads/`, `/my-account/plans/`, one plan and one workout. Also screenshot logged out: `/my-account/` (login and register) and `/my-account/lost-password/`.
- [ ] The Portal is legible on the dark background (ticket 10's third criterion). Body text, buttons, links and status colours all meet WCAG AA contrast; list the ratios in the Answer.
- [ ] Logging a set on the workout screen still works. `portal.js` is untouched.
- [ ] Lint and PHPStan pass; `10f.json` parses if it exists.
