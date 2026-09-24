# Albanian translation of the theme

Type: task
Status: resolved
Wave: 4
Blocked by: 11

## What to build

Merge `themes/optimum-lift/languages/src/*.json` into
`themes/optimum-lift/languages/sq.po` (header: `Language: sq`,
`Plural-Forms: nplurals=2; plural=(n != 1);`), check coverage against
`wp i18n make-pot` output for the theme, translate anything missing in the
mock's voice, then generate `sq.mo` and `sq.l10n.php` with `wp i18n make-mo`
and `wp i18n make-php` in the WP-CLI container. Delete `languages/src/` once
merged; `sq.po` is the source from then on.

## Acceptance criteria

- [x] With the site language `sq`, no English theme string is visible on the homepage, shop, Product pages, drawer, checkout or thank-you page.
- [x] 100% of `make-pot` strings are translated.

## Comments

2026-09-24: the sources are every file in `languages/src/`, including the sub-ticket files (`05.json`, `06a.json`, `06b.json`, `07.json`, `08.json`, `09a.json`, `10b.json`, …). A key defined in more than one file must carry the same translation; list any conflict in the Answer. `06a.json` held a raw 0x04 byte instead of the escape `\u0004` until 05a fixed it: check that no other file does.

## Answer

Done 2026-09-24.

- **POT:** `wp i18n make-pot . languages/optimum-lift.pot --domain=optimum-lift --exclude=assets,languages` in the WP-CLI container gives 472 entries: 21 with a context, 16 plurals, and the `style.css` and `theme.json` strings.
- **Merge:** a script (`C:\Users\Work\.claude\jobs\ee423a8e\tmp\12\merge.cjs`, kept in the scratchpad) reads every `languages/src/*.json` file (01, 02, 04, 05, 06a, 06b, 07, 08, 09a, 09b, 09d, 10a, 10b, 10c, 10d, 10e) and writes `languages/sq.po` entry for entry from the POT, keeping references and translator comments. The header has `Language: sq` and `Plural-Forms: nplurals=2; plural=(n != 1);`.
  - **Conflicts: none.** Keys defined in more than one file carry identical translations.
  - **Raw 0x04 bytes: none**, in any file. Context keys use the `\u0004` escape.
  - **Unused JSON keys: none.** Every key is still in the code.
  - **Plurals:** every one matches the code's `msgid_plural`.
- **Missing, translated here (17)**, all theme metadata:
  - the `style.css` description ("Tema e dyqanit të Optimum Lift, e ndërtuar posaçërisht.") and URI;
  - the `theme.json` colour names (Sfondi, Sipërfaqja, E ngritur, Vija, Teksti, Teksti i zbehtë, Theksi, Theksi i çelët, Acid), font names (Inter, Anton) and size names (E vogël, Mesatare, E madhe, Shumë e madhe).
- **Coverage: 472 of 472 `make-pot` strings translated (100%).** 16 msgstrs equal their msgid on purpose: Optimum Lift, the URI, Blog, Bonus, "Bonus:", PDF, Kcal, Min, Menu, "d", Acid, Inter, Anton, "−%s%%", "−%d%%", "“%s”".
- **Compiled** with `wp i18n make-mo sq.po .` and `wp i18n make-php sq.po .` into `sq.mo` and `sq.l10n.php`. `languages/src/` is deleted; `sq.po` is the source from now on. `optimum-lift.pot` stays beside it for `wp i18n update-po`.
- **Tooling:** `sq.l10n.php` is generated PHP on one line, so `phpcs.xml.dist` now excludes `themes/optimum-lift/languages/*`, like `assets/`. PHPStan passes with it included.

Evidence:

- `wp eval` with locale `sq`: `__('Open cart')` → "Hap shportën", `_n('%s review', …, 5)` → "%s vlerësime", `_x('Paper', 'Color name')` → "Sfondi".
- **No English theme string visible** (`w11\english.mjs`). It takes every source string whose translation differs, splits it into literal fragments of 6+ characters (401 fragments), and searches each page's visible text plus `aria-label`, `placeholder`, `alt`, `title`, options and hidden dialogs. Pages covered:
  - homepage, `/shop/`, a kind archive, all five Products;
  - the drawer after an add;
  - `/cart/`, checkout, checkout with validation errors;
  - thank-you (order 80, logged in), My Account, search, 404.

  The only hit was "Protein" on 62, which is the prefix of the Albanian "Proteina" the page shows.
- Build, lint (exit 0) and PHPStan pass.

Follow-ups:

- WooCommerce's own strings come from its `sq` language pack.
- The Plans plugin's strings ("Your Plans", "Open", "Download PDF", Portal labels) are English, as the spec's follow-ups list.

Adding a string from now on:
1. `wp i18n make-pot . languages/optimum-lift.pot --domain=optimum-lift --exclude=assets,languages`
2. `wp i18n update-po languages/optimum-lift.pot languages/sq.po`
3. Translate the new entries.
4. `wp i18n make-mo` and `make-php`.
