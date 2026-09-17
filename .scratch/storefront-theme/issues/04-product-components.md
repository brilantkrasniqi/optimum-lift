# Product components: card, bundle banner, price, rating, buttons

Type: task
Status: ready-for-agent
Wave: 1

## What to build

The reusable product partials in `template-parts/product/`, per the table
"Product components" in the spec. The product card is the most-reused
component in the store: build the `shop` variant element for element from
`dyqani.html`'s `card()` and brief §4.1 (photo + badge + percent, goal pill,
category + name, fractional stars + labelled count, blurb + 3 complementary
bullets, price + struck anchor + absolute saving, the one-time/instant/guarantee
line, sold count, "Shiko produktin" primary + a real bordered "Shto në shportë").

`featured` follows `index.html` section 9 cards; `compact` follows the
cross-sell cards in `produkt.html` / `produkt-dieta.html` section 15/16;
`bundle-banner` follows `dyqani.html` `bundleBanner()` (`shop`) and the
`index.html` anchor card (`home`).

## Notes

- Use only the helpers in the spec (ticket 02 writes them in parallel); every call guarded so a missing helper degrades instead of fatals is **not** required here, the integration step runs after both land.
- Buttons follow the markup contract exactly (`data-buy-now`, `data-add-to-cart`, `data-cta`).
- Every element with a computed value renders nothing when the helper returns null (no "0 të shitura", no empty stars, no "Kursen 0").
- Images: `wp_get_attachment_image()` with sensible `sizes`, `loading="lazy"` unless `$args['eager']`.
- Albanian copy for strings goes into `languages/src/04.json`.

## Acceptance criteria

- [ ] Each part renders for a program, a diet and the bundle Product without notices.
- [ ] At 390px the card's two buttons are at least 44px tall and full width.
- [ ] The rating shows 4.7 as 94% star width with "4,7" and "(N vlerësime)".
- [ ] lint and PHPStan pass on `template-parts/product/`.
