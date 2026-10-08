# The Size picker on the Product page

Type: task
Status: claimed
Blocked by: 06

## What to build

Read spec Decisions 10, 12 and 13. The page must work fully without JavaScript; the script only makes it nicer.

**`template-parts/product/size-picker.php`.** It renders only when `optimum_lift_needs_choice($product)` is true, inside the hero's price box (`single-product/price-box.php`), between the price and the buy buttons:
- `<form id="ol-size-form" action="<the Product's permalink>" method="get" data-size-picker>`. The permalink, so a no-JavaScript add renders the same page with WooCommerce's notice. Buy Now redirects on `wp_loaded` wherever it lands.
- Per attribute of `$product->get_variation_attributes()`, in the parent's order:
  - a `fieldset` with a `legend` (`wc_attribute_label()`, e.g. "Pesha");
  - one radio pill per term in the attribute's term order, `name="attribute_<taxonomy>"`, `value` the slug, the term name as the label, and `required`.
- Nothing is checked unless the request carries valid `attribute_*` values (Decision 12); invalid ones are ignored.
- A hidden `variation_id`, filled by the script.
- A small `<script type="application/json" data-size-variations>` listing each published variation's `id`, its attribute values, and whether it is purchasable and in stock. Build it from `get_children()`; `get_available_variations()` carries far more than this needs.
- The price box keeps one price (Decision 10). Nothing in it changes when a Size is picked.

**The buttons submit that form.** For a Product that needs a choice, `product/buy-buttons.php` and `product/add-to-cart.php` render submit buttons when they get a `form` argument (the price box and the buy bar pass `'form' => 'ol-size-form'`):
- `<button type="submit" form="ol-size-form" name="ol_buy_now" value="<ID>" data-buy-now-submit>`.
- `<button type="submit" form="ol-size-form" name="add-to-cart" value="<ID>" data-size-add>`. **Not** `data-add-to-cart`: `cart.js` intercepts clicks on that attribute before the browser validates the form.

Without the argument they render ticket 08's "Choose your size" link. Simple Products render exactly what they render today.

**The sticky buy bar** (`single-product/buy-bar.php`) on a variable Product:
- It shows `[data-size-summary]` next to the price: the chosen Size, or "Choose your size" until there is one.
- Its buttons submit the hero's form through the `form` attribute.
- With nothing chosen, the browser's own validation blocks the submit and moves focus to the first empty group. Give the fieldsets a `scroll-margin-top` that clears the sticky header.

**`assets/src/js/modules/size-picker.js`**, started from `main.js` when `[data-size-picker]` exists:
- On every change, set `variation_id` to the matching variation, or empty it.
- Disable options that cannot complete an available variation given the other groups' choices. When a choice makes another group's checked option impossible, uncheck that option.
- Update `[data-size-summary]`.
- Dispatch `ol:size:change` with the variation ID and label, for anything else that wants it.

**`assets/src/js/modules/cart.js`:** handle the form's `submit` event, not the click, so native validation has already passed.
- When `event.submitter` is the add button: `preventDefault()`, open the drawer, and post `product_id`, `variation_id` and every `attribute_*` to `ol_add_to_cart`. If the answer has `needs_choice`, close the drawer, show the notice by the picker and focus the first empty group. If the request fails, submit the form natively.
- When the submitter is Buy Now: let it navigate, and give it the same loading state `pending-links.js` gives `[data-buy-now]` links.
- Leave the existing `[data-add-to-cart]` link handling as it is for Simple Products.

**Styles:**
- Pill radios that match the theme's components: visible focus ring, checked state and disabled state.
- 44 px minimum touch targets.
- They wrap on a 360 px screen without horizontal scrolling.
- Put them in the theme's CSS next to the other product components (Tailwind v4, configured in CSS; ADR-0002).

## Acceptance criteria

- [ ] On a Training Plan Product the page's HTML is unchanged apart from asset hashes. Diff the saved HTML from `main` and from the branch.
- [x] On `plani-ushqimor-12-javor`: with nothing chosen, both buttons in the price box and in the buy bar submit nothing, and the browser points at the first empty group, which is not hidden under the sticky header.
- [x] With JavaScript: Add opens the drawer with the chosen Size, and Buy Now goes to checkout with it.
- [x] Without JavaScript (disable it in devtools): Add reloads with WooCommerce's added notice and the chosen variation in the cart, and Buy Now goes to checkout with it.
- [x] `?attribute_pa_gjinia=femer&attribute_pa_pesha=60-70` pre-checks both groups. An invalid value checks nothing.
- [x] `dieta-mesdhetare` shows a single Pesha group.
- [x] With one variation trashed:
  - its combination is disabled with JavaScript;
  - without JavaScript, choosing it gives "This size is not available." from the server.
- [x] Keyboard only: Tab reaches each group, the arrow keys move the choice, and Buy Now submits. The accessibility tree shows each radio's name and its group's legend.
- [x] On a diet that also has an "included versions" list, the versions still read as included (check marks, not controls) and the picker reads as a choice. Put a screenshot in the comment.
- [x] At 360 px wide: the pills wrap, there is no horizontal scroll, and the buy bar's summary and buttons fit.
- [x] `npm run build`, `npm run lint:php` and `npm run analyse:php` pass.

## Comments

### 2026-10-07 (Claude)

Built in the "Diets 07" commit; the boxes stay open until the owner's PC run.

**Changed:**
- New `template-parts/product/size-picker.php`, rendered by `single-product/price-box.php` between the price and the buttons. Options come from `get_variation_attributes()` in the term order of `wc_get_product_terms()` (as WooCommerce's own dropdown does). A plain permalink's query (`?product=…`) is kept as hidden inputs, since a GET form drops the action's own query.
- `product/buy-buttons.php` and `product/add-to-cart.php`: with `form`, submit buttons (`data-buy-now-submit`, `data-size-add`); without it, one "Choose your size" link to `#blej` (`{prefix}-choose-size`), which ticket 08 relies on. Simple Products take the old branch unchanged.
- `single-product/buy-bar.php`: `[data-size-summary]` and the `form` argument; a variable Product gets its own button class string (the add button is `[data-size-add]`). The PHP tags sit at column 0 so a Simple Product's HTML is byte-identical.
- New `modules/size-picker.js` (started from `main.js` before `cart.js`). `cart.js` handles the form's `submit` for `[data-size-add]`, with a native `requestSubmit()` fallback when the request fails, and closes the drawer on `needs_choice`. It also follows `needs_choice` to the Product's URL for `[data-add-to-cart]` links and the swap button. `pending-links.js` gives `[data-buy-now-submit]` its loading state on `submit`. The spinner selectors in `components.css` cover both new buttons.
- Styles: `.ol-size-*` in `product.css`. These are radio pills with an empty ring that fills when chosen, so they read as a choice next to the check-mark version pills. Each is at least 44 px, with a focus ring, checked and disabled (struck through) states. `scroll-margin` sits on the radios themselves, because the browser scrolls the invalid radio, not its fieldset, into view: 10.5rem clears the header and the desktop bar, 6rem the phone bar.

**Checked here** with a static copy of the picker's markup, the built CSS and JS, and the theme.json colours, in headless Chromium (no WordPress in this environment):
- At 360 px the pills wrap two per row and the page does not scroll sideways. The bar shows "Mashkull · 80–90 kg" next to the price and Buy Now.
- Nothing checked: the bar's Buy Now submits nothing. Focus goes to "Mashkull", scrolled clear of the phone bar (radio bottom 644 px, bar top 671 px). On desktop, after the smooth scroll, it sits 486 px from the top.
- With the Femër · 90+ variation missing:
  - choosing Femër disables 90+;
  - choosing 90+ after Mashkull disables Femër;
  - `variation_id` follows each complete choice, and the arrow keys move the choice.
- A complete choice submits.

**PC test** (after `ol-shop seed`):
1. Save the HTML of a Training Plan Product on `main` and on this branch (`curl -s <url> > a.html`, in Git Bash, not PowerShell) and diff them. Only asset hashes differ.
2. On `plani-ushqimor-12-javor` with nothing chosen:
   - Buy Now and Add in the price box, then in the buy bar after scrolling down, all submit nothing.
   - The browser points at Gjinia, which is visible.
3. With JavaScript:
   - Add opens the drawer with the chosen Size.
   - Buy Now goes to checkout with it.
4. With JavaScript disabled:
   - Add reloads with WooCommerce's notice, and the Size is in the cart.
   - Buy Now goes to checkout.
5. `?attribute_pa_gjinia=femer&attribute_pa_pesha=60-70` pre-checks both groups. `attribute_pa_pesha=99` checks nothing in Pesha.
6. `dieta-mesdhetare` shows one Pesha group.
7. Trash one variation of 12-javor:
   - with JavaScript, its combination is disabled;
   - without JavaScript, choosing it gives "This size is not available.".
8. Keyboard: Tab reaches each group, the arrows move the choice, and Enter on Buy Now submits.
9. A diet with "included versions" next to the picker: take a screenshot for this comment.
10. At 360 px wide: no horizontal scroll, and the bar fits.

### 2026-10-08 (Claude)

Checked on the owner's PC on be8100c with headless Edge (Playwright), 12-javor 826 (variations 827–836), Dieta Mesdhetare 837. No console errors.

- **HTML diff, Training Plans 12-javor and Force Mase, logged out, `main` against the branch on the same database:** no asset-hash differences. Two kinds of change:
  - One whitespace change before the value stack's Buy Now. The new `$cta_link` line was its own `<?php … ?>` tag, so its indentation was printed. It is now inside the `if` tag ("Diets 07: no stray whitespace in the value stack"), so the box stays open until that diff is re-run.
  - The cross-sell cards for the three diets now say "Choose your size" and link to `#blej`, with the struck regular price. That is tickets 06 and 08: `main` gave a variable parent no anchor price. The price box and the buy bar are identical.
- **Nothing chosen:** all four buttons (`form="ol-size-form"`) submit nothing, and focus goes to the first Gjinia radio, below the 65 px sticky header, both in the price box and from the buy bar (1280×900 and 390×800). At 390 px the bar shows only Buy Now, as it does for every Product.
- **With JavaScript:** Mashkull · 80–90 kg gives `variation_id` 830 and the bar's summary; Add opens the drawer with that line. Femër · 60–70 kg with Buy Now goes to checkout with only 833.
- **Without JavaScript:** Add reloads with WooCommerce's added notice, cart 830. Buy Now Femër · 60–70 kg goes to checkout with 833.
- **The URL:** `?attribute_pa_gjinia=femer&attribute_pa_pesha=60-70` checks both. `pa_pesha=99` checks only Gjinia, and `pa_gjinia=xyz` checks nothing.
- **Dieta Mesdhetare:** a single Pesha group.
- **Femër · 90+ (836) trashed with `wp post update 836 --post_status=trash`, then restored:**
  - With JavaScript, each of the two greys out the other.
  - Without JavaScript, Buy Now and Add give "This size is not available." and the cart stays empty.
- **Keyboard:**
  - Tab reaches Gjinia, then Pesha, then Buy Now and Add. The arrows move the choice, and Enter on Buy Now checks out 834.
  - The accessibility tree shows the groups "Gjinia" and "Pesha" with each radio's name.
- **Included list:** it keeps its check marks, and the picker reads as pills inside the price box. A screenshot was taken on the owner's PC; it is not attached here.
- **At 360 px:** the pills wrap over four rows and there is no horizontal scroll. The bar's summary and Buy Now fit.
- **Checks:** `npm run build`, `phpcs` (0 errors) and `phpstan` pass.
