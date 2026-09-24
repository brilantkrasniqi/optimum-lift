# Data layer: sales fields, product helpers, pricing, proof, offers, block renderer

Type: task
Status: resolved
Wave: 1

## What to build

Everything under "Data layer (ticket 02)" in the spec, exactly as named there:
the ACF field groups (`inc/shop/fields.php`) and the helper API in
`inc/shop/{product-data,pricing,proof,offer,blocks}.php`.

ADR-0006 and ADR-0008 are the rules. The brief's §4 table (native field vs
custom field) is binding: rating, reviews, sold, featured, order, blurb and
prices come from WooCommerce, never from ACF.

## Notes

- Register field groups on `acf/include_fields`, only when `acf_add_local_field_group` exists.
- The front page location rule: `page_type == front_page`.
- The bundle group: `post_taxonomy == product_cat:paketa`.
- `icon` select choices: read the file names in `assets/icons/` at registration time (ticket 01 creates them; tolerate an empty directory).
- Transients: key them with a version string; clear the proof transients on `woocommerce_order_status_changed`, `save_post_product`, and `comment_post`/`wp_update_comment_count` for product reviews.
- `optimum_lift_offer()`: use the site timezone for `offer_ends_at`; return timestamps in UTC seconds.
- `optimum_lift_render_blocks()`: skip rows whose template part is missing; in `WP_DEBUG`, print an HTML comment naming the missing part.
- `optimum_lift_replace_tokens()`: prices through `wc_price()` with tags stripped to plain text is wrong for HTML contexts; return HTML-safe output (escape the text, then insert the formatted prices), and document that callers must not escape it again.
- No markup lives here except `optimum_lift_title_html()` and `optimum_lift_heading_html()`.
- `languages/src/02.json` for any strings (badge labels, "deri në −%s", …).

## Acceptance criteria

- [ ] Every field and layout in the spec's tables exists with those names, visible on a Product, on a `paketa` Product (bundle group) and on the static front page.
- [ ] Every helper in the spec exists with that signature and a PHPDoc return shape; `npm run analyse:php` passes on `inc/shop/`.
- [ ] With ACF deactivated, calling every helper on a Product does not fatal.
- [ ] A bundle's anchor price equals the sum of its components' current prices; a bundle priced at or above that sum has no saving.
- [ ] Proof helpers return null below their thresholds, and the thresholds are filterable.

## Answer

Landed in `1adeef1` (wave-1): `inc/shop/{fields,product-data,pricing,proof,offer,blocks}.php` with the helper API from the spec. Re-checked 2026-09-24 at integration level (PHPStan clean, every seeded block renders); ticket 11c exercises the ADR-0008 thresholds.
