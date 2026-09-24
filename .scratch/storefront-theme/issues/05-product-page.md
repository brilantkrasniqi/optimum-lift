# Product page frame: hero, gallery, price box, buy bar, cross-sells

Type: task
Status: claimed
Wave: 2
Blocked by: 01, 02, 03, 04
Split into: 05a, 05b

## What to build

`template-parts/single-product/layout.php` and its parts, per "Product page"
in the spec. Mock: `produkt.html` and `produkt-dieta.html` sections 3, 4 and
15/16 (the middle sections are ticket 06's blocks, rendered by
`optimum_lift_render_blocks()`).

- Hero with CSS grid areas (mobile: head → gallery → body; desktop: gallery sticky left spanning both).
- Gallery (`modules/gallery.js`): featured image + gallery images, thumbnails swap the main image (`srcset` kept, `aria-pressed`), badge and media label chips; `.ph-photo` placeholder with the kind icon when there are no images; main image `fetchpriority="high"`, not lazy.
- Price box, delivery lines, payment badges, bundle hint (computed copy, links to the bundle Product).
- Trust strip from `ol_stats`.
- Fallback when there are no blocks.
- Cross-sells (`optimum_lift_cross_sells()`, `compact` cards).
- Sticky buy bar (`modules/buybar.js`), shown when the price box leaves the viewport, IntersectionObserver-based.
- WooCommerce structured data (`WC()->structured_data->generate_product_data()`), and the `view_item` tracking payload (`id`, `name`, `price`, `currency`).
- Header CTA already links `#blej` (ticket 01).
- `assets/src/css/product.css` only for what utilities cannot express.
- `languages/src/05.json`.

## Acceptance criteria

- [ ] Screenshots at 390px and 1440px of the seeded program and diet Products match the mocks' hero and cross-sell sections, with the spec's deviations.
- [ ] "Bli tani" goes to checkout with only that Product in the cart (after ticket 09; until then the link is correct).
- [ ] The buy bar never covers the footer's last content on mobile (spacer) and appears/disappears once per crossing.
- [ ] Google's Rich Results structure (JSON-LD `Product` with `offers`) is present in the HTML.
- [ ] lint and PHPStan pass on your files.

## Comments

2026-09-24: split into sub-tickets 05a, 05b. Work those, not this file; the last of them to resolve also resolves this ticket. 05a is the wave-2 baseline (lint, broken JSON, re-seed) that every other sub-ticket waits for. The Buy Now end-to-end check moved to 09f.
