# Demo catalogue seed and local site configuration

Type: task
Status: resolved
Wave: 1

## What to build

`wp ol-shop seed` in `themes/optimum-lift/inc/cli.php`, as described under
"Seed (03)" in the spec. It is how every other ticket sees its work with real
data, so the content must be complete: transcribe the Albanian copy of every
section from `index.html`, `produkt.html` and `produkt-dieta.html` into the
`ol_blocks` layouts and fields named in the spec, and write the two products
without a mock page (Forcë & Masë, Dieta Mesdhetare) and the bundle in the same
voice (shorter stacks are fine: qualification, phases, reviews, FAQ, final CTA).

Where the spec deviates from the mock, seed the deviation: included versions
instead of selectors (and FAQ answers that no longer say "choose Vegjetariane
above"), no PayPal claims, etc.

## Notes

- Write fields with `update_field()` using the **field keys** from ticket 02's naming scheme (`field_olt_*`), not names, so values save even before the field groups are loaded in CLI context; ticket 02 writes the groups in parallel, so derive keys from the spec's naming convention and verify them once both are done.
- Idempotent: look up by slug and update; `--reset` deletes and recreates the seeded Products, front page, coupon and reviews.
- Refuse to run unless `wp_get_environment_type()` is `local` or `development`.
- Demo reviews: 3–12 per Product with realistic Albanian text, author names prefixed "Demo", ratings averaging 4.6–4.9; update the rating meta (`WC_Comments::clear_transients` / product `set_rating_counts`, `set_average_rating`, `set_review_count`).
- Demo `total_sales` roughly proportional to the mock's `sold`, scaled down.
- Link the first published `ol_training_plan` to the program Product through the Plans plugin's `plans` field when that plugin is active.
- `docker/setup.sh`: install the `sq` core and WooCommerce language packs (`wp language core install sq`, `wp language plugin install woocommerce sq`), tolerant of network failure. Leave everything else in setup.sh alone.
- Site config done by the seed, not setup.sh: `WPLANG=sq`, admin user `locale=en_US`, coming soon off, default country `XK`, price format, Cart/Checkout pages to shortcodes, static front page, `offer_ends_at`, `exit_coupon`.
- No `languages/src` file needed (seed content is Albanian data, not UI strings).

## Acceptance criteria

- [ ] `wp ol-shop seed` runs twice in a row without errors or duplicates.
- [ ] After seeding, `/`, `/shop/`, each Product, `/product-category/dieta/`, `/cart/`, `/checkout/` return 200 for a logged-out visitor.
- [ ] Every layout in the spec appears at least once in the seeded content.
- [ ] `npm run lint:php` and `npm run analyse:php` pass on `inc/cli.php`.

## Answer

Landed in `1adeef1` (wave-1): `wp ol-shop seed` in `inc/cli.php` (Products 60–64, front page `kreu`, coupon, legal pages, shortcode Cart/Checkout, price format, `sq`). The seed has no user-facing theme strings, so there is no `03.json`. Sales and the site offer run for 3 days after seeding: re-seed when `on_sale` is false (it was on 2026-09-24).
