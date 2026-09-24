# Review and fix pass

Type: task
Status: resolved
Wave: 3
Blocked by: 05, 06, 07, 08, 09, 10
Split into: 11a, 11b, 11c, 11d, 11e

## What to do

Adversarial review of the integrated theme, then fixes:

- **Fidelity:** screenshots of every page at 390px and 1440px against the mocks; list visual regressions.
- **Behaviour:** buy now, add to cart, drawer upsell ladder, bundle rules, coupon, checkout fields, thank-you, no-JS paths, exercised over HTTP with a cookie jar.
- **Honesty:** every rule in ADR-0008 holds (set a Product below each threshold and check it disappears; expire the offer and check the bar goes).
- **Code:** escaping, nonces/CSRF where state changes, PHPStan, PHPCS, dead code, contract drift from the spec.
- **Accessibility and speed:** keyboard paths through menu, drawer, tabs, accordion and modal; contrast on dark; no layout shift from fonts or bars; JS and CSS weight.

Findings are verified before fixing. Fixes may touch any file.

## Comments

2026-09-24: split into sub-tickets 11a, 11b, 11c, 11d, 11e. Work those, not this file; the last of them to resolve also resolves this ticket. Work them one at a time: fixes may touch any file.

## Answer

Resolved 2026-09-24 through 11a–11e, one commit each. Fixed:

- **11a, fidelity:** the page scrolled sideways at 390px wherever a comparison table sat (`.scroll-x` positioned); the homepage table width back to the mock's; version pills on one row; the blog index title; the thank-you Plans separators.
- **11b, behaviour:** a minimum-spend coupon link never applied on the add that crossed the minimum; the checkout coupon form had no way in without JavaScript.
- **11c, honesty:** the site-wide countdown ran with no Product on sale (`optimum_lift_sale_running()`).
- **11d, code:** a PHP warning on an array `cart_item_key`; dead code removed (`optimum_lift_shop_setting()`, `optimum_lift_entry_meta()`, `.pill*`, `.eyebrow--accent`).
- **11e, accessibility and speed:**
  - the page is `inert` behind the mobile menu;
  - the skip link now focuses `<main>`;
  - checkout focus order and headings, with a new `form-billing.php` override;
  - `zinc-500`/`zinc-600` text raised to AA;
  - "Ends in" full white;
  - `style.css` no longer loaded.

Each sub-ticket's Answer lists what was deferred and why.
