# Checkout: two-column layout, order review with savings, notices and form styles

Type: task
Status: resolved
Wave: 2
Parent: 10
Blocked by: 10a

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. No mock exists for checkout. Extend the design system (dark surfaces, `h-display` headings, `.btn`, acid checks, guarantee boxes) so checkout feels like the same store.

## What to build

- **`woocommerce/checkout/form-checkout.php`**: copy `plugins/woocommerce/templates/checkout/form-checkout.php` (WooCommerce 11.1.0; template `@version 9.4.0`) and keep its `@version` header.
  - Wrap the form in a grid of two columns on desktop: customer details on the left (10a moved payment and Place order here), and the order summary on the right, sticky (`lg:sticky`, offset by `--ol-sticky-top`).
  - On mobile, the summary comes **first**, collapsible (`<details>`), with the total in its `<summary>`. It stays inside `form.checkout`.
  - The grid wraps `</form>` and the `woocommerce_after_checkout_form` output too, so the coupon form (moved there by 10a) sits under the summary.
  - Keep every hook call and the classes and ids `checkout.js` relies on: `form.checkout`, `#customer_details`, `#order_review`, `.woocommerce-checkout-review-order-table`, `.woocommerce-checkout-payment`.
- **`woocommerce/checkout/review-order.php`**: copy it (template `@version 11.0.0`) and add:
  - a thumbnail per line (`template-parts/product/thumb.php`, small);
  - the struck anchor per line (`optimum_lift_anchor_price()`) when there is a saving;
  - a savings row, "Kursen" = anchors' sum − subtotal, only when > 0.

  Keep the coupon, fee and total rows.
- **`assets/src/css/woocommerce.css`**, in three sections (notices / forms / checkout). WooCommerce's own CSS is dequeued, so nothing falls back to its look.
  - **Notices:** `.woocommerce-message`, `.woocommerce-info`, `.woocommerce-error`, including links and buttons inside them. They also print in the Product hero and the shop.
  - **Forms:** `.form-row`, labels, `.required`, `.input-text`, selects, checkboxes, `.woocommerce-invalid` and `.woocommerce-validated`.
  - **Checkout:**
    - the payment list: `#payment .payment_methods` radios and `.payment_box`;
    - `#place_order` styled like `.btn .btn-primary .btn-lg .btn-block`. Tailwind v4's `@apply` accepts utilities, not component classes, so `@apply` the utilities those classes are built from (`components.css`);
    - `.woocommerce-privacy-policy-text`;
    - the `blockUI` overlay (`.blockUI.blockOverlay`), dark;
    - the trust block;
    - the coupon toggle and form.
  - Reuse the recipes in `pages.css`: input (l.158), label (l.154), checkbox row (l.165-175), card (l.52).
  - `page.php:15-46` already renders store pages wide with no prose.
- **`woocommerce/README.md`**: list each override, its WooCommerce `@version`, and why it exists.
- **`languages/src/10c.json`**: your strings.

## Files you own

- `woocommerce/checkout/{form-checkout,review-order}.php`
- `assets/src/css/woocommerce.css` (notices, forms, checkout sections)
- `woocommerce/README.md`
- `inc/shop/checkout.php` (10a is resolved by then)
- `languages/src/10c.json`

## Acceptance criteria

- [x] Ticket 10's first criterion: logged out, a seeded Product bought through Buy Now (`/?ol_buy_now=60` once 09b is done, otherwise `?add-to-cart`) shows only email, first name, last name, country, payment and Place order. Screenshot it at 390px and 1440px with COD temporarily enabled (see 10a), then disable COD.
- [x] Submitting the form empty shows styled inline errors and a styled error notice. No light, unstyled box appears anywhere.
- [x] On mobile the summary is first and collapsible; on desktop it is sticky on the right. Changing the country, or applying `OPTIMUM10`, refreshes it with no JS errors and keeps the layout.
- [x] The savings row equals the sum of the anchors minus the subtotal (for 60, 14,99 − 7,99 = 7,00 €).
- [x] Lint and PHPStan pass, and `10c.json` parses.

## Answer

Built (2026-09-24):

- **`woocommerce/checkout/form-checkout.php`** (`@version 9.4.0`): `.ol-checkout` grid; `form.checkout` is `display: contents`, so the details column, the summary and the coupon wrapper (`woocommerce_after_checkout_form`, outside the form) share one grid.
  - Areas, mobile: notices, summary, coupon, details.
  - Areas, `lg`: notices full-width; details on the left; summary on the right, spanning an `auto` and a `1fr` row and `position: sticky` at `--ol-sticky-top + 1.5rem`; the coupon form in the last right-hand row.
  - The summary is `<details open>`. A small inline script collapses it below 1024px and keeps it open at 1024px and up. Without JS it stays open.
  - The `<summary>` holds the "Your order" heading, a "Show details" hint and `.ol-summary-total`, which `woocommerce_update_order_review_fragments` refreshes.
- **`woocommerce/checkout/review-order.php`** (`@version 11.0.0`): thumb + category per line, `<del>` anchor when on sale, and a `.ol-savings` "You save" row = Σ anchors − displayed subtotal. All original rows and hooks are kept.
- **`inc/shop/checkout.php`**: `optimum_lift_checkout_summary_total_html()` and its fragment; a "Payment" heading before the moved payment block; "Billing details" becomes "Your details" through `gettext_woocommerce` on checkout when nothing ships.
- **`woocommerce.css`**:
  - notices (✓ / i / ! badges; styled links and buttons);
  - forms (label/input recipes from `pages.css`, invalid/validated states, inline errors, half-width first/last from `sm`);
  - checkout (the grid, the summary card and toggle, the review table, payment method cards, `#place_order` built from the `btn-primary btn-lg btn-block` utilities plus `shadow-glow`, privacy text, a dark `blockUI`, the coupon toggle and form, the login form).
- **`woocommerce/README.md`**: an overrides table; **`10c.json`**: 3 strings.

Evidence (Playwright via `/?ol_buy_now=60`, COD enabled temporarily; screenshots at 390px and 1440px):

- Fields: `billing_email`, `billing_first_name`, `billing_last_name`, `billing_country`, then payment and "Bëje porosinë".
- 390px: summary collapsed and above the details, showing "7,99 €" in its bar; the toggle opens it. 1440px: summary open on the right; after scrolling 600px its top is 24px (sticky).
- Empty submit: a dark red error notice listing three errors, three `.woocommerce-invalid` rows with red borders and inline messages. No light boxes.
- Country → DE and `OPTIMUM10` → review refreshed. Summary bar and order total 7,19 €, "Kupon: optimum10 −0,80 €" row, trust block kept. No JS errors at either width.
- Savings row "You save −7,00 €" (14,99 − 7,99).
- lint exit 0; PHPStan OK; build OK; `10c.json` parses; `debug.log` unchanged.

Deviation: on desktop the coupon toggle sits at the **bottom** of the right column, level with Place order, not directly under the summary. A sticky element can't leave its grid area, so a summary that stays sticky the whole height of the form can't have the coupon form (which must be outside `form.checkout`) right under it. The summary sticks down to just above the toggle. On mobile the toggle is directly under the summary.

Environment note: the **Check payments** gateway was enabled before this ticket, even though the spec says no gateway is enabled. I disabled it along with COD, so `wp wc payment_gateway list` now shows none enabled.
