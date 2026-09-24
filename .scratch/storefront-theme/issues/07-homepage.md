# Homepage: hero, marquee, problem, steps, goal tabs, pricing, sticky CTA, exit intent

Type: task
Status: claimed
Wave: 2
Blocked by: 01, 02, 03, 04
Split into: 07a, 07b

## What to build

`front-page.php` and the homepage-only layouts `home-hero`, `marquee`,
`problem`, `steps`, `goal-tabs`, `pricing` (template parts in
`template-parts/blocks/`), plus the mobile sticky CTA, the exit-intent modal
and `inc/shop/coupon.php`. Mock: `index.html` sections 3–7, 9, 18, 19 and its
JS. Sections 8 and 10–16 are ticket 06's blocks.

## Notes

- `home_hero`: proof chip = `optimum_lift_format_count_plus(optimum_lift_customer_count())` "klientë" + store rating when above threshold; the floating discount badge uses the bundle's saving percent and says "vetëm sonte" only when the offer ends within 24 h, and is hidden without a saving; stats tokens are replaced.
- `pricing`: countdown chip only with a real offer; bundle anchor via `bundle-banner` `home`; `featured` cards (`optimum_lift_featured_products()`); "Shiko të gjitha (N)" links to the shop; the "rest" panel hides when every product is already shown; trust row uses `payment-badges`.
- `goal_tabs`: WAI-ARIA tabs (`modules/tabs.js`: arrow keys, `aria-selected`, `aria-controls`, panels `hidden`); without JS all panels show stacked.
- `steps`: the recent-orders line only when `optimum_lift_recent_orders_count()` returns a number.
- `marquee`: duplicated list for the loop, second copy `aria-hidden`, paused under reduced motion.
- Sticky CTA (`modules/sticky-cta.js`) and a footer spacer on mobile.
- Exit intent (`modules/exit-intent.js`): desktop pointer only (`matchMedia('(pointer: fine)')`), mouse leaving through the top, not before 8 s on page, once per 7 days (localStorage, wrapped in try/catch), focus trapped, Escape closes; rendered only when `exit_coupon` is a valid, published, unexpired coupon. CTA `?ol_coupon=CODE#…`.
- `inc/shop/coupon.php`: `ol_coupon` query arg → validate → store in WC session; apply on `woocommerce_add_to_cart` and on `woocommerce_before_checkout_form` when the cart has items and the coupon is not applied; never apply an invalid code; add a notice once applied.
- `front-page.php` falls back to the page content when the front page has no blocks.
- `languages/src/07.json`.

## Acceptance criteria

- [ ] Screenshots at 390px and 1440px match `index.html` sections 1–9 and 17–19 with the spec's deviations.
- [ ] Every hero number traces to a helper or the Customizer; nothing reads "12.400+" unless the data says so.
- [ ] Following the exit modal CTA and adding a Product shows the coupon applied in the drawer and at checkout.
- [ ] lint and PHPStan pass on your files.

## Comments

2026-09-24: split into sub-tickets 07a, 07b. Work those, not this file; the last of them to resolve also resolves this ticket. The "coupon applied in the drawer" check moved to 09f.
