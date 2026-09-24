# Wave-2 baseline: green lint, valid translation JSON, live demo offer

Type: task
Status: ready-for-agent
Wave: 2
Parent: 05
Blocked by: 01, 02, 03, 04

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`.

## What to build

Nothing new. Put the tree back in a state every other sub-ticket can start from. The health check on 2026-09-24 found PHPStan clean, the build compiling and every page rendering without PHP errors, with these exceptions:

- `npm run lint:php` fails with 3 errors, all `Generic.WhiteSpace.ScopeIndent.IncorrectExact` on the `?>` that closes a multi-line embedded PHP block:
  - `template-parts/blocks/comparison.php:161`
  - `template-parts/blocks/final-cta.php:99`
  - `template-parts/blocks/reviews.php:192`

  phpcbf marks all three as fixable. Line-length warnings don't fail the run (`ignore_warnings_on_exit` in `phpcs.xml.dist`).
- `languages/src/06a.json` is invalid JSON. The key on line 7 (`macronutrient total` + separator + `Fat`) contains a raw 0x04 byte, but the context separator must be written as the escape `\u0004`.
- The seed's sales and the site offer have ended: every Product shows `on_sale: false` at `/wp-json/wc/store/v1/products`. As a result, countdowns, savings and the urgency bar are hidden.
- `assets/dist/` predates the wave-2 commit, so it lacks the `js:hidden` and `data-shown:` rules.

## Files you own

- `template-parts/blocks/{comparison,final-cta,reviews}.php`: whitespace changes, or the small refactor below. No behaviour change.
- `languages/src/06a.json`

## Steps

1. Run `npm run composer -- fix`, then `git diff --stat`. Only the three templates may change; revert anything else phpcbf touched.
   - If the re-indent looks wrong, or an error remains, compute the values before the markup instead:
     - `$cell_class` in `comparison.php:155-161`, at the top of the cell loop.
     - `$body_class` in `final-cta.php:93-99`.
     - In `reviews.php:185-192`, a `$summary_text` computed before the `<p>` and echoed inside it.
2. In `06a.json`, write the key as `"macronutrient total\u0004Fat"`, keeping the value `"Yndyra"`. Then validate every source file:
   `for f in languages/src/*.json; do node -e "JSON.parse(require('fs').readFileSync('$f','utf8'))" || echo "BAD $f"; done`
3. Re-seed with `MSYS_NO_PATHCONV=1 docker compose --profile cli run --rm -T wpcli wp ol-shop seed`, then run `npm run build`.

## Acceptance criteria

- [ ] `npm run lint:php` exits 0 and `npm run analyse:php` reports no errors.
- [ ] Every `languages/src/*.json` parses.
- [ ] The Store API lists Products 60–63 with `on_sale: true`, and `/` shows the urgency bar with a countdown.
- [ ] `npm run build` succeeds, and `git diff --stat` shows only the files above.
