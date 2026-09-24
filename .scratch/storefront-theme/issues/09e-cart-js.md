# Cart drawer JavaScript (`modules/cart.js`)

Type: task
Status: resolved
Wave: 2
Parent: 09
Blocked by: 09d

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Spec: "JavaScript" and "Cart (09)". Mock: `ol-design/cart.js` §5, §7 and §9. The DOM wiring there is portable; its data layer isn't.

## What to build

`assets/src/js/modules/cart.js` (currently a stub; `main.js` already imports it last). `init()` returns immediately when `#ol-cart-drawer` is missing, which is the case on checkout.

**Endpoint URLs:** `window.optimumLift.wcAjaxUrl.replace('%%endpoint%%', name)`. `optimumLift` comes from `inc/assets.php:80-84,108-120`.

**One delegated `click` listener on `document`:**
- `[data-add-to-cart]` (links and buttons, anywhere on the page, the drawer's complement button included):
  - `preventDefault()`, then set `aria-busy="true"` on the element. `.btn[aria-busy]` is already styled (`components.css:43`).
  - POST `FormData{product_id}` to `ol_add_to_cart`.
  - On `ok` → apply fragments, open the drawer, and `track('add_to_cart', item)` (`import { track } from './track.js'`).
  - On `ok: false` → apply fragments, put `notice` into `[data-cart-notice]`, and open the drawer.
  - On a network or HTTP failure → `window.location.href = el.href`, when there is one.
  - Always clear `aria-busy`.
- `[data-cart-remove]` → POST `{cart_item_key, nonce}` to `ol_remove_from_cart`.
- `[data-cart-swap]` → POST `{bundle_id, nonce}` to `ol_swap_to_bundle`.
- `[data-cart-toggle]` → open the drawer.
- `[data-cart-close]` (the backdrop, the close button, "continue browsing") → close it. The continue-browsing link just closes the drawer; its `href` is only the no-JS fallback.
- `[data-cta="cart-checkout"]` → `track('begin_checkout', JSON.parse(el.dataset.beginCheckout))`, then let it navigate.
- Never intercept `[data-buy-now]`. It is plain navigation to 09b's endpoint.

**Open:**
1. Add `html.olc-open`.
2. Set `drawer.inert = false`.
3. Set `inert` on every other `body` child except the drawer, its backdrop and `script` elements. There is no page wrapper: `body` holds the skip link, header, mobile menu, `main` and footer.
4. Set `aria-expanded="true"` on every `[data-cart-toggle]`.
5. Focus the close button with `{preventScroll: true}`.
6. Trap Tab. Copy the pattern in `modules/menu.js:11-57`: its `FOCUSABLE` selector, the visible-only filter, and wrap-around.
7. Escape closes.
8. Dispatch `ol:cart:open` on `document`.

**Close:** reverse every step and return focus to the element that opened the drawer.

**`applyFragments(fragments)`:** for each key, replace **every** element matching the selector (there are two badge spans), then dispatch `ol:cart:updated` with `detail.fragments`.

**On load:** if `document.cookie` contains `woocommerce_items_in_cart`, POST `get_refreshed_fragments` once and apply the result. A cached page then corrects itself (ADR-0007).

**Constraints:**
- No prices are computed or formatted in JS.
- No user-facing strings in JS; read anything you need from `data-*` attributes.
- No jQuery.

## Files you own

- `assets/src/js/modules/cart.js`

## Acceptance criteria

- [x] Playwright at 390px and 1440px:
  - "Shto në shportë" on 60's page opens the drawer, which shows line 60 and the complement box for 62. Both header badges read 1.
  - Clicking the complement box adds 62, and the box becomes the 0,01 € upgrade (09c's matrix).
  - Remove works.
- [x] With [61, 62], the swap leaves only the bundle.
- [x] Keyboard: Tab never leaves the open drawer, Escape closes it, and focus returns to the button that opened it. While open, `main` is `inert`.
- [x] Aborting the endpoint request (`page.route(…, r => r.abort())`) makes the add-to-cart link navigate to its `href`, so WooCommerce adds the Product itself.
- [x] `dataLayer` gets `ol_add_to_cart` with `item`, and `ol_begin_checkout` on the checkout click.
- [x] `grep -nE 'toFixed|Intl\.|parseFloat|\* *[0-9]' assets/src/js/modules/cart.js` finds no price arithmetic.

## Answer

Built `assets/src/js/modules/cart.js` (2026-09-24). It uses one delegated click listener:
- `[data-add-to-cart]`: modified clicks (new tab or window) still follow the link.
- `[data-cart-remove]` and `[data-cart-swap]`.
- `[data-cart-toggle]` and `[data-cart-close]`.
- `[data-begin-checkout]` → `track('begin_checkout', …)`, then the link navigates.

While a request runs, the control has `aria-busy`, and a second click on a busy control is ignored.

- **Open** adds `html.olc-open`, lifts `inert` from the drawer and makes every other `body` child inert except the drawer, the backdrop and scripts. It only makes inert, and later restores, children that weren't already inert, so the closed mobile menu stays inert. It then sets `aria-expanded` on every toggle, focuses `.olc-close`, traps Tab (menu.js pattern), handles Escape and dispatches `ol:cart:open`.
- **Close** reverses all of that and focuses the opener. When the drawer's own complement button adds a Product, the original opener is kept.
- **`applyFragments`** replaces every match of each key. If the focused control was replaced (a remove button), focus moves to the close button. Then it dispatches `ol:cart:updated`.
- `add_to_cart` is tracked only when `ok` and `added !== false`, so re-adding a Product already in the cart doesn't inflate AddToCart. A successful swap also tracks `add_to_cart` for the bundle.
- On load, when the `woocommerce_items_in_cart` cookie exists, it POSTs `get_refreshed_fragments` once.

Evidence (Playwright, same results at 390px and 1440px):

- Product 60, "Add to cart" in `#blej` → drawer opens with line 60 and "Goes well with this … Add Plani Ushqimor 12-Javor — 6,99 €". Badges `['1(on)', '1(on)']`, `main.inert === true`, toggle `aria-expanded="true"`, focus on `.olc-close`.
- 25 × Tab and 5 × Shift+Tab never left the drawer.
- Complement click → lines 60 and 62, box "Only +0,01 € more for everything … Switch to Transformimi Total".
- Remove 62 → Store API cart `60`; focus stayed in the drawer.
- Escape → closed, `main.inert === false`, focus back on `[data-add-to-cart="60"]`.
- [61] → "Only +6,00 €" upgrade. [61, 62] → "Better deal … save 0,99 €". Swap → Store API cart `64`. The checkout link then navigated to `/checkout/`.
- `dataLayer`: `ol_add_to_cart` for 60 and 62 with `item`; `ol_begin_checkout` `{value: 6.99, currency: "EUR", items: [{id: 62, …}]}`.
- With `page.route('**/?wc-ajax=ol_add_to_cart*', abort)`, the click navigated to `/product/programi-i-stervitjes-12-javor/?add-to-cart=60`, and WooCommerce added it (cart `62,60`).
- No page errors. The price-math `grep` finds nothing (exit 1). `npm run build` OK.
