# Classic cart page (the no-JS fallback for the drawer)

Type: task
Status: ready-for-agent
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

- [ ] Screenshot `/cart/` at 390px and 1440px, with 60 + 62 and when empty. Both are styled dark with no WooCommerce look.
- [ ] Remove, apply a coupon (`OPTIMUM10`) and "Proceed to checkout" all work.
- [ ] The cross-sells show `compact` cards.
- [ ] Lint and PHPStan pass, and `10e.json` parses.
