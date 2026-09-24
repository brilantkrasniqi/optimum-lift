# Shop archive: translations and acceptance

Type: task
Status: resolved
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

- [x] Ticket 08's criteria: screenshots match, apart from the spec's deviations. The bundle banner shows only on the shop page and on `paketa`.
- [x] Sorting by `price`, `price-desc` and `popularity` orders the grid correctly. Parse the card prices and names out of the HTML with curl and compare them with the Store API.
- [x] Playwright with `javaScriptEnabled: false`: the filter pills navigate, and the `<noscript>` "Apply" button sorts.
- [x] `08.json` parses.

## Answer

Built (2026-09-24):

- **`languages/src/08.json`**: the 27 strings, with Albanian from `dyqani.html` where it exists ("Të zgjedhurat", "Më të shiturat", "Çmimi: i ulët → i lartë", "Filtro sipas kategorisë", "Rendit", "Po punojmë për produkte të reja — ndërkohë shiko të gjitha.", "Garanci %d ditë për çdo produkt", "… produkte digjitale në shqip — programe stërvitjeje dhe plane ushqimore.", …). The 5 plurals have plural objects.
  - "Previous"/"Next" are "E mëparshme"/"Tjetra". I ran before 10b, so **10b must copy these exactly**.
  - No key clash with other files.
- **Fix (`template-parts/shop/archive.php`)**: on the bundle archive the banner is the only Product. The template printed "1 product" and then an empty grid section (a dead gap above the guarantee band). The grid section is now skipped when there are no grid Products but the count is above 0 (and there's no pagination); a short spacer keeps the rhythm.

Evidence:

- **Sort** (curl, cards parsed from the grid):
  - `menu_order`: 60, 61, 62, 63 (menu 1–4).
  - `price`: 63, 62, 60, 61 at 5,99 / 6,99 / 7,99 / 8,99 €.
  - `price-desc`: 61, 60, 62, 63.
  - `popularity`: 60, 62, 61, 63, which matches `total_sales` 97 / 74 / 37 / 13.
  - All of this matches the Store API prices. Bundles are excluded from the grid.
- **No JS** (Playwright, `javaScriptEnabled: false`): the filter pills are links (All → `/shop/`, Training programs / Diets / Bundles → their `product-category` URLs), and clicking Diets navigated to `/product-category/dieta/`. The `<noscript>` "Apply" button is visible; after choosing "Price: high to low" it submitted to `/shop/?orderby=price-desc`.
- **Banner**: `a[data-buy-now="64"]` is present on `/shop/` and `/product-category/paketa/`, and absent on `dieta` and `programe-stervitjeje`.
- **Screenshots**: `/shop/`, `?orderby=price`, `dieta` and `paketa` at 390px and 1440px against `dyqani.html`. They match. Differences, all spec deviations:
  - computed badges ("Best value" instead of "8 nga 10"; "New"; "Best seller");
  - a sold count only from 25 up (63 has 13, so none);
  - € prices;
  - diet placeholders use the acid tint (`ph-photo--acid`);
  - the banner's add-to-cart is the outline button from `buy-buttons` rather than the mock's text link;
  - English UI until ticket 12.
- `08.json` parses; lint exit 0; PHPStan OK; build OK; `debug.log` unchanged.
