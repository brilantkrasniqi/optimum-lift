# Homepage: goal tabs, mobile sticky CTA, translations, fidelity pass

Type: task
Status: resolved
Wave: 2
Parent: 07
Blocked by: 05a

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`.

## What to build

Ticket 07's markup landed in `a5fa5db`, but its modules are still stubs. This ticket covers the tabs and the sticky CTA; 07b does the exit modal.

- **`assets/src/js/modules/tabs.js`** drives `template-parts/blocks/goal-tabs.php:95-109`.
  - Markup:
    - `div[data-tabs]` wraps the block.
    - The `div[role=tablist]` is printed only when there are two or more tabs. Its wrapper is `hidden js:flex`.
    - Each tab is `button[role=tab]` with an `id`, `aria-controls`, `aria-selected`, a roving `tabindex` and `data-cta`. Its selected look comes from `aria-selected:` utilities.
    - Each panel is `div[role=tabpanel][aria-labelledby][tabindex=0]`.
  - Return early when there is no `[role=tablist]` (a single tab has no roles).
  - Panels 2+ are hidden by the class **`js:hidden`**, not the `hidden` attribute. At init, remove that class from every panel and set the `hidden` attribute on the inactive ones. Otherwise those panels can never be shown.
  - Support click, ArrowLeft/ArrowRight (wrapping), Home and End. Move `tabindex` with the selection, update `aria-selected`, and let focus follow the selection.
  - Without JS, the tab list stays hidden and every panel shows, stacked.
- **`assets/src/js/modules/sticky-cta.js`** drives `template-parts/home/sticky-cta.php:25`.
  - The bar is `div[data-sticky-cta]` with the classes `js:translate-y-full js:data-shown:translate-y-0 md:hidden`.
  - Use a passive scroll listener that toggles `data-shown` when `scrollY > 700`. Only act when the state changes, and evaluate once at init.
  - Set `inert` on the bar while it is hidden.
  - Add `motion-reduce:transition-none` to its markup.
  - Without JS, the bar stays visible. The footer spacer already exists (`site-footer.php:92-94`, `optimum_lift_has_sticky_bar()`).
- **`languages/src/07.json`** covers all 20 missing strings in the 07 files. That includes the exit modal's 8 strings, so 07b adds none.
  - Examples: "%1$s — %2$d%% off", "%s Albanian customers", "Only tonight", "View all (%s)", "Offer ends in", "Wait — don’t leave it for Monday", "Get the plan — %d%% off".
  - Plurals: `home-hero.php:159` ("%1$s/5 · %2$s review") and `steps.php:115` ("%s customer started in the last 24 hours").
  - Already defined, so don't redefine: "−%d%%" and "Value without discount:" (04).
- **Fidelity.** Compare `/` with `index.html` sections 1–9 and 17–19 at 390px and 1440px. Sections 10–16 belong to 06b. Also trace every number in the hero to a helper or a Customizer setting (ADR-0008): nothing may read "12.400+" unless the data says so.

## Files you own

- `assets/src/js/modules/{tabs,sticky-cta}.js`
- `assets/src/css/home.css`
- `front-page.php`
- `template-parts/blocks/{home-hero,marquee,problem,steps,goal-tabs,pricing}.php`
- `template-parts/home/sticky-cta.php`
- `languages/src/07.json`

## Reuse

- `modules/buybar.js`: the show/hide pattern using `data-shown` and `inert`.
- `modules/track.js`: tab clicks are already tracked through their `data-cta`.

## Acceptance criteria

- [x] Keyboard walk-through of the goal tabs: arrows move and select, Tab leaves the tab list and enters the panel, and exactly one panel is visible.
- [x] Playwright with JavaScript disabled: every panel shows, stacked, and no tab list is visible.
- [x] At 390px the sticky CTA appears after 700px of scroll and hides again above that. The footer's last line is never covered.
- [x] Screenshots match the mock sections, apart from the spec's deviations. Any remaining differences are listed in the Answer.
- [x] `07.json` parses.

## Answer

Built (2026-09-24):

- **`modules/tabs.js`**: the WAI-ARIA tabs pattern per `[data-tabs]`. It returns early without a `[role=tablist]`. At init it removes `js:hidden` from every panel and sets `hidden` on the inactive ones. Click, ArrowLeft/ArrowRight (wrapping), Home and End work; focus follows the selection, and the roving `tabindex` and `aria-selected` stay in sync.
- **`modules/sticky-cta.js`**: a passive scroll listener. `data-shown` appears when `scrollY > 700`, and the bar changes only when that state flips (also evaluated at init). The bar is `inert` while hidden. `motion-reduce:transition-none` is added to `sticky-cta.php`.
- **`languages/src/07.json`**: 20 strings, including the exit modal's (so 07b adds none). The 2 plurals have plural objects. The Albanian is the mock's where it exists ("Prit — mos e lësho për të hënën", "Përdore tani", "Jo faleminderit, do të vazhdoj pa ulje", "Oferta mbaron pas", "Vetëm sonte", "Shiko të gjitha (%s)", "Pagesë 100% e sigurt", "Dorëzim i menjëhershëm në email", "… klientë e kanë filluar në 24 orët e fundit"). There's no key clash with other files.
- **Honesty fix (`home-hero.php`)**: the "Only tonight" chip under the hero's −X% badge showed whenever the offer ended within 24 hours, which could be tomorrow evening. It now shows only when the offer ends **today** in the site time zone (`wp_date('Y-m-d', ends_at) === wp_date('Y-m-d')`), per ADR-0008.
- **`front-page.php`**: prints WooCommerce notices at the top of `main` when there are any (the follow-up from 09a). A no-JS `?add-to-cart=62` with the bundle in the cart now shows "…is already included in Transformimi Total in your cart." on `/`.
- `home.css` stays as it was, since nothing needed CSS.

Evidence (Playwright):

- **Tabs** at 1440px. Init: panels `V--`, tabs `S0 --1 --1`. ArrowRight ×3 → `-V-`, `--V`, `V--` (wraps), with focus on the selected tab. Home → first, End → last, ArrowLeft → middle. Tab from the tab list → focus on `ol-tabs-4-panel-1` (the visible panel). Clicking tab 2 → `-V-`. Exactly one panel is visible every time. No JS errors.
- **No JS** (390px): panels `VVV` stacked, tab list not visible, sticky CTA visible.
- **Sticky CTA** (390px): hidden and inert at 0 and 650px; shown at 760; hidden and inert again at 300. At the bottom, the footer's last line ends at y=740, above the bar at 770.
- **Fidelity**: `/` against `index.html` sections 1–9 and 17–19 at 390px and 1440px. Layout, type, spacing and copy match. Differences, all by spec or seeded content:
  - Numbers are computed: "600+ klientë shqiptarë" (Customizer baseline 600 + paid orders), "4,8/5 · 45 vlerësime" (store rating above the review threshold), the hero stats, "−50%" (the highest real saving). None of them reads "12.400+" or "1.284". The "Mbi 40 klientë … 24 orët e fundit" line under the steps is hidden below its threshold (`optimum_lift_recent_orders_count()` ≥ 10). "Vetëm sonte" is hidden because the offer ends in 3 days.
  - € prices; payment badges only Visa and Mastercard; the pricing badge "Best value" instead of "8 nga 10 klientë" (the share is below its threshold).
  - The value stack's heading and item values are seeded content (06a).
  - The pricing "rest" line reads "Kemi edhe 1 produkte të tjera", a plural mismatch in the seeded `rest_text` that the mock shares. Seed content, flagged for real copy.
  - UI chrome is in English until ticket 12.
- `07.json` parses; lint exit 0; PHPStan OK; build OK; `debug.log` unchanged.
