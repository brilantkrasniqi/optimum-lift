# Cart drawer, Buy Now, bundle rules and the upsell decision

Type: task
Status: claimed
Wave: 2
Blocked by: 01, 02, 03, 04
Split into: 09a, 09b, 09c, 09d, 09e, 09f

## What to build

Everything under "Cart (09)" in the spec: `inc/shop/{cart,bundle,buy-now,upsell}.php`,
`template-parts/cart/*`, `modules/cart.js`, `assets/src/css/drawer.css`.
ADR-0007 is the architecture. Mock: `cart.js` (read every
`[PORTABLE AS-IS]` / `[SPEC ONLY — REBUILD]` note) and brief §6.

## Notes

- Keep Buy Now and add-to-cart in separate handlers and separate files.
- Endpoints return JSON via `wp_send_json()`; fragments come from `WC_AJAX::get_refreshed_fragments()`'s filter (`woocommerce_add_to_cart_fragments`), keyed `div.olc-body-inner`, `div.olc-foot-inner`, and every count badge.
- Bundle rules on both paths (AJAX and `?add-to-cart=` without JS): `woocommerce_add_to_cart_validation` for "already covered by a bundle", `woocommerce_add_to_cart` for removing covered components after a bundle is added.
- Virtual Products are sold individually (existing filter in `inc/woocommerce.php`): adding one already in the cart must not surface WooCommerce's "cannot add another" error in the drawer; treat it as success.
- The upsell box copy follows the mock: `swap` "Ofertë më e mirë … Kalo te paketa e plotë dhe kurse X"; `complement` "Shkon bashkë me këtë … Shto NAME — PRICE" with the program/diet sentence; `upgrade` (new) "Vetëm +X më shumë për gjithçka" with the bundle name and anchor value.
- JS: delegated clicks; optimistic "adding…" state on the pressed button (`aria-busy`); after success open the drawer and track `add_to_cart` with the returned item; on network failure fall back to following the link's `href`.
- Drawer accessibility: `aria-modal`, focus into the close button on open, focus trap, restore focus, Escape, backdrop click, `inert` or `aria-hidden` on the page behind while open.
- The header toggle's `aria-expanded` mirrors the drawer.
- Admin warning for a bundle not cheaper than its components (on `woocommerce_process_product_meta` → transient → `admin_notices`).
- `languages/src/09.json`.

## Acceptance criteria

- [ ] Program in cart → drawer offers the diet (or the bundle upgrade when that is cheaper overall); program + diet at or above the bundle price → offers the swap with the right saving; clicking it leaves only the bundle.
- [ ] Adding the bundle removes its components; adding a component while the bundle is in the cart changes nothing and says why.
- [ ] `?ol_buy_now=ID` with other items in the cart lands on checkout with only that Product.
- [ ] No price in the drawer is computed in JavaScript.
- [ ] Works with JavaScript disabled (add-to-cart URLs and Buy Now still work; the toggle links to the cart page).
- [ ] lint and PHPStan pass on your files.

## Comments

2026-09-24: split into sub-tickets 09a, 09b, 09c, 09d, 09e, 09f. Work those, not this file; the last of them to resolve also resolves this ticket. 09f runs this ticket's acceptance end to end.
