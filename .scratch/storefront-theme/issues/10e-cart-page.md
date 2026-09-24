# Classic cart page (the no-JS fallback for the drawer)

Type: task
Status: resolved
Wave: 2
Parent: 10
Blocked by: 10d

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. This ticket shares `woocommerce.css` with 10c and 10d.

## What to build

Without JavaScript, the header's cart button links to `/cart/`, which renders `[woocommerce_cart]` through `page.php` (wide, no prose). ADR-0007: no WooCommerce screen may fall back to light, unstyled boxes.

- **`woocommerce.css`, new cart-page section.** Prefer CSS over template overrides. Cover:
  - `table.shop_table.cart` (stacked rows on mobile), the product thumbnail and name, the remove link (×), the price and subtotal;
  - the coupon field and button;
  - "Update cart";
  - `.cart_totals` with the "Proceed to checkout" button (`.checkout-button`) styled as `.btn-primary`;
  - the empty cart (`.cart-empty`, `.return-to-shop`).
- **Cross-sells.** WooCommerce's `woocommerce_cross_sell_display` uses the loop templates, which the theme doesn't style. Remove it from `woocommerce_cart_collaterals` in `inc/shop/checkout.php`, and print `compact` cards from `optimum_lift_cross_sells()` over the cart lines instead, using the grid from `template-parts/single-product/cross-sells.php`.
- **Template overrides** go in `woocommerce/cart/**` and `woocommerce/notices/**`, and only when CSS can't do the job. Copy each from `plugins/woocommerce/templates/`, keep its `@version`, and add it to `woocommerce/README.md`.
- **`languages/src/10e.json`:** your strings.

The `woocommerce` script (jQuery) loads on the cart page (`inc/assets.php:152-154`), so WooCommerce's own update and remove behaviour works.

## Files you own

- `assets/src/css/woocommerce.css` (cart-page section)
- `inc/shop/checkout.php` (the cross-sells swap)
- `woocommerce/cart/**`, `woocommerce/notices/**` (only if needed)
- `woocommerce/README.md`
- `languages/src/10e.json`

## Acceptance criteria

- [x] Screenshot `/cart/` at 390px and 1440px, with 60 + 62 and when empty. Both are styled dark with no WooCommerce look.
- [x] Remove, apply a coupon (`OPTIMUM10`) and "Proceed to checkout" all work.
- [x] The cross-sells show `compact` cards.
- [x] Lint and PHPStan pass, and `10e.json` parses.

## Answer

Built (2026-09-24), with **no template overrides**:

- **`woocommerce.css`, cart-page section**:
  - `.woocommerce` becomes a grid: lines and coupon on the left, a sticky `.cart_totals` card on the right from `lg`, notices and cross-sells full width.
  - Each `tr.cart_item` is a drawer-style card at every width: thumb, name, subtotal, × remove. `thead` is screen-reader-only.
  - Price and quantity columns are hidden, since every Product is sold individually and the subtotal equals the price. "Update cart" is hidden too; only quantities would need it.
  - Coupon field + ghost button; a totals card with Anton total, acid coupon row and `.checkout-button` built from the `btn-primary btn-lg btn-block` utilities; the empty state (`.cart-empty` as an info notice, `.return-to-shop` as a white button).
- **`inc/shop/checkout.php`**:
  - `optimum_lift_cross_sells_for($products, $limit)`: the union of cross-sells without owned or bundle-covered Products.
  - `woocommerce_cart_item_thumbnail` → the theme's `thumb.php` in a `.ol-cart-thumb` tile, replacing WooCommerce's light placeholder image.
  - `woocommerce_cross_sell_display` is removed from `woocommerce_cart_collaterals` and replaced on `woocommerce_after_cart` by a "Frequently bought together" grid of `compact` cards.
- **`modules/cart.js`** (09e, mine): `[data-add-to-cart]` isn't intercepted on the cart page (`body.woocommerce-cart`), because the drawer can't update the cart table. The link's `?add-to-cart=` reloads the cart page with the new line.
- **`woocommerce/README.md`**: a note that the cart page needs no override.
- **`languages/src/10e.json`**: `{}`, no new strings (the heading reuses 05's "Frequently bought together").

Evidence:

- Screenshots of `/cart/` at 390px and 1440px, with 60 + 62 and empty. Everything is dark: line cards with kind-icon tiles, the totals card, cross-sells (63, bundle 64, 61 as compact cards), and the empty notice with "Kthehu te shitorja". No light boxes or WooCommerce placeholder images remain; before the change, all of them were there.
- Playwright (JS on, WooCommerce's jQuery cart script):
  - Remove Dieta Mesdhetare → lines 60 and 62, notice "“Dieta Mesdhetare” u hoq. Të zhbëhet?".
  - `OPTIMUM10` → "Kupon: optimum10 −1,50 €", total 13,48 €.
  - "Kaloni tek kasa" → `/checkout/`.
  - A cross-sell "Add to cart" → `/cart/?add-to-cart=63`, with the line added and the drawer not opened. No JS errors.
- lint exit 0; PHPStan OK; build OK; `10e.json` parses; `debug.log` unchanged.

**Environment note, not code**: every local page now takes 3–9 s, including WordPress's generated `robots.txt` (~3 s), with an empty cart, and with idle container CPU. That looks like Docker-on-Windows bind-mount I/O. It made WooCommerce's AJAX cart updates look stuck under a 2.5 s test wait; with a wait on `.blockUI` they complete. Worth checking before judging speed in 11e.
