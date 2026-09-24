# Documentation

Type: task
Status: ready-for-agent
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
