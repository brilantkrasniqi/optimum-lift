# Review and fix: accessibility and speed

Type: task
Status: resolved
Wave: 3
Parent: 11
Blocked by: 11d

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Run the 11 sub-tickets one at a time. This is the last one; resolve parent 11 when you are done.

## What to do

- **Keyboard paths** (Playwright, keyboard only):
  - skip link;
  - header nav, then the mobile menu: open, trap, Escape, restore focus;
  - the drawer, with the same checks as the menu;
  - the goal tabs (arrows, Home/End);
  - the FAQ accordion (no focus inside a collapsed answer);
  - the exit modal (trap, Escape);
  - gallery thumbnails;
  - checkout.

  Focus must stay visible everywhere (the acid outline).
- **Screen-reader semantics:**
  - dialog roles and labels (menu, drawer, exit modal);
  - `aria-expanded` and `aria-controls`;
  - `inert` behind open dialogs;
  - live region for drawer notices;
  - headings in order on every page.
- **Contrast on dark:** body text, muted text, buttons (especially white on accent), acid on dark, links, form errors, and the Portal.
- **Layout shift:**
  - fonts: Anton preloaded, `font-display`;
  - the urgency bar and the sticky bars;
  - images with width and height;
  - measure CLS with Playwright `PerformanceObserver` on `/`, 60 and `/shop/` at 390px.
- **Weight:**
  - `assets/dist/main.css` and `main.js` sizes after `npm run build`;
  - no WooCommerce stylesheet or jQuery on non-store pages;
  - no Google Fonts or other third-party request;
  - Splide only where it's used.

Verify each finding before fixing it.

## Acceptance criteria

- [x] The Answer has a findings table and the measured numbers: CLS per page, CSS/JS bytes (raw and gzip), and requests per page type.
- [x] Every verified finding is fixed or deferred with a reason.
- [x] Build, lint and PHPStan pass.
- [x] Parent 11 is resolved.

## Answer

Reviewed 2026-09-24. Scripts are in `C:\Users\Work\.claude\jobs\dd052601\tmp\pw\w11\`:

- `kb.mjs`: keyboard only; skip link, header, menu, drawer, tabs, FAQ, exit modal.
- `gal.mjs`: gallery thumbnails.
- `kbco.mjs`, `coerr.mjs`: checkout.
- `headings.mjs`
- `contrast.mjs`: every text node's colour against its composited background, through a canvas, so `oklch()` colours resolve.
- `cls.mjs`, `clscheck.mjs`: CLS.
- `requests.mjs`

### Findings

| Area | Finding | Verified | Fix |
| --- | --- | --- | --- |
| Mobile menu | Open, `main` and `header` were **not** `inert` (the drawer and exit modal already were), so a screen reader's virtual cursor could leave the dialog | Yes: `inertMain:false` while open | `menu.js` makes the other `body` children inert on open and restores only those on close, like `cart.js`. After: `inertMain:true`, `inertHeader:true`; Escape restores both and returns focus to "Open menu" |
| Skip link | Enter moved the view but left focus on `<body>`, because `<main>` couldn't take focus | Yes | `<main id="main" tabindex="-1">` in all nine templates (`[tabindex="-1"]` already has no outline). After: focus on `MAIN#main`; the next Tab reaches the hero CTA |
| Checkout focus order | On phones the order summary shows first (grid area), but it came after the form in the DOM, so keyboard users reached it after "Place order" | Yes (`kbco.mjs`) | `<details class="ol-checkout-summary">` moved before the fields in `woocommerce/checkout/form-checkout.php`; the grid areas keep both layouts (positions measured unchanged at 390 and 1440). After: summary → email → names → country → Place order |
| Checkout headings | `h1` "Checkout" → `h3` "Your details", "Payment", "Your order", skipping a level | Yes (`headings.mjs`) | "Your order" (`form-checkout.php`) and "Payment" (`checkout.php`) are `h2`. WooCommerce's billing `h3` comes from its template, hence a new override, **`woocommerce/checkout/form-billing.php`** (the original with the heading as `h2`, every hook kept). CSS selectors accept `h2`. After: no heading skips on any of 14 pages |
| Contrast: muted text | Tailwind's `zinc-500` (#71717A) is 4.15 on paper, 3.95 on surface, 3.69 on raised and 3.54 on `white/5`. That's under AA for the small text using it: 100 uses (card categories, stat labels, notes, anchors) | Yes: 84 failing elements on `/`, 65–67 on Products | `--color-zinc-500: #8a8a93` in `main.css` `@theme`: 5.86 paper, 5.58 surface, 5.22 raised, 5.01 `white/5`, still a step below zinc-400 (7.8) |
| Contrast: dim text | `zinc-600` text (#52525B, 2.3–2.6): copyright, SSL line, "not included" line, versions note, breadcrumb, the drawer's "Remove" and its reassurance line, the "optional" field label | Yes | Moved to `zinc-500`. The `aria-hidden` separators (`·`, `/`), icons, gradients and underlines keep `zinc-600` |
| Contrast: urgency bar | "Ends in" in `white/90` on accent: 4.01 | Yes | Full white: 4.69 |
| Contrast: after | — | `contrast.mjs` on `/`, 60, 62, `/shop/`, `/cart/`, `/checkout/`, `/my-account/`, 404, the drawer open, and logged in on `/my-account/plans/`, a Plan, orders and thank-you: **0 text below AA**. Only the `.olstars` empty track (`white/16`) is left: it's a `role="img"` graphic with an `aria-label`, and the value is printed as text beside it | Stars not changed |
| Weight | `style.css` (theme header only, 538 bytes) was a separate render-blocking stylesheet | Yes | No longer enqueued; WordPress reads the header from the file (`wp theme list` still shows 0.1.0). 1 stylesheet per page |
| Focus rings | Nav links and the white header CTA read grey/near-black right after Tab | Checked: an artefact. Tailwind's `transition` fades `outline-color` in over 150 ms; after 300–400 ms every focused control on the paths below is acid 2px | — |

Checked and passing:

- **Header:** skip link visible on focus, then logo, nav, CTA, cart.
- **Mobile menu:** `role=dialog`, `aria-modal`, labelled "Menu", `aria-expanded` toggles; Tab and Shift+Tab cycle inside (14 presses); Escape closes and restores focus.
- **Drawer:** the same, labelled "Your cart", with `main` and `header` inert; the close button is labelled "Close cart"; notices go to `role=status` `aria-live=polite`.
- **Goal tabs:** arrows wrap, Home and End work, roving `tabindex`, one panel shown, Tab goes to the panel.
- **FAQ:** closed answers are hidden and hold no focusable elements; Enter expands; Tab moves question to question.
- **Exit modal:** `role=dialog`, `aria-modal`, labelled by `#ol-exit-title`, page inert, focus on Close, 3-control trap, Escape closes. It arms after 8 s, on `(pointer: fine)` only.
- **Gallery** (with a temporary second image, removed afterwards): thumbnails named "Show image N of 2", acid ring, Enter and Space set `aria-pressed`.
- **Checkout errors:** a `role=alert` group whose items link to their fields, inline messages, `aria-invalid` on 3 fields.

### Measurements

**CLS** at 390px (mobile emulation, ~1.6 Mbps and 150 ms latency, a full scroll, 3 runs each): `/` 0.0000 / 0.0000 / 0.0000; 60 0.0000 / 0.0000 / 0.0000; `/shop/` 0.0000 / 0.0000 / 0.0000. As a control, a 200px insert scores 0.3364, so the observer works. Why nothing shifts:

- Anton and Inter are preloaded, with `font-display: swap`.
- The urgency bar is server-rendered.
- The sticky CTA and buy bar are `fixed`.
- Every `<img>` comes from `wp_get_attachment_image()` with width and height; no raw `<img>` in templates.

**Bytes** after `npm run build`:

| File | Raw | gzip -9 |
| --- | --- | --- |
| `main.css` | 208 363 | 26 194 |
| `main.js` | 13 766 | 4 692 |
| `slider.js` (Splide, registered only) | 31 466 | 13 847 |
| Anton woff2 | 18 612 | — |
| Inter woff2 | 48 256 | — |
| `/` HTML | 173 417 | 25 461 |
| 60 HTML | 137 786 | 21 918 |
| `/shop/` HTML | 80 184 | 13 531 |

`main.css` is 121 KB `@layer components` (WooCommerce, drawer, account, Portal, with `@apply` expanded), 70 KB utilities and 26 KB of typography prose.

**Requests per page type** (after the fix; the `fetch` is `get_refreshed_fragments`, only with a cart cookie):

| Page | Requests | Detail |
| --- | --- | --- |
| Home, Product, shop, page, search, 404 | 8 | Document, 1 CSS, 2 fonts, 3 JS (`sourcebuster.js`, `order-attribution.js`, `main.js`), 1 fetch |
| Blog post | 9 | + `comment-reply.js` |
| Account | 14 | + jQuery, blockUI, `js.cookie`, `woocommerce.js`, `account-i18n` |
| Cart | 16 | + WooCommerce's cart scripts |
| Checkout | 17 | + WooCommerce's checkout scripts |

No third-party request on any page. No WooCommerce or block-library stylesheet on any page. jQuery only on cart, checkout and account. Splide isn't loaded anywhere, since no template uses a slider yet.

### Deferred

- **WooCommerce order attribution.** `sourcebuster.js` and `order-attribution.js` load on every page and set `sbjs_*` cookies. They record the UTM source on each order, which matters for Meta ads, but they are a store feature and a consent question for EU buyers. That's the owner's call (WooCommerce → Settings → Advanced → Features), not the theme's.
- **The checkout coupon toggle still comes after "Place order" in focus order.** It must stay outside `form.checkout` and on WooCommerce's `woocommerce_after_checkout_form` hook; moving it above the form would change that hook's position for every plugin.
- **The 150 ms outline fade.** It's part of the `transition` utility and the final ring is acid; not changed.
- **`main.css` at 26 KB gzipped** is one cached request; splitting account/Portal CSS out is possible later.

Build, lint (exit 0; one line-length warning in `form-billing.php`, a line kept from WooCommerce's original) and PHPStan pass. `debug.log` has no new lines (158, the last from 11d's test).
