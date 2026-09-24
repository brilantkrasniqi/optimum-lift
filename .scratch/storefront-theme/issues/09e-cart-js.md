# Cart drawer JavaScript (`modules/cart.js`)

Type: task
Status: ready-for-agent
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

- [ ] Playwright at 390px and 1440px:
  - "Shto në shportë" on 60's page opens the drawer, which shows line 60 and the complement box for 62. Both header badges read 1.
  - Clicking the complement box adds 62, and the box becomes the 0,01 € upgrade (09c's matrix).
  - Remove works.
- [ ] With [61, 62], the swap leaves only the bundle.
- [ ] Keyboard: Tab never leaves the open drawer, Escape closes it, and focus returns to the button that opened it. While open, `main` is `inert`.
- [ ] Aborting the endpoint request (`page.route(…, r => r.abort())`) makes the add-to-cart link navigate to its `href`, so WooCommerce adds the Product itself.
- [ ] `dataLayer` gets `ol_add_to_cart` with `item`, and `ol_begin_checkout` on the checkout click.
- [ ] `grep -nE 'toFixed|Intl\.|parseFloat|\* *[0-9]' assets/src/js/modules/cart.js` finds no price arithmetic.
