# Buy Now endpoint (`?ol_buy_now=ID`)

Type: task
Status: resolved
Wave: 2
Parent: 09
Blocked by: 05a

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Architecture: ADR-0007 (Buy Now has its own endpoint and shares no handler with add-to-cart).

## What to build

`inc/shop/buy-now.php` is new; `functions.php` already requires it behind `is_file()`. Every "Bli tani" button already links to `optimum_lift_buy_now_url()` (`inc/shop/product-data.php:484`), but nothing handles that URL yet. Examples:
- `template-parts/product/buy-buttons.php:46`
- `template-parts/header/mobile-menu.php:67`
- `blocks/final-cta.php`
- `blocks/value-stack.php`

Add a `wp_loaded` handler. Copy the shape of the `?ol_coupon` handler in `inc/shop/coupon.php:112-136`.
- **Guards:** return on `wp_doing_ajax()` or when `ol_buy_now` is absent. Read the ID with `absint`.
- **Validation:** the Product must exist and be published, purchasable and in stock. Also apply `woocommerce_add_to_cart_validation` yourself; `WC_Cart::add_to_cart()` doesn't. After the cart is emptied, 09a's rules pass.
- **Session:** if the visitor has no WooCommerce session cookie yet, create it, as `coupon.php` does.
- **Success:**
  1. `WC()->cart->empty_cart()`
  2. `WC()->cart->add_to_cart($id)`
  3. `nocache_headers()`
  4. `wp_safe_redirect(wc_get_checkout_url())`, then `exit`
- **Failure:** add a notice and redirect to the Product's permalink, or to the shop when the Product doesn't exist. `template-parts/single-product/hero.php:39-40` and `template-parts/shop/archive.php:43-45` print notices.

The stored exit-intent coupon applies automatically: `add_to_cart()` fires `woocommerce_add_to_cart`, which `coupon.php:138` hooks, and the code lives in the session, so it survives `empty_cart()`.

`languages/src/09b.json` holds your notice strings.

## Files you own

- `inc/shop/buy-now.php`
- `languages/src/09b.json`

## Acceptance criteria

- [x] Cookie jar: add 61 and 62 with `?add-to-cart=`. Then `/?ol_buy_now=60` returns a 302 to `/checkout/`, and the Store API cart holds only 60.
- [x] `/?ol_buy_now=64` (the bundle) lands on checkout with only 64.
- [x] `/?ol_buy_now=999999` returns a 302 to the shop with a notice. An out-of-stock Product returns a 302 to its page with a notice (set it with WP-CLI, then restore).
- [x] After `/?ol_coupon=OPTIMUM10`, `/?ol_buy_now=60` lands on checkout with the coupon applied.
- [x] `09b.json` parses.

## Answer

Built `inc/shop/buy-now.php` and `languages/src/09b.json` (2026-09-24).

The `wp_loaded` handler for `?ol_buy_now=ID`, in order:
1. Guards: no cart or session, admin, or AJAX.
2. Starts the session cookie for first-time visitors. This happens **before** validation, so a failure notice also survives the redirect.
3. Checks that the Product exists, is published, purchasable and in stock.
4. `empty_cart()`, then applies `woocommerce_add_to_cart_validation`, then `add_to_cart()` (exceptions caught).
5. `nocache_headers()` and a redirect to checkout.

On failure, `optimum_lift_buy_now_fail()` adds a `notice` and redirects to the Product, or to the shop when the Product isn't viewable.

Evidence (cookie-jar curl):

- Cart `61,62` → `/?ol_buy_now=60` → `302 -> /checkout/`, cart `60`.
- `/?ol_buy_now=64` → `302 -> /checkout/`, cart `64`.
- `/?ol_buy_now=999999` (fresh visitor) → `302 -> /shop/`, and the shop shows "This product is no longer available."
- 61 set to `outofstock` (post meta), fresh visitor → `302 -> /product/force-mase/`, and the page shows "Forcë & Masë can't be bought right now." Restored to `instock`.
- `/?ol_coupon=OPTIMUM10` then `/?ol_buy_now=60` → checkout; cart `60` with coupon `optimum10`, total 7,19 €.
- `09b.json` parses; lint exit 0; PHPStan OK; `debug.log` unchanged.

Deviation: when validation or `add_to_cart()` fails after the cart was emptied, the previous cart is not restored. That can only happen when a third-party validation filter refuses the add, since the stock and purchasable checks run before emptying.
