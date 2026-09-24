# Proof blocks: FAQ accordion, `.proof-copy`, translations, acceptance

Type: task
Status: ready-for-agent
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

- [ ] Ticket 06's criteria, for the 06b blocks:
  - Screenshots of 60 (`produkt.html` 10–14), 62 (`produkt-dieta.html`) and `/` (`index.html` 10–16) at 390px and 1440px match.
  - The medical disclaimer appears in 62's FAQ.
- [ ] Comparison prices follow the Product's price. Change a column Product's sale price with WP-CLI, reload, see the new price, then revert.
- [ ] No block prints an empty heading, list or table. Blank a block's rows (`wp eval` with `update_field`), check the page, then restore with `wp ol-shop seed`.
- [ ] The accordion works by keyboard and allows one open item per container. Tab never lands inside a collapsed answer.
- [ ] `06b.json` parses.
