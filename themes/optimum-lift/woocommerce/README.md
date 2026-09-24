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
| `checkout/form-checkout.php` | 9.4.0 | Two-column grid (`.ol-checkout`, `form.checkout` is `display: contents`): details and payment left, the order summary right and sticky, the coupon form under it; on phones the summary comes first as a collapsed `<details>` with the total in its `<summary>`. Every hook, id and class `checkout.js` uses is kept. (10c) |
| `checkout/review-order.php` | 11.0.0 | Thumbnail and category per line, the struck anchor price when a line is on sale, and a "You save" row (anchors' sum − subtotal). Every row and hook of the original is kept. (10c) |

Hooks rather than overrides (`inc/shop/checkout.php`): the trimmed fields, no
order notes, the coupon form moved after the checkout form, the payment block
moved into the form column with a "Payment" heading, the trust block after
"Place order", the summary total fragment, and "Billing details" read as
"Your details" for carts without shipping.
