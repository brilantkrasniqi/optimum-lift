# Product page: translations, sticky-bar spacer, fidelity pass

Type: task
Status: ready-for-agent
Wave: 2
Parent: 05
Blocked by: 05a

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`.

## What to build

Ticket 05's templates landed in `a5fa5db`: `template-parts/single-product/*`, `modules/gallery.js` and `modules/buybar.js`. Three things are still missing: the translations, a spacer fix, and the check against the mocks.

- **Translations.** Write `languages/src/05.json` with every translatable string in `template-parts/single-product/*`. There are 19, not counting "Value without discount:", which `04.json` already has. Use the Albanian from `produkt.html` and `produkt-dieta.html` verbatim where it exists. Examples: "Frequently bought together", "Arrives in your email within 60 seconds", "Buy now — instant access", "Show image %1$d of %2$d". These need plural objects:
  - `bundle-hint.php:57`: "%1$s includes this and %2$s more product(s) for %3$s."
  - `fallback.php:38`: "%d-day guarantee — no questions asked" (the same text for both forms still needs the plural object).
- **Spacer.** `optimum_lift_has_sticky_bar()` (`inc/template-tags.php:320`) returns true on every Product. But `template-parts/single-product/buy-bar.php:20` renders nothing when the Product is not purchasable or out of stock, and `layout.php:23-26` returns early for password-protected Products. In those cases the footer's `h-20` spacer (`template-parts/footer/site-footer.php:92-94`) is dead space. Make the helper return true on a Product only when the buy bar actually renders.
- **Fidelity.** Screenshot Product 60 against `produkt.html` and Product 62 against `produkt-dieta.html`, at 390px and 1440px: sections 3 (hero), 4 (trust strip) and 15/16 (cross-sells). The middle sections belong to 06a/06b. Fix regressions in the files you own. Check against the spec's list of deviations before calling anything a regression. For example, on mobile the title sits above the gallery on purpose, and the version pills are not interactive.

## Files you own

- `template-parts/single-product/*`
- `assets/src/css/product.css`
- `inc/template-tags.php` (only `optimum_lift_has_sticky_bar()`)
- `languages/src/05.json`

## Reuse

- The hero grid is built from utilities in `hero.php:45-88`: grid areas head / gallery / body, and a sticky gallery on desktop. `product.css` probably stays empty. If it does, say so in the Answer.
- Structured data: `layout.php:28` calls `WC()->structured_data->generate_product_data()`.
- `view_item` payload: `layout.php:54-61`.

## Acceptance criteria

- [ ] Screenshots of 60 and 62 at 390px and 1440px match the mocks' sections 3, 4 and 15/16, apart from the spec's deviations. Differences you left alone are listed in the Answer.
- [ ] The HTML carries JSON-LD `Product` with `offers`, and the `view_item` payload.
- [ ] Playwright, at 390px: scroll past the price box and back. The buy bar appears once and disappears once, and at the bottom of the page the footer's last line stays visible above it.
- [ ] A Product made unpurchasable (for example, set out of stock with WP-CLI, then restore it) has no buy bar and no spacer.
- [ ] `05.json` parses. The "Bli tani" check (checkout with only that Product) moves to 09f.
