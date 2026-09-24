# Product page: translations, sticky-bar spacer, fidelity pass

Type: task
Status: resolved
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

- [x] Screenshots of 60 and 62 at 390px and 1440px match the mocks' sections 3, 4 and 15/16, apart from the spec's deviations. Differences you left alone are listed in the Answer.
- [x] The HTML carries JSON-LD `Product` with `offers`, and the `view_item` payload.
- [x] Playwright, at 390px: scroll past the price box and back. The buy bar appears once and disappears once, and at the bottom of the page the footer's last line stays visible above it.
- [x] A Product made unpurchasable (for example, set out of stock with WP-CLI, then restore it) has no buy bar and no spacer.
- [x] `05.json` parses. The "Bli tani" check (checkout with only that Product) moves to 09f.

## Answer

Built (2026-09-24):

- **`languages/src/05.json`**: all 19 strings from `template-parts/single-product/*` ("Value without discount:" is left to 04). The Albanian comes verbatim from `produkt.html` / `produkt-dieta.html` where it exists: "Blihet shpesh bashkë me", "Të vjen në email brenda 60 sekondave", "Bli tani — akses i menjëhershëm", "Pagesë e njëhershme — pa abonim për të anuluar", "Akses i përhershëm + përditësime", "Pa abonim i fshehur", "Pa anulim i komplikuar", "Provoje pa rrezik", "Garanci %d ditë — pa pyetje", "Klientët që morën këtë program, shtuan edhe këto.", "Ushqimi zgjidh sa humbet…", "%1$s i ka të gjitha programet dhe dietat për %2$s." The rest is written in the same voice. The two `_n()` strings have plural objects. There's no key conflict with other files (checked by script).
- **`optimum_lift_has_sticky_bar()`**: on a Product it's true only when the buy bar renders: purchasable, in stock, and not behind a password form. It stays true on the front page, and the filter is kept.
- **`product.css`**: stays empty; the hero is all utilities.

Evidence:

- JSON-LD `@graph`: `Product+offers, BreadcrumbList`. `view_item` payload `{"id":60,"name":"Programi i Stërvitjes 12-Javor","price":7.99,"currency":"EUR"}`.
- Playwright at 390px, scrolling past the price box and back in 150px steps: `data-shown` transitions exactly `[true, false]`. At the bottom of the page the footer's last line ends at y=740, above the bar's top at 776.
- 61 set `outofstock`: no buy bar and no `h-20 md:hidden` spacer; after restoring, both are back. (The one remaining `data-buybar-target` is the price box's scroll sentinel, not the bar.)
- Fidelity screenshots for 60 vs `produkt.html` and 62 vs `produkt-dieta.html` at 390px and 1440px, covering the hero, trust strip and cross-sells: layout, type, spacing and copy match. Differences left alone:
  - Spec deviations: title and rating above the gallery on mobile; non-interactive version pills, which also means a note line; computed badge, rating, sold count, percent; € prices; Customizer payment badges (Visa, Mastercard only); a countdown only to a real end date.
  - The mock's gallery thumbnail strip is missing because the seeded Products have no gallery images. It renders once images exist (real content follow-up).
  - Compact cross-sell cards have "View product" + "Add to cart" (spec, ticket 04), where the mock has only one button.
  - UI chrome strings show in English until ticket 12 compiles `sq.mo`.
- `05.json` parses; lint exit 0; PHPStan OK; `debug.log` unchanged.
