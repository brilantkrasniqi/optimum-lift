# Shop archive

Type: task
Status: claimed
Wave: 2
Blocked by: 01, 02, 03, 04
Split into: 08a

## What to build

`template-parts/shop/archive.php` (+ sub-parts) and `inc/shop/archive.php`, per
"Shop" in the spec. Mock: `dyqani.html`.

## Notes

- Server-rendered from the main query: no client-side filtering.
- Filter pills are links (shop page, and each kind category archive), keep the current `orderby`; the active pill has `aria-current="page"`.
- Sort: a GET form with `orderby` (and the current category via the URL), labels from the mock, `modules/shop-sort.js` submits on change, a `<noscript>` submit button.
- `inc/shop/archive.php`: exclude bundles from the main product query on the shop page and kind archives (not the `paketa` archive), set per-page high enough to show the whole catalogue (the brief: 3–8 Products), map `orderby=menu_order` as the default.
- Intro count sentence uses `optimum_lift_catalog_count()`; the result count counts the banner when it shows (mock logic).
- Empty state with "Shiko të gjitha" linking to the shop.
- Guarantee band uses `guarantee_days`.
- Breadcrumb markup matches the mock (not WooCommerce's default output).
- `languages/src/08.json`.

## Acceptance criteria

- [ ] `/shop/`, `/shop/?orderby=price`, `/product-category/dieta/`, `/product-category/paketa/` match `dyqani.html` for the equivalent filter and sort at 390px and 1440px.
- [ ] Sorting by price and popularity orders the grid correctly.
- [ ] Works with JavaScript disabled.
- [ ] lint and PHPStan pass on your files.

## Comments

2026-09-24: split into sub-tickets 08a. Work those, not this file; the last of them to resolve also resolves this ticket.
