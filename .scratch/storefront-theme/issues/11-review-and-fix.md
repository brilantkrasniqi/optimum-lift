# Review and fix pass

Type: task
Status: claimed
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
