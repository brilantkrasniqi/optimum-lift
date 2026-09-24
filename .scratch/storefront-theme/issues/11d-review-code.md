# Review and fix: code (escaping, CSRF, dead code, contract drift)

Type: task
Status: resolved
Wave: 3
Parent: 11
Blocked by: 11c

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Run the 11 sub-tickets one at a time.

## What to do

Review the whole theme (`git diff f87b422 -- themes/optimum-lift`) along with the storefront's hooks into the Plans plugin:

- **Escaping.** Escape at output: `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` for editor HTML. Token output from `optimum_lift_replace_tokens()` is already HTML: check it is never double-escaped, and never printed without having gone through that function.
- **State changes.** Check nonces and CSRF on everything that changes state: `ol_remove_from_cart`, `ol_swap_to_bundle`, the admin notices, and the `?ol_buy_now` / `?ol_coupon` GET handlers. For the GET handlers, confirm the risk is acceptable and write down why.
- **Input.** Sanitise and cast all input: `absint` and `wc_clean`, never raw `$_GET`/`$_POST`.
- **Dead code.** Remove leftover stubs, unused helpers, CSS classes nothing uses, and old BEM styles.
- **Contract drift.** Compare the code with the spec's "Helper API", "Markup contracts" and "JavaScript" sections. Update the code, or record the deviation for ticket 13.
- **Tooling.** PHPCS and PHPStan clean, `declare(strict_types=1)` in `inc/`, and no string-built Tailwind classes.
- **Colour classes.** `grep -rn '\-ink\b' themes/optimum-lift --include=*.php` finds no Tailwind `ink` colour classes.

Verify each finding before fixing it.

## Acceptance criteria

- [x] The Answer has a findings table (file:line, finding, verified, fix).
- [x] Every verified security finding is fixed.
- [x] Build, lint and PHPStan pass.

## Answer

Reviewed 2026-09-24: the theme at `git diff f87b422 -- themes/optimum-lift`, plus the storefront's hooks into the Plans plugin (the thank-you Plans section, `woocommerce_order_received_verify_known_shoppers`, the Portal CSS).

| file:line | Finding | Verified | Fix |
| --- | --- | --- | --- |
| `inc/shop/cart.php:189` | `(string) $_POST['cart_item_key']`: an array posted with a valid nonce logs "Array to string conversion" | Yes: `debug.log` line 158 (2026-09-24 13:58), from my test request | `is_string()` before `wc_clean(wp_unslash())`. Re-run: `ok`, no new log line |
| Superglobals, whole theme | Every read is typed and sanitised: `absint()` for `product_id`, `bundle_id` and `ol_buy_now`; `is_string` + `wc_clean(wp_unslash())` for `ol_coupon`, `key`; `is_string` + an allow-list for `orderby`; `wp_verify_nonce(wp_unslash())` for `nonce`. `absint()` of an array is 1, which resolves to the post "Hello world" and is refused as not a Product | Yes (`orderby[]`, `product_id[]`, `nonce[]` probed: no log lines) | — |
| `ol_remove_from_cart`, `ol_swap_to_bundle` | Nonce `ol-cart`, checked (11b: no nonce → "session expired", nothing changed) | Yes | — |
| `ol_add_to_cart` | No nonce, by design: it has to work on cached pages. WooCommerce's own `?add-to-cart=` and `wc-ajax=add_to_cart` have none either. A forged request can only add a Product to the visitor's cart, and they see and control it | — | Accepted |
| `?ol_buy_now=` (GET) | A third-party page can send a visitor here: it replaces their cart with one Product and shows checkout. Nothing is paid without the buyer's own "Place order"; ads and no-JS links need plain navigation (ADR-0007). The redirect uses `wp_safe_redirect()` to the checkout or the Product | — | Accepted: the worst case is an emptied cart |
| `?ol_coupon=` (GET) | A third-party page can store a valid coupon for a visitor. That only gives the visitor a discount they could type anyway; invalid codes are ignored, and the argument is stripped by a `wp_safe_redirect()` | — | Accepted |
| Admin notices (`bundle.php:139`, `checkout.php:74`) | Read-only: a per-user transient written on the bundle's own `acf/save_post`, and a `current_user_can('edit_pages')` page check. No action reads the request | — | — |
| `checkout.php:152` known-shopper bypass | Shows the order on thank-you without a login only when all hold: the URL has the order key (`hash_equals`), this session's billing email matches the order's, and the order is inside WooCommerce's 10-minute email-verification grace. That's the same window WooCommerce gives guests, so it doesn't widen access | Code read | Accepted |
| Escaping | Every `echo` of a variable traces to an escaped value: `esc_*`, `wp_kses_post` for `wc_price()` and cells, `wp_get_attachment_image()` output, `optimum_lift_icon()`, `optimum_lift_cart_badge_html()` (escapes inside), and fixed tag names (`$tag`, `$heading`, `$tile_tag`). `optimum_lift_replace_tokens()` and `optimum_lift_heading_html()` escape before adding markup, and nothing wraps their output in `esc_*` again (grep finds none) | Yes | — |
| Tokens printed without the function | Seeded pages show no literal `{token}` (11c `probe.mjs`/`tok.mjs`; the only `{string}` is a JSDoc comment in an inline script) | Yes | — |
| `inc/shop/product-data.php:31` | `optimum_lift_shop_setting()`: a fallback for when the Customizer module wasn't loaded (ticket 02 ran in parallel with 01). `inc/customizer.php` is always loaded now | Yes | Removed; the 6 callers use `optimum_lift_setting()` |
| `inc/template-tags.php:37` | `optimum_lift_entry_meta()` is unused and prints an old BEM `entry__meta` class | Yes: no caller in the theme or the plugin | Removed |
| `assets/src/css/components.css` | `.pill`, `.pill-acid`, `.pill-accent`, `.eyebrow--accent` are unused (the badges use utilities) | Yes: grep for the class names in every PHP/JS file | Removed; recorded for 13 as a spec change |
| Other BEM-style selectors | All in use: `ol-*` from the Plans plugin (`ol-status--*` is built from a variable in `portal/plan.php:69`), `woocommerce-*` from WooCommerce templates, `ph-photo--acid` and `eyebrow--acid` in the theme | Yes | — |
| Stubs and TODOs | None left (`stub`, `TODO`, `FIXME` greps are empty; "ticket NN" appears only in file headers) | Yes | — |
| Helper API | Every function the spec lists exists with its signature | Yes (script comparing the spec to `^function` definitions) | — |
| JS contract | `window.optimumLift` has `wcAjaxUrl`, `checkoutUrl`, `cartUrl`, `currency`; `ol:cart:updated` and `ol:cart:open` fire; `track.js` maps the four Meta events, CTAClick, gtag, `data-ol-track` and `once` | Yes | Drift recorded for 13: the dataLayer push carries `event_id` (GTM convention), not `eventID`, and drops `once`; the Pixel still gets `{eventID}` |
| Tooling | PHPCS exit 0 (warnings only), PHPStan "No errors", `declare(strict_types=1)` in every `inc/` file, no string-built Tailwind classes (grep: only ids are concatenated; `goal-tabs.php` picks full `grid-cols-*` names with `match`) | Yes | — |
| Colour classes | `grep -rn '\-ink\b' --include=*.php` finds no Tailwind `ink` utility | Yes | — |

Every verified security finding is fixed; there was one, the array input. Build, lint and PHPStan pass. Every page type answers 200, or 302/404 where expected, with no PHP notices. `debug.log` gained only the one line my own test request caused before the fix.
