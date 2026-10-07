# ADR, glossary, translations and docs

Type: task
Status: ready-for-agent
Blocked by: 04, 06, 07, 08, 09

## What to build

**`docs/adr/0011-diets-are-sold-in-sizes-as-variable-products.md`.** Use the format of ADR-0006 and ADR-0007: Status, Context, the options considered, Decision and Consequences. Take the substance from spec Decisions 9–19.
- Options to cover: Simple Product with all 10 PDFs; WooCommerce's variations form; the theme's own picker; a separate Product per Size.
- Consequences to include:
  - every Size costs the same;
  - "Any" variations are unsupported;
  - a bundle must sell every Size of its sized diets;
  - the picker is generic, so a third attribute needs no theme change.
- Add "Amended by ADR-0011 (diet Sizes)" to ADR-0006's status line, and to ADR-0007's if its Buy Now description changed.

**`CONTEXT.md`:** add **Food**, **Recipe** (with recipe key) and **Size**, as defined at the top of the spec, near **Nutrition Plan**.

**Translations:**
- Every new string in the `optimum-lift` domain goes into `languages/sq.po`. Run `wp i18n make-pot`, `update-po`, `make-mo` and `make-php` as in the storefront's ticket 12. The strings include "Choose your size", "Choose your size first.", "This size is not available.", "Changed to %s", "Size PDFs" and the box's warnings.
- Write Albanian in the store's voice, informal "ti" (e.g. "Zgjidh madhësinë", "Zgjidh fillimisht madhësinë."), and list every new msgid and translation in the comment so the owner can check them in Poedit.

**Docs:**
- `themes/optimum-lift/woocommerce/README.md`: the `review-order.php` row mentions the Size line, and the hooks paragraph mentions the variation-title filter.
- `content/diets/README.md`: rewrite "Selling the sizes in WooCommerce" for the new flow:
  1. On production, once: Products › Attributes, create Gjinia and Pesha with the exact slugs and terms from spec Decision 9 (the seed does this locally).
  2. New Product, type Variable. Add Gjinia and Pesha, or only Pesha for a one-gender diet, "Used for variations". Generate variations, then bulk-set the price.
  3. Update, then upload all the PDFs in "Size PDFs" and check its table has no warnings.
  4. To update a diet, re-render and upload again. Customers who bought get the new file.
- `CLAUDE.md`: one paragraph like the storefront's, covering:
  - diets are written in `content/diets/` (renderer, Recipes, plans; spec `.scratch/diet-plans/`);
  - sized diet Products are variable, handled in `inc/shop/sizes.php` and `size-files.php` (or wherever ticket 06 put them) and `modules/size-picker.js`;
  - every Size costs the same.

## Acceptance criteria

- [ ] ADR-0011 exists, and ADR-0006 points to it.
- [ ] `CONTEXT.md` defines Food, Recipe and Size.
- [ ] `wp i18n make-pot` finds no new string missing from `sq.po`. The Albanian shows on the Product page, in the drawer and in the Size PDFs box with the site in Albanian.
- [ ] The README walk-through matches what the screens actually show. Follow it once on the local site from a blank Product.
- [ ] `npm run lint:php` and `npm run analyse:php` pass.

## Comments
