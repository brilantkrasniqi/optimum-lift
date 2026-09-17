# Section blocks shared by Product pages and the homepage

Type: task
Status: ready-for-agent
Wave: 2
Blocked by: 01, 02, 03, 04

## What to build

One template part per layout in `template-parts/blocks/`:
`qualification`, `phases`, `preview`, `sample-day`, `shopping-list`,
`value-stack`, `results`, `reviews`, `comparison`, `credibility`, `guarantee`,
`faq`, `final-cta`, `rich-text`. Field names are in the spec. Each receives
`$args = ['block' => array, 'product' => ?WC_Product, 'post_id' => int, 'index' => int]`.

Mock sections: `produkt.html` 5–14, `produkt-dieta.html` 5–15, `index.html` 8
(value stack, `split` layout with the plan mockup), 10–16 (results,
testimonials, comparison, credibility, guarantee, FAQ, final CTA).

## Notes

- Section wrapper: `<section id="{optimum_lift_block_id}" class="… {optimum_lift_block_tone_class}">`, eyebrow, `h2` via `optimum_lift_heading_html()`, intro.
- Any row can be empty: render nothing rather than an empty shell.
- `value_stack`: values through `wc_price()`, total computed, "you pay" = the product's current price, CTA is Buy Now for that product.
- `comparison`: `price` rows use the column product's current price; `cta` rows show "Po e shikon" for the context product and a link otherwise; horizontal scroll with `.scroll-x .scroll-hint` on mobile.
- `reviews`: native reviews (approved, rating ≥ 4, with text, newest first, limited), then manual items; the summary box uses `optimum_lift_rating()` (Product) or `optimum_lift_store_rating()` (front page) and hides below the threshold; never print "Blerje e verifikuar" unless WooCommerce marks the review as verified.
- `faq`: accordion (`modules/accordion.js`: one open at a time, `aria-expanded`, `aria-controls`, keyboard accessible); `note` renders the disclaimer box and must never be dropped when set; `help_box` only when `whatsapp` is set.
- `final_cta`: countdown only when `optimum_lift_offer()` returns one.
- `results`: before/after images or `.ph-photo` placeholders with "Para"/"Pas" labels; the disclaimer always shows.
- `.reveal` on section content, never on anything above the fold.
- `languages/src/06.json`.

## Acceptance criteria

- [ ] Every block renders from the seeded data on the program Product, the diet Product and the front page, matching the mock sections at 390px and 1440px.
- [ ] The medical disclaimer appears on the diet Product's FAQ.
- [ ] The comparison table's prices change when a Product's price changes.
- [ ] No block prints an empty heading, list or table.
- [ ] lint and PHPStan pass on your files.
