# Bundle rules on the server (both add paths) and the bundle price warning

Type: task
Status: resolved
Wave: 2
Parent: 09
Blocked by: 05a

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Architecture: ADR-0007. Spec: "Cart (09)".

## What to build

`inc/shop/bundle.php` (new). `functions.php:42-45` already requires it, behind an `is_file()` guard.

- **Validation**, on `woocommerce_add_to_cart_validation` (`$passed, $product_id, $quantity`):
  - If a bundle in the cart contains the Product (see `optimum_lift_bundle_components()`), return false and add a `notice`-type message: "%1$s is already included in %2$s in your cart."
  - If the Product itself is already in the cart, return false with a gentle `notice`-type message: "%s is already in your cart."
    - This stops WooCommerce's sold-individually *error* ("You cannot add another…"). `inc/woocommerce.php:67-69` makes every virtual Product sold individually, and WooCommerce throws at `class-wc-cart.php:1305-1318`.
    - Check with `WC()->cart->find_product_in_cart(WC()->cart->generate_cart_id($id))`.
  - Note: `WC_Cart::add_to_cart()` does **not** apply this filter. Its callers do: `WC_Form_Handler` for `?add-to-cart=`, and `WC_AJAX`. The theme's own endpoints (09b, 09d) must apply it themselves.
- **Cleanup**, on `woocommerce_add_to_cart` at **priority 10**: when the added Product is a bundle, call `WC()->cart->remove_cart_item()` for every line whose Product is one of its components.
  - It must run before `inc/shop/coupon.php:138`, which applies the stored coupon at priority 20. That way the coupon is validated against the cleaned cart.
- **Admin warning**: warn when a bundle's price is not below the sum of its components.
  - Hook `acf/save_post` at priority 20. Don't use `woocommerce_process_product_meta`: WooCommerce saves on `save_post` at priority 1, before ACF writes `ol_bundle_components` at priority 10, so the components would still be the old ones.
  - If `optimum_lift_current_price($bundle) >= optimum_lift_anchor_price($bundle)`, set a per-user transient. `admin_notices` shows the warning once, then deletes the transient.
- **`languages/src/09a.json`**: your strings, English source, in the mock's Albanian voice.

## Files you own

- `inc/shop/bundle.php`
- `languages/src/09a.json`

## Reuse

From `inc/shop/product-data.php`:
- `optimum_lift_is_bundle()` (l.87)
- `optimum_lift_bundle_components()` (l.97): published and purchasable components only
- `optimum_lift_bundles()` (l.151)
- `optimum_lift_plain_text()` (l.228): names in notices; they come back entity-encoded

From `inc/shop/pricing.php`:
- `optimum_lift_current_price()` (l.11)
- `optimum_lift_anchor_price()` (l.22): for a bundle, the sum of its components' current prices

Keep `WC()->cart?->` calls null-safe; PHPStan runs with `treatPhpDocTypesAsCertain: false`.

## Acceptance criteria

- [x] Cookie-jar curl (`curl -c jar -b jar -L`): `/?add-to-cart=60`, then `/?add-to-cart=64`. The Store API cart (`/wp-json/wc/store/v1/cart`) holds only 64.
- [x] Then `/?add-to-cart=62`: the cart is unchanged, and the next page shows the "already included" notice.
- [x] `/?add-to-cart=64` again: no error notice, no change.
- [x] Setting the bundle to 40 € in wp-admin (admin/admin) shows the warning once. Restore the price with `wp ol-shop seed`.
- [x] `09a.json` parses.

## Answer

Built `inc/shop/bundle.php` and `languages/src/09a.json` (2026-09-24).

- `woocommerce_add_to_cart_validation`: returns false with a `notice` when a bundle in the cart covers the Product ("%1$s is already included in %2$s in your cart."), or when the Product already has a line ("%s is already in your cart."). This replaces WooCommerce's sold-individually error.
- `woocommerce_add_to_cart` at priority 10: adding a bundle removes the lines of its components.
- `acf/save_post` at priority 20 → per-user transient → one `admin_notices` warning. There are two messages: price ≥ components' sum, and no published components.
- New public helpers for 09b/09c/09d: `optimum_lift_cart_bundle_covering(int $product_id): ?WC_Product` and `optimum_lift_cart_has_product(int $product_id): bool`. The theme's own endpoints still need to call `apply_filters('woocommerce_add_to_cart_validation', true, $id, 1)` themselves.

Evidence:

- Cookie jar: `/?add-to-cart=60` → cart `60`; `/?add-to-cart=64` → cart `64` only.
- `/?add-to-cart=62` → cart still `64`; the next WooCommerce page (`/product/plani-ushqimor-12-javor/`) shows `woocommerce-info` "…already included in Transformimi Total in your cart."
- `/?add-to-cart=64` again → cart `64`, no `woocommerce-error`; an info notice "…already in your cart." instead.
- Playwright in wp-admin: set the bundle to 40 € and saved. The warning read "Transformimi Total costs 40,00 €, which is not less than its components together (29,96 €)…" once, and was gone on the next admin page. Restored with `wp ol-shop seed` (Store API price 1499 again).
- `09a.json` parses; lint exit 0; PHPStan OK; `debug.log` unchanged.

Follow-up (not my file): the front page (`front-page.php`, 07) prints no WooCommerce notices. A no-JS `?add-to-cart=` from a homepage card leaves its notice until the next Shop, Product or Cart page. 07a/07b could call `woocommerce_output_all_notices()` near the top of the page.
