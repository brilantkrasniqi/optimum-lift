# Checkout: trimmed fields, hook moves, trust block, block-page notice

Type: task
Status: ready-for-agent
Wave: 2
Parent: 10
Blocked by: 05a

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Spec: "Checkout and after (10)". ADR-0007: classic checkout, and a cart with no shipping asks only for email, name and country. The map's rule: style WooCommerce's checkout, don't rebuild it.

## What to build

`inc/shop/checkout.php` is new; `functions.php:46` already requires it behind `is_file()`.

- **Trim the fields** with `woocommerce_checkout_fields`. When `WC()->cart` exists and `!needs_shipping()`, keep only:
  - `billing_email`: first, `autocomplete="email"`, `custom_attributes` → `inputmode="email"`;
  - `billing_first_name` and `billing_last_name`: side by side (`form-row-first` / `form-row-last`);
  - `billing_country`: a native `<select>`. `selectWoo` is dequeued at `inc/assets.php:146-148`, so WooCommerce's `country-select.js` leaves it alone.

  Drop the shipping fields entirely.
- **Order notes:** return false from `woocommerce_enable_order_notes_field`.
- **Coupon form:** remove `woocommerce_checkout_coupon_form` from `woocommerce_before_checkout_form` (priority 10) and add it to `woocommerce_after_checkout_form`. It must stay **outside** `form.checkout`: `checkout.js:1167` binds `form.checkout_coupon` once at startup, and nested forms are invalid HTML. 10c places it under the order summary visually.
- **Payment block:** remove `woocommerce_checkout_payment` from `woocommerce_checkout_order_review` (priority 20) and add it to `woocommerce_checkout_after_customer_details`. WooCommerce's `update_order_review` refreshes `.woocommerce-checkout-payment` by selector wherever it sits (`class-wc-ajax.php:496-497`). This lets 10c collapse the order summary on mobile without hiding payment.
- **Trust block:** hook `woocommerce_review_order_after_submit` (it prints inside `#payment`, next to Place order) to load `template-parts/checkout/trust.php`. It lists:
  - the guarantee (`optimum_lift_guarantee_label()`, hidden when `guarantee_days` is 0);
  - instant access;
  - encrypted payment;
  - `template-parts/product/payment-badges.php`.
- **Admin notice:** on `admin_notices`, warn when the Cart or Checkout page (`wc_get_page_id('cart' | 'checkout')`) has `has_block('woocommerce/cart' | 'woocommerce/checkout')`. Link to that page's edit screen and say to use `[woocommerce_cart]` / `[woocommerce_checkout]`.
- **`languages/src/10a.json`:** your strings.

The theme's minimal checkout header ("Pagesë e sigurt", the guarantee) already exists (`template-parts/header/checkout-header.php`). Don't duplicate it.

## Files you own

- `inc/shop/checkout.php`
- `template-parts/checkout/trust.php` (new directory)
- `languages/src/10a.json`

## Testing a real checkout

No payment gateway is enabled locally. For tests, enable **Cash on delivery**, not Check payments: COD puts a virtual Product's order in `processing`, which grants Plans, while Check puts it `on-hold`.
- Turn it on with `wp wc payment_gateway update cod --enabled=true --user=1`, or in WooCommerce › Settings › Payments.
- Make sure "Accept for virtual orders" is on.
- **Disable it again when you're done.**

Until 09b lands, reach checkout with `/?add-to-cart=60` and then `/checkout/`.

## Acceptance criteria

- [ ] Logged out, with 60 in the cart, `/checkout/` has exactly these fields: email (first), first name, last name, country. Plus payment and Place order. No address, phone, company or order notes, and no terms checkbox (the seed empties its text).
- [ ] The coupon form sits outside `form.checkout`, and applying `OPTIMUM10` through it works.
- [ ] Choosing another country, or applying a coupon, refreshes the order review and payment without JS errors.
- [ ] The trust block shows next to Place order and survives an `update_checkout` refresh.
- [ ] Temporarily put the Checkout block on the Checkout page: the admin notice appears. Restore it with `wp ol-shop seed`.
- [ ] COD is disabled again; lint and PHPStan pass; `10a.json` parses.
