# Proof blocks: FAQ accordion, `.proof-copy`, translations, acceptance

Type: task
Status: resolved
Wave: 2
Parent: 06
Blocked by: 05a

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`.

## What to build

The 06b templates landed in `a5fa5db`: `template-parts/blocks/{results,reviews,comparison,credibility,guarantee,faq,final-cta}.php`. The FAQ can't be opened yet, one class is undefined, and there are no translations.

- **`assets/src/js/modules/accordion.js`** is still a stub. The markup and CSS already fix its contract:
  - Markup, `faq.php:65-76`:
    - Container: `div[data-accordion]`.
    - Item: `div.acc` › `h3` › `button.acc-btn#faq-{index}-q{i}[aria-expanded][aria-controls]`.
    - Answer: `div.acc-body#faq-{index}-a{i}`.
  - CSS, `components.css:146-177`: `.js .acc-body` collapses to `0fr`, `.js .acc.open .acc-body` opens it, and `.acc.open .acc-ico` rotates the icon.

  The JS must:
  - Toggle `.open` on `btn.closest('.acc')`. The button's parent is the `h3`, so the mock's `this.parentElement` is wrong here.
  - Close the other items in the same `[data-accordion]` container only, so two FAQ blocks on one page stay independent.
  - Keep `aria-expanded` in sync.
  - Set `inert` on every collapsed `.acc-body`, at init and on each toggle. A `0fr` row still leaves links inside it focusable.
  - Keyboard support comes free with native buttons. Without JS, every answer shows.
- **`.proof-copy`** is used at `faq.php:76`, `faq.php:86` and `credibility.php:89`, but defined nowhere. It wraps editor HTML: multi-paragraph `<p>`, `<strong>`, links and lists. Define it in `assets/src/css/blocks-proof.css` with:
  - paragraph spacing;
  - `strong` in `text-zinc-200`, as in the mock;
  - links using the inline-link recipe from `pages.css` (`font-bold text-white underline decoration-zinc-600 underline-offset-2`);
  - list styling.
- **`languages/src/06b.json`** needs the 24 strings not already defined elsewhere. Examples: "Verified purchase", "Rate…", "Your rating", "Write a review", "You're viewing it", "Have other questions before you buy?". Albanian comes from the mocks.
  - Plurals: `reviews.php:189` ("%s review") and `reviews.php:191` ("%s review for this product").
  - Contexts: `result photo` for "Before"/"After" (`results.php:65,69`); `quotation` for "“%s”" (`results.php:137`, `reviews.php:222`).
  - Already defined, so don't redefine: "%s out of 5 stars" and "%s review" (04), "Comparison" (02), "(opens in a new tab)" (06a), "Buy now".
- **No star widget.** The review form keeps WooCommerce's native rating `<select>` inside a `<details>` (`reviews.php:253-279`), which the spec allows.

## Files you own

- `assets/src/js/modules/accordion.js`
- `assets/src/css/blocks-proof.css`
- `template-parts/blocks/{results,reviews,comparison,credibility,guarantee,faq,final-cta}.php`
- `languages/src/06b.json`

## Reuse

- The module pattern: `export function init()`, returning early when there is no `[data-accordion]`. `main.js` already imports and initialises it.
- `modules/menu.js` shows the house style for delegated listeners.

## Acceptance criteria

- [x] Ticket 06's criteria, for the 06b blocks:
  - Screenshots of 60 (`produkt.html` 10–14), 62 (`produkt-dieta.html`) and `/` (`index.html` 10–16) at 390px and 1440px match.
  - The medical disclaimer appears in 62's FAQ.
- [x] Comparison prices follow the Product's price. Change a column Product's sale price with WP-CLI, reload, see the new price, then revert.
- [x] No block prints an empty heading, list or table. Blank a block's rows (`wp eval` with `update_field`), check the page, then restore with `wp ol-shop seed`.
- [x] The accordion works by keyboard and allows one open item per container. Tab never lands inside a collapsed answer.
- [x] `06b.json` parses.

## Answer

Built (2026-09-24):

- **`modules/accordion.js`**: one click listener per `[data-accordion]`. It toggles `.open` on `btn.closest('.acc')`, closes the other items **of the same container** only, keeps `aria-expanded` in sync, and sets `inert` on every collapsed `.acc-body`, both at init and on each toggle. Without JS, all answers show (the existing CSS).
- **`.proof-copy`** in `blocks-proof.css`: paragraph spacing, `strong` in `text-zinc-200`, the `pages.css` inline-link recipe, and disc/decimal lists with accent/zinc markers.
- **`languages/src/06b.json`**: 24 strings (2 contexts: `result photo`, `quotation`; 1 plural, "%s review for this product"). There's no key clash with other files; "%s review", "%s out of 5 stars", "Comparison", "(opens in a new tab)" and "Buy now" aren't redefined. The Albanian comes from the mocks where it exists ("Blerje e verifikuar", "Ke pyetje të tjera para se të blesh?", "Shkruaj në WhatsApp — përgjigjemi brenda 24 orësh.", "Rezultatet ndryshojnë nga personi në person dhe varen nga zbatimi i planit.", "Para"/"Pas"). WooCommerce's rating labels use the same voice.
- The block templates didn't need changes; the review form keeps its native `<select>`.

Evidence:

- **Accordion** (Playwright, 390px, Product 62): at init all 7 items are closed and inert. Enter on #0 → `Oe` (open, expanded) with the others closed. Space on #1 → only #1 open. Clicking #1 again → all closed. Twelve Tabs from the first question never landed inside a collapsed answer. No JS errors.
- **Disclaimer**: 62's FAQ note renders "Shënim: Ky plan është material informativ dhe edukativ për njerëz të shëndetshëm — nuk është këshillë mjekësore…".
- **Comparison prices**: 60's comparison read `7,99 / 8,99 / 14,99 €`. With 60 at 6.49 (post meta), it read `6,49 / 8,99 / 14,99 €`. Reverted by `wp ol-shop seed` (Store API 799).
- **Empty blocks**: I blanked the rows and bodies of results, reviews (manual source), comparison, credibility, guarantee, FAQ and final CTA on 60 and the front page with `update_field('field_olt_blocks', …)`. There were 0 empty headings, lists, tbodies or paragraphs, no `data-accordion` and no `<table>`. The blanked sections disappeared entirely, headings included; the final CTA stayed (its button still has content). Restored with the seed.
- **Screenshots**: 60, 62 and `/` at 390px and 1440px, from the first proof section to the footer, against `produkt.html`, `produkt-dieta.html` and `index.html`. Structure, type, spacing and copy match. Differences:
  - Reviews show native demo reviews first, then manual ones (spec), so 6 cards instead of the mock's 3.
  - Manual testimonials don't carry "Blerje e verifikuar". Only native reviews from verified buyers do (ADR-0008).
  - Computed prices and rating; the trainer/results placeholders are as seeded.
  - UI chrome is in English until ticket 12.
- `06b.json` parses; lint exit 0; PHPStan OK; build OK; `debug.log` unchanged.

Not tested live: two FAQ blocks on one page, since no seeded page has two. The listener and item lookup are scoped to their own container.
