# Homepage: goal tabs, mobile sticky CTA, translations, fidelity pass

Type: task
Status: ready-for-agent
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

- [ ] Keyboard walk-through of the goal tabs: arrows move and select, Tab leaves the tab list and enters the panel, and exactly one panel is visible.
- [ ] Playwright with JavaScript disabled: every panel shows, stacked, and no tab list is visible.
- [ ] At 390px the sticky CTA appears after 700px of scroll and hides again above that. The footer's last line is never covered.
- [ ] Screenshots match the mock sections, apart from the spec's deviations. Any remaining differences are listed in the Answer.
- [ ] `07.json` parses.
