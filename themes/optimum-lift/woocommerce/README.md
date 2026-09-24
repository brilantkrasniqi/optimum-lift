# Template overrides

Files here override WooCommerce's own templates. The path must mirror the
plugin's: `woocommerce/templates/loop/price.php` is overridden by
`themes/optimum-lift/woocommerce/loop/price.php`.

Reach for a hook in `inc/woocommerce.php` first. An override is a frozen copy of
plugin code — it stops receiving upstream fixes, and WooCommerce will warn in
**WooCommerce → Status** once the original changes. Override only when the
markup itself has to change.

`woocommerce.php` in the theme root is not an override; it is the page wrapper
for every shop view.

## Overrides

| File | WooCommerce `@version` | Why |
| --- | --- | --- |
| `checkout/form-billing.php` | 3.6.0 | The billing heading is an `h2` instead of an `h3`, so the checkout's headings run h1 → h2 ("Your details", "Payment", "Your order") without skipping a level. Every hook and field of the original is kept. (11e) |
| `checkout/form-checkout.php` | 9.4.0 | Two-column grid (`.ol-checkout`, `form.checkout` is `display: contents`): details and payment left, the order summary right and sticky, the coupon form under it. On phones the summary comes first as a collapsed `<details>` with the total in its `<summary>`, and it's first in the source too, so keyboard focus follows what the buyer sees (11e). "Your order" is an `h2`. Every hook, id and class `checkout.js` uses is kept. (10c) |
| `checkout/review-order.php` | 11.0.0 | Thumbnail and category per line, the struck anchor price when a line is on sale, and a "You save" row (anchors' sum − subtotal). Every row and hook of the original is kept. (10c) |
| `checkout/thankyou.php` | 8.1.0 | Success hero with the page's h1 (first name, order number, date, total), "what happens next", the `woocommerce_thankyou` output (order table, then the Plans plugin's `.ol-order-plans`), cross-sells of what was bought, and the `purchase` tracking payload (`once`, order key as `eventID`). The failed branch keeps WooCommerce's pay-again and account links. (10d) |

Hooks rather than overrides (`inc/shop/checkout.php`): the trimmed fields, no
order notes, the coupon form moved after the checkout form, the payment block
moved into the form column with a "Payment" `h2`, the trust block after
"Place order", the summary total fragment, and "Billing details" read as
"Your details" for carts without shipping, and the order-received page shown
without a login wall to the buyer who just placed the order (see 10d).

Without JavaScript the coupon form is shown directly (WooCommerce hides it behind a script toggle), from `woocommerce.css` (11b).

The classic cart page (10e) has no override: it is styled in `assets/src/css/woocommerce.css`, and `inc/shop/checkout.php` swaps its thumbnail for the theme's (`woocommerce_cart_item_thumbnail`) and WooCommerce's loop cross-sells for `compact` cards (`woocommerce_after_cart`).

My Account and the Plans Portal have no overrides either (10f): both are styled in `assets/src/css/account.css`. The theme has no `optimum-lift-plans/` template overrides.
