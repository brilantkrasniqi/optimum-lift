# ADR, glossary, translations and docs

Type: task
Status: resolved
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

- [x] ADR-0011 exists, and ADR-0006 points to it.
- [x] `CONTEXT.md` defines Food, Recipe and Size.
- [x] `wp i18n make-pot` finds no new string missing from `sq.po`. The Albanian shows on the Product page, in the drawer and in the Size PDFs box with the site in Albanian.
- [x] The README walk-through matches what the screens actually show. Follow it once on the local site from a blank Product.
- [x] `npm run lint:php` and `npm run analyse:php` pass.

## Comments

### 2026-10-07 (Claude)

Done in the "Diets 10" commit; the boxes stay open until the owner's PC run.

- **The ADR is ADR-0013, not 0011.** (It was ADR-0012 until the merge, when the consent ADR took 0012 on `main`.) `docs/adr/0011-plans-move-between-sites-as-json-files.md` arrived with PR #3 first. The new ADR is `docs/adr/0013-diets-are-sold-in-sizes-as-variable-products.md`, and the code comments name it. ADR-0006's and ADR-0007's status lines say "Amended by ADR-0013 (diet Sizes)". ADR-0007's Buy Now bullet notes the Size.
- `CONTEXT.md`: **Food**, **Recipe** (with recipe key) and **Size**, after Nutrition Plan.
- Docs:
  - `woocommerce/README.md`: the `review-order.php` row, plus a paragraph on the variation-title filter and the `wp_loaded` pre-handler;
  - `content/diets/README.md`: "Selling the sizes in WooCommerce" rewritten for the Size PDFs box;
  - `CLAUDE.md`: a diets paragraph.
- **Translations:** WP-CLI cannot run in the cloud session, so `wp i18n` was not used. A small script appended the new entries to `optimum-lift.pot` and `sq.po` and rebuilt `sq.mo` and `sq.l10n.php` from the `.po`. On the unchanged `.po`, the same script produced files byte-identical to the committed `wp i18n make-mo`/`make-php` output. On the PC, run `make-pot`, `update-po`, `make-mo` and `make-php` once to refresh the references and confirm nothing is missing.

New strings (`optimum-lift` domain), for Poedit:

| English | Shqip |
| --- | --- |
| Changed to %s | U ndryshua në %s |
| This size is not available. | Kjo madhësi nuk është në dispozicion. |
| Choose your size first. | Zgjidh fillimisht madhësinë. |
| Choose your size | Zgjidh madhësinë |
| Size PDFs | PDF-të e madhësive |
| Size PDFs: | PDF-të e madhësive: |
| The Sizes have different prices. Every Size of a Product must cost the same; the page shows the lowest price. | Madhësitë kanë çmime të ndryshme. Çdo madhësi e një produkti duhet të kushtojë njësoj; faqja tregon çmimin më të ulët. |
| %s has an "Any …" attribute. Give it one value per attribute: such Sizes cannot be bought. | %s ka një atribut "Çfarëdo …". Jepi një vlerë për çdo atribut: madhësi të tilla nuk mund të blihen. |
| %s is not Virtual, so checkout asks for an address and skips the withdrawal waiver. | %s nuk është Virtuale, ndaj pagesa kërkon adresë dhe nuk shfaq heqjen dorë nga e drejta e tërheqjes. |
| %s has no PDF. | %s nuk ka PDF. |
| %1$s has %2$d PDFs, but the bundle contains %3$d diets sold in Sizes. | %1$s ka %2$d PDF, por paketa përmban %3$d dieta që shiten sipas madhësisë. |
| Upload the PDFs the diet renderer made for this Product, all at once. Each file goes on the Size its name ends with ("…-mashkull-80-90kg.pdf"). A file with the same plan name replaces the old one and keeps its download link. | Ngarko njëherësh PDF-të që krijoi gjeneruesi i dietave për këtë produkt. Çdo skedar shkon te madhësia me të cilën mbaron emri i tij ("…-mashkull-80-90kg.pdf"). Një skedar me të njëjtin emër plani zëvendëson të vjetrin dhe ruan linkun e shkarkimit. |
| Size | Madhësia |
| Price | Çmimi |
| Virtual | Virtuale |
| Downloadable | I shkarkueshëm |
| Files | Skedarët |
| Status | Statusi |
| Published | I publikuar |
| Private | Privat |
| Upload PDFs | Ngarko PDF |
| The files are attached when you update the Product. | Skedarët bashkëngjiten kur përditëson produktin. |
| %s did not upload. Try again, or upload fewer files at once. | %s nuk u ngarkua. Provo sërish, ose ngarko më pak skedarë njëherësh. |
| %s is not a PDF. | %s nuk është PDF. |
| %s matches no Size of this Product. Its name must end with a Size, like "-mashkull-80-90kg.pdf". | %s nuk përputhet me asnjë madhësi të këtij produkti. Emri i tij duhet të mbarojë me një madhësi, si "-mashkull-80-90kg.pdf". |
| %s matches more than one Size. | %s përputhet me më shumë se një madhësi. |
| %1$s and %2$s are both for %3$s. | %1$s dhe %2$s janë të dy për %3$s. |
| The upload folder could not be created. Check that wp-content/uploads is writable. | Dosja e ngarkimit nuk u krijua dot. Kontrollo që wp-content/uploads të jetë e shkrueshme. |
| %1$s could not be stored: %2$s | %1$s nuk u ruajt dot: %2$s |
| %1$s: %2$s | %1$s: %2$s |
| The Size PDFs were not attached: | PDF-të e madhësive nuk u bashkëngjitën: |
| %d PDF attached. | %d PDF u bashkëngjit. / %d PDF u bashkëngjitën. |

Unsure: "Çfarëdo …" for WooCommerce's "Any …". Use whatever WooCommerce's own Albanian shows on the variation screen.

**PC test:** follow the README's "Selling the sizes in WooCommerce" from a blank Product, with the site in Albanian. Then check the picker, the drawer and the Size PDFs box read in Albanian.

### 2026-10-08 (Claude)

Checked on the owner's PC on be8100c.

- **The ADRs:** ADR-0013 exists, since 0011 is plan files. The status lines of ADR-0006 and ADR-0007 point to it, and ADR-0007 has the Buy Now note.
- **`CONTEXT.md`:** it defines Food, Recipe and Size.
- **The translations:**
  - `wp i18n make-pot`, `update-po`, `make-mo` and `make-php` ran in the container. `sq.po` has 514 entries, none untranslated, fuzzy or obsolete, and the rebuilt `sq.mo` and `sq.l10n.php` are byte-identical to the committed ones.
  - The `.pot` and `.po` diffs were only `#:` references and translator comments. They are not committed here; the next `make-pot` refreshes them.
  - Its two warnings about translator comments come from `main`: "%s is already in your cart." in `bundle.php` and `coupon-toast.php`, and "%s review".
  - The Albanian showed in the picker, the notices, the drawer and the Size PDFs box. WooCommerce's "Any …" did not come up, so "Çfarëdo …" is still for the owner to check.
- **The README walk-through:** followed on a new Product from blank, and it matched. The README now also names the Albanian wp-admin labels and the first-visit tour, and mentions `max_file_uploads`, since a bundle's 20 files hit PHP's default limit. The box's Femër-first order was fixed in ticket 09.
- **Checks:** `phpcs` 0 errors and `phpstan` OK.
