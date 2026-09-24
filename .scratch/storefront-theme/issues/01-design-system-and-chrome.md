# Design system and site chrome

Type: task
Status: resolved
Wave: 1

## What to build

The design tokens, fonts, CSS primitives, JavaScript skeleton, header, footer,
mobile menu, urgency bar, countdown, icons and theme settings that every other
ticket builds on. Also the stubs that let tickets 02–10 run in parallel.

Mock references: the `<head>` styles, sections 1–2, the mobile menu, the
footer and the JS of `index.html`, `produkt.html` and `dyqani.html`.

## Scope (files: see the ownership table in the spec)

- `theme.json`: palette and font families per the spec (fontFace pointing at `assets/fonts/`), `styles` background `paper`, text `ink`.
- Fonts: install `@fontsource/anton` and `@fontsource-variable/inter` as devDependencies, copy the **latin** woff2 files into `themes/optimum-lift/assets/fonts/` (committed), preload Anton and Inter in `wp_head`.
- `assets/src/css/main.css` imports `base.css`, `components.css`, then `product.css`, `blocks.css`, `home.css`, `shop.css`, `drawer.css`, `woocommerce.css`, `pages.css` (create the ones you do not own as empty files with a header comment naming their ticket). Map tokens in `@theme inline`; shadows in `@theme`. Remove the old BEM components that the new chrome replaces.
- `base.css` / `components.css`: every class listed under "Component classes" in the spec.
- `inc/assets.php`: keep the version logic; dequeue WooCommerce's front-end styles (`woocommerce_enqueue_styles` → `[]`, plus `wc-blocks-style` and `woocommerce-inline`) and `wc-add-to-cart`, `wc-cart-fragments`, `selectWoo`/`select2` except where WooCommerce needs them on checkout country selects (prefer native selects); inline `window.optimumLift`; print the `html.js` class as early as possible; drop the Splide enqueue from the default path (keep the registration).
- `inc/setup.php`: remove the WooCommerce gallery zoom/lightbox/slider supports (the Product page has its own gallery); menus `primary`, `footer`; image sizes if needed.
- `inc/customizer.php`: settings table from the spec and `optimum_lift_setting()`.
- `inc/icons.php`: `optimum_lift_icon(string $name, string $class = 'w-4 h-4', array $attrs = []): string` reading `assets/icons/<name>.svg` (static cache), `aria-hidden="true"` by default. Create the icon files used across the mocks (logo bars, cart, menu, close, arrow-right, check, x, shield-check, clock, star, play, bolt, barbell, bowl, plate, calendar, trend-up, target, user, users, lock, send, chat, instagram, tiktok, box, list, bar-chart, medal, phone, video, fire, sparkle …).
- `header.php`, `footer.php`, `template-parts/header/{urgency-bar,site-header,mobile-menu,checkout-header}.php`, `template-parts/footer/{site-footer,checkout-footer}.php`, `template-parts/components/countdown.php`: behaviour in "Header and chrome" in the spec. Call data-layer helpers (`optimum_lift_offer()`, `optimum_lift_block_nav()`, `optimum_lift_find_bundle()`) only when `function_exists()`, since ticket 02 writes them in parallel.
- `woocommerce.php`: route `is_product()` → `template-parts/single-product/layout`, shop / product taxonomies → `template-parts/shop/archive`, fall back to `woocommerce_content()` when the part does not exist (`locate_template`).
- `functions.php`: require every `inc/` and `inc/shop/` file from the ownership table, guarded by `is_file()`; load `inc/cli.php` only under WP-CLI.
- JS: `main.js` importing all modules; `modules/track.js`, `countdown.js`, `menu.js` (overlay, focus trap, Escape, `aria-expanded`), `reveal.js`; stub modules (exporting a no-op `init`) for every other module in the ownership table.
- `languages/src/01.json` with your strings.

## Acceptance criteria

- [ ] `npm run build`, `npm run lint:php`, `npm run analyse:php` pass (ignore errors in files other tickets own that are still being written, and say so).
- [ ] Every page renders the dark background, Inter body text and Anton headings with no Google Fonts or CDN requests.
- [ ] The header matches the mock at 390px and 1440px; the mobile menu opens as an overlay without shifting layout, traps focus and closes on Escape and backdrop click.
- [ ] The urgency bar shows only with a real offer, counts down with days when more than 24 h remain, and disappears at zero.
- [ ] No WooCommerce stylesheet is enqueued on the front end.
- [ ] `grep -rn '\-ink\b' themes/optimum-lift --include=*.php` finds no Tailwind `ink` colour classes.

## Answer

Landed in `1adeef1` (wave-1): tokens and fonts in `theme.json`, `base.css`/`components.css`, header, footer, mobile menu, urgency bar, countdown, icons, Customizer settings, `inc/assets.php` dequeues and `window.optimumLift`, and the stubs for tickets 02–10. Re-checked 2026-09-24 at integration level only (build, PHPStan, HTTP smoke test with no PHP errors); ticket 11 reviews it in depth. Known follow-up: `optimum_lift_has_sticky_bar()` ignores whether the buy bar renders (fixed in 05b).
