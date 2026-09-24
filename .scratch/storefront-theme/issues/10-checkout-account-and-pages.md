# Checkout, thank-you, cart page, My Account, Portal and content pages

Type: task
Status: resolved
Wave: 2
Blocked by: 01, 02, 03, 04
Split into: 10a, 10b, 10c, 10d, 10e, 10f

## What to build

Everything under "Checkout and after (10)" in the spec. No mock exists: extend
the design system (dark surfaces, `h-display` headings, `.btn`, acid checks,
guarantee boxes) so these screens feel like the same store. The map's rule:
style Woo's checkout, do not rebuild it.

## Notes

- `inc/shop/checkout.php`: field trimming for carts that need no shipping (ADR-0007), email first with `autocomplete="email"` and `inputmode="email"`, remove order notes, move the coupon toggle below the order summary, trust block after the place-order button (guarantee days, instant access, encrypted payment, payment badges), "Pagesë e sigurt" in the minimal header is ticket 01's.
- Admin notice when the Cart/Checkout pages hold blocks instead of shortcodes.
- Template overrides in `themes/optimum-lift/woocommerce/`: copy from `wp-content/plugins/woocommerce/templates/` of the installed version (11.1.0) and keep the `@version` header; prefer hooks when markup need not change.
- Thank-you: success hero with the customer's first name, "what happens next" (email within minutes, set-password email for new accounts, where to open the Plans), the order details (the Plans plugin hooks its section in there), cross-sells of what was bought (compact cards), and the `purchase` tracking payload (`transaction_id`, `value`, `currency`, `items`, `eventID` = order key, `once`).
- Styling in `woocommerce.css`: notices, form rows, inputs, selects, checkboxes, radio payment methods, order review table, cart page, My Account navigation and tables, login/register, lost password, downloads, address forms, and the Plans Portal (`.ol-portal` in dark: check `portal.css` contrast with the new `theme.json` values and override variables where needed).
- `pages.css` + templates: `page.php` (prose-ol; WooCommerce pages wide without prose), blog index/single/archive/search/404 in the dark design with a way back to the shop.
- `editor.css`: leave the editor light, but set fonts.
- `languages/src/10.json`.

## Acceptance criteria

- [ ] Logged out, a seeded Product bought through Buy Now shows a checkout with email, first name, last name, country, payment and place order only, at 390px and 1440px.
- [ ] A test order with "Cash on delivery" or "Check payments" enabled temporarily reaches the thank-you page styled, with the Plans section when the Product has Plans; disable the gateway again afterwards.
- [ ] My Account › Plans (Portal) is legible on the dark background.
- [ ] No WooCommerce screen shows light unstyled boxes.
- [ ] lint and PHPStan pass on your files.

## Comments

2026-09-24: split into sub-tickets 10a, 10b, 10c, 10d, 10e, 10f. Work those, not this file; the last of them to resolve also resolves this ticket. The spec's ownership labels map as: 10a (checkout side) → 10a, 10c, 10d, 10e; 10b (pages and account) → 10b, 10f. The Portal and `account.css` live in 10f; notices, forms, checkout, thank-you and the cart page in `woocommerce.css` (10c, 10d, 10e).

## Answer

Resolved through 10a (trimmed checkout fields, hooks, trust block, block-page notice), 10b (translations, a light editor canvas, plus the missing `editor-styles` support), 10c (checkout layout and styles), 10d (thank-you page, plus the fix for the known-shopper login wall), 10e (cart page) and 10f (My Account and the Portal on the dark design).
