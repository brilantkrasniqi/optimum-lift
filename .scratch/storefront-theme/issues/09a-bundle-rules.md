# Bundle rules on the server (both add paths) and the bundle price warning

Type: task
Status: ready-for-agent
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

- [ ] Cookie-jar curl (`curl -c jar -b jar -L`): `/?add-to-cart=60`, then `/?add-to-cart=64`. The Store API cart (`/wp-json/wc/store/v1/cart`) holds only 64.
- [ ] Then `/?add-to-cart=62`: the cart is unchanged, and the next page shows the "already included" notice.
- [ ] `/?add-to-cart=64` again: no error notice, no change.
- [ ] Setting the bundle to 40 € in wp-admin (admin/admin) shows the warning once. Restore the price with `wp ol-shop seed`.
- [ ] `09a.json` parses.
