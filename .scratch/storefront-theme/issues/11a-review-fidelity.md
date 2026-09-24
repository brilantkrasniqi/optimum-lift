# Review and fix: visual fidelity against the mocks

Type: task
Status: resolved
Wave: 3
Parent: 11
Blocked by: 05, 06, 07, 08, 09, 10

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. The 11 sub-tickets run one at a time, because fixes may touch any file.

## What to do

1. **Screenshot every page at 390px and 1440px.** Use the spec's Verification section. Scroll first, or force `.reveal` visible.

   | Page | Compare with |
   | --- | --- |
   | `/` | `index.html` |
   | Product 60 | `produkt.html` |
   | Product 62 | `produkt-dieta.html` |
   | Products 61, 63, 64 | the same system, no mock |
   | `/shop/` and the three kind archives | `dyqani.html` |
   | Drawer open | `cart.js` §4 look |
   | Checkout, thank-you, `/cart/`, My Account, Portal, blog, search, 404 | the design system (no mock) |

2. **List the visual regressions against the mocks.** Leave out what the spec lists as a deviation.
3. **Verify each finding** by re-checking it at the other width and against the mock's markup, then fix it.

## Acceptance criteria

- [x] The Answer holds a findings table: page, width, finding, verified yes/no, and what was fixed or why not.
- [x] Before/after screenshots for every fix.
- [x] Build, lint and PHPStan pass.

## Answer

Reviewed 2026-09-24. Screenshots of every page listed at 390px and 1440px, plus section-by-section pairs (live on the left, mock on the right) for `/` ↔ `index.html`, 60 ↔ `produkt.html`, 62 ↔ `produkt-dieta.html` and `/shop/` ↔ `dyqani.html`. They're in the job scratchpad (`C:\Users\Work\.claude\jobs\ee423a8e\tmp\11a\`: `*-before.png`, `*-after.png`, `pairs/*-cmp-NN.png`). The scripts are in `C:\Users\Work\.claude\jobs\dd052601\tmp\pw\w11\` (`shots.mjs`, `pairs.mjs`, `flows.mjs`, `overflow*.mjs`, `ver.mjs`, `crops.mjs`). `.reveal` is forced visible, and the sticky bars are hidden in the element pairs.

The site still shows English UI strings because `sq.mo` does not exist yet. That is ticket 12 and isn't listed below. The spec's deviations are also left out: computed proof and percentages, no "vetëm sonte", no countdown for the bundle, check pills instead of selectors, card + Mastercard only, the title above the gallery on mobile, native reviews before manual ones, and WhatsApp elements hidden without a number.

| Page | Width | Finding | Verified | Fix |
| --- | --- | --- | --- | --- |
| `/`, Product 60 (any page with a comparison block) | 390 | The page scrolls sideways: `scrollWidth` is 611 on a 390 viewport. The `.screen-reader-text` spans in the table cells are `position: absolute`, and their containing block was `.scroll-hint`, outside the `overflow-x: auto` scroller, so they widened the page. | Yes: hiding the section gives `scrollWidth` 380; 1440 is unaffected. | `.scroll-x { position: relative }` (`components.css`). After: `scrollWidth` 380 on both. |
| `/` | 390 | The comparison table is `min-w-[680px]`, but `index.html` uses 620px, so the third column didn't peek in, and the scroll cue was lost. | Yes: mock markup `index.html:1193`; `produkt.html` uses 680. | Front page `min-w-[620px]`, Products keep 680 (`comparison.php`). After: the third column peeks, as in the mock. |
| Products 60, 61, 62 | 390 + 1440 | Version pills wrap: the third pill drops to its own row ("I avancuar", "Shtim mase", "Pa laktozë"). The added check icon makes each pill about 20px wider than the mock's button. At 1440, `sm:grid-cols-2` also squeezed each group into half the column. | Yes, at both widths, against `produkt.html:321`. | Groups are `flex flex-wrap gap-x-8 gap-y-5`. Pills are `px-3 gap-1 text-[12px]` with a 12px check under `sm`, and the mock's sizes from `sm` (`versions.php`). After: one row per group on 60–63 at both widths (`ver.mjs`). |
| Blog index (`/?post_type=post`, no posts page set) | both | The `h1` read "Hello world!". `get_the_title(0)` returns the current post's title when `page_for_posts` is 0. | Yes | `optimum_lift_page_title()` falls back to "Blog" (the key already exists in `10b.json`). |
| Thank-you | both | The Plans row shows stray " — " and " · " between the plan name and its buttons (the plugin's text separators, `text-zinc-600`). | Yes, in the markup from `Orders` in the plugin | `.ol-order-plans li` font-size 0; the children set their own size (`woocommerce.css`). |
| Product 60 | both | The "not for you" item "…merr Transformimin Total" is plain text; the mock bolds and links it. | Yes | Not fixed: the spec says `no_items` is plain text. |
| Products 60, 62 | both | No gallery thumbnails. | Yes | Not a regression: the seeded Products have no images, and thumbnails render when a gallery exists. The mock's are placeholders. |
| Product 60 preview | 1440 | The video tile has no white play button. | Yes | Not fixed on purpose: no `media_video_url` is seeded, so a play button would be a dead control. |
| `/` value stack | both | Heading "Vlerë e plotë…" instead of "Vlerë 22.900 L…". | Yes | Seeded copy, not markup: the mock's figure is a Lekë sum. |
| Drawer, checkout, `/cart/`, My Account, Portal, search, 404, legal page | both | No regressions: dark design, no overflow, no page errors. | — | — |

Build, `npm run lint:php` (exit 0) and `npm run analyse:php` ("No errors") pass. `debug.log` has no new lines (157, the last from 2026-09-19).

Follow-ups: none for this ticket. Ticket 12 turns the remaining English strings into Albanian.
