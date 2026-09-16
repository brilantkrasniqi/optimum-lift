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
