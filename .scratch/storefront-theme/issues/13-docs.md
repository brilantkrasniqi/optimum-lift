# Documentation

Type: task
Status: resolved
Wave: 4
Blocked by: 11

## What to build

- `CLAUDE.md`: the storefront in two or three lines (where sections, cart and checkout live, `wp ol-shop seed`, the ADRs), matching the file's existing density.
- `themes/optimum-lift/woocommerce/README.md`: list the overrides and why each exists.
- `.scratch/storefront-theme/spec.md`: set Status, and record anything built differently from the spec.
- Close tickets 01–13 (`Status: resolved`) with a short `## Answer`.

## Comments

2026-09-24: 01–04 were resolved when the remaining work was split into sub-tickets (05a … 11e). Close every sub-ticket and parent that is still open. Record these deviations from the spec in `spec.md` (confirm each against the code first):

- The cart drawer total is `WC()->cart` total, including coupon discounts, not the subtotal (09d), so the exit-intent coupon (07b) is visible.
- Checkout payment is moved from the order review to after the customer details, and the coupon form sits after `form.checkout` (10a, 10c).
- With the seeded prices, program + diet (14,98 €) is 0,01 € under the bundle, so the drawer offers an "upgrade +0,01 €" rather than a swap (09c).
- Any other deviation recorded in a sub-ticket `## Answer`.

## Answer

Done 2026-09-24.

- **`CLAUDE.md`:** the `wp ol-shop seed` line next to the Plans seed, and a short storefront list: sections (`ol_blocks` → `template-parts/blocks/`); cart, Buy Now and bundles; checkout and `woocommerce/README.md`; real-data proof; the ADRs; the `sq.po` workflow.
- **`themes/optimum-lift/woocommerce/README.md`:**
  - lists all four overrides with the current WooCommerce `@version` (checked against `plugins/woocommerce/templates`: form-billing 3.6.0, form-checkout 9.4.0, review-order 11.0.0, thankyou 8.1.0) and why each exists; `form-billing.php` (11e) is new, and the `form-checkout.php` row covers the source-order change;
  - the no-JS coupon form;
  - that cart, My Account and the Portal have no overrides.
- **`spec.md`:**
  - Status "done (2026-09-24)";
  - a new "As built" section. It has the three deviations this ticket names (each confirmed in the code: `foot.php:28`, `checkout.php:59–63` and `form-checkout.php`, the 11b ladder `[60 62] → upgrade +0,01`) and the others recorded in sub-ticket Answers (09b, 10c, 10d, 11b, 11c, 11d, 11e, 12);
  - three follow-ups from the review.
- **Tickets:** 01–12, every sub-ticket (05a … 11e) and every parent (05–11) have `Status: resolved` and an `## Answer`. This ticket is the last.
