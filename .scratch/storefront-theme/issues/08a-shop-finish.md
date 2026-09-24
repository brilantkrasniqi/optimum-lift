# Shop archive: translations and acceptance

Type: task
Status: ready-for-agent
Wave: 2
Parent: 08
Blocked by: 05a

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`.

## What to build

Ticket 08 landed in `a5fa5db`: `inc/shop/archive.php`, `template-parts/shop/*` and `modules/shop-sort.js`. What remains is the translations and running its acceptance checks.

- **`languages/src/08.json`**: the 27 strings not yet defined anywhere, with Albanian from `dyqani.html`. Examples: "Our picks", "Best sellers", "Price: low to high", "Filter by category", "Sort", "Apply", "No products yet.".
  - Plurals:
    - `guarantee.php:24` "%d-day guarantee on every product" (same text for both forms)
    - `guarantee.php:29` "Try it for %d day(s)."
    - `intro.php:46` "%s digital product(s) in Albanian — …"
    - `intro.php:52` "One-time payment, instant access, %d-day guarantee on all of them." (same text for both forms)
    - `toolbar.php:54` "%s product(s)"
  - Already defined in 01, so don't redefine: `breadcrumb\u0004Home` (`inc/shop/archive.php:114`), "Products", "Breadcrumb", "Shop", "All products".
  - "Previous" and "Next" also appear in 10b's templates. Whichever of 08a and 10b runs second must copy the first one's translation exactly.
- **Fidelity.** Compare `/shop/`, `/shop/?orderby=price`, `/product-category/dieta/` and `/product-category/paketa/` at 390px and 1440px with `dyqani.html` under the equivalent filter and sort. Fix regressions in the files you own.

## Files you own

- `template-parts/shop/*`
- `inc/shop/archive.php`
- `assets/src/css/shop.css`
- `assets/src/js/modules/shop-sort.js`
- `languages/src/08.json`

## Acceptance criteria

- [ ] Ticket 08's criteria: screenshots match, apart from the spec's deviations. The bundle banner shows only on the shop page and on `paketa`.
- [ ] Sorting by `price`, `price-desc` and `popularity` orders the grid correctly. Parse the card prices and names out of the HTML with curl and compare them with the Store API.
- [ ] Playwright with `javaScriptEnabled: false`: the filter pills navigate, and the `<noscript>` "Apply" button sorts.
- [ ] `08.json` parses.
