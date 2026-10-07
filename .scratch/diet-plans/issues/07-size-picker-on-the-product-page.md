# The Size picker on the Product page

Type: task
Status: ready-for-agent
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
- [ ] On `plani-ushqimor-12-javor`: with nothing chosen, both buttons in the price box and in the buy bar submit nothing, and the browser points at the first empty group, which is not hidden under the sticky header.
- [ ] With JavaScript: Add opens the drawer with the chosen Size, and Buy Now goes to checkout with it.
- [ ] Without JavaScript (disable it in devtools): Add reloads with WooCommerce's added notice and the chosen variation in the cart, and Buy Now goes to checkout with it.
- [ ] `?attribute_pa_gjinia=femer&attribute_pa_pesha=60-70` pre-checks both groups. An invalid value checks nothing.
- [ ] `dieta-mesdhetare` shows a single Pesha group.
- [ ] With one variation trashed:
  - its combination is disabled with JavaScript;
  - without JavaScript, choosing it gives "This size is not available." from the server.
- [ ] Keyboard only: Tab reaches each group, the arrow keys move the choice, and Buy Now submits. The accessibility tree shows each radio's name and its group's legend.
- [ ] On a diet that also has an "included versions" list, the versions still read as included (check marks, not controls) and the picker reads as a choice. Put a screenshot in the comment.
- [ ] At 360 px wide: the pills wrap, there is no horizontal scroll, and the buy bar's summary and buttons fit.
- [ ] `npm run build`, `npm run lint:php` and `npm run analyse:php` pass.

## Comments
