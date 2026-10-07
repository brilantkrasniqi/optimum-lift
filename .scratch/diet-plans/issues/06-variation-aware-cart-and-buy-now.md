# Variation-aware cart, Buy Now and helpers

Type: task
Status: claimed
Blocked by: 05

## What to build

Server side only. Ticket 07 builds the picker on top of it. Read spec Decisions 9, 10 and 13–18. Before relying on what WooCommerce does, read these in the container's WooCommerce source and note what you found in the comment:
- `WC_Product_Variable::get_price()` and `get_regular_price()` in `view` context;
- `WC_Cart::add_to_cart()` for variations, and how it builds the cart ID;
- `WC_Form_Handler::add_to_cart_handler_variable()`;
- `WC_Product_Data_Store_CPT::find_matching_product_variation()`;
- `wc_update_total_sales_counts()`.

**Helpers, in `inc/shop/product-data.php` or a new `inc/shop/sizes.php` loaded with the other shop files:**
- `optimum_lift_needs_choice(WC_Product $p): bool`: the Product is variable.
- `optimum_lift_base_product(WC_Product $p): WC_Product`: the parent of a variation, else the Product itself.
- `optimum_lift_size_label(WC_Product $p): string`: for a variation, its attribute values' term names in the parent's attribute order, joined by " · " ("Mashkull · 80–90 kg"); `''` for anything else.
- `optimum_lift_resolve_variation(WC_Product $parent, array $request): ?WC_Product_Variation`. It takes a `variation_id` that must be a child of `$parent`, or `attribute_<name>` values limited to the parent's variation attributes and sanitised like WooCommerce does. It returns `null` when:
  - the choice is incomplete or matches no variation;
  - the variation is not published, purchasable and in stock;
  - the variation has an "Any …" attribute. Those are not supported; ticket 09 warns about them.

**Variations resolve to their parent** inside these helpers, so their callers need no change (Decision 17):
- `optimum_lift_product_kind()` (and with it `optimum_lift_is_bundle()` and `optimum_lift_category_label()`);
- `optimum_lift_bundle_components()`;
- anything reading ACF fields or cross-sells for a cart line.

Find the rest by following every consumer of `optimum_lift_cart_lines()` (`cart/body.php`, `cart/foot.php`, `inc/shop/upsell.php`, `inc/shop/checkout.php:238`) and the `checkout/review-order.php` override. List what you changed in the comment.

**Prices:**
- Confirm that `optimum_lift_current_price()` and `optimum_lift_anchor_price()` on a variable parent give the lowest variation price and lowest regular price. If they don't, use `get_variation_price('min', true)` and `get_variation_regular_price('min', true)` for variable Products.
- `optimum_lift_offer()` (`inc/shop/offer.php:51`): for a variable Product, the sale end is the earliest future `date_on_sale_to` among its on-sale, purchasable variations.

**Adding to the cart:**
- `optimum_lift_cart_add()` takes an optional variation. It runs `woocommerce_add_to_cart_validation` with `($passed, $parent_id, 1, $variation_id, $attributes)`, as WooCommerce's own variable handler does, then calls `add_to_cart($parent_id, 1, $variation_id, $attributes)`.
- `wc_ajax_ol_add_to_cart` also reads `variation_id` and `attribute_*`.
  - A variable Product without a resolvable choice answers `ok: false` and `needs_choice: true`, with the notice "Choose your size first.", or "This size is not available." when the choice was complete but matches nothing.
  - The response `item` keeps the parent's `id` and adds `variant` (the Size label).
- In `inc/shop/bundle.php`:
  - `optimum_lift_cart_has_product()` matches a line by `product_id` or `variation_id`.
  - The "already in your cart" check blocks only the **same** variation.
  - After a successful add (`woocommerce_add_to_cart`), any other line with the same parent is removed with the notice "Changed to %s" (the new Size label). That is Decision 15. Removing after the add, not during validation, means a failed add never loses the old line.
  - Bundle covering keeps working on parent IDs.
- `wc_ajax_ol_swap_to_bundle` with a variable bundle:
  - Find a cart line whose parent is one of the bundle's components and that has a variation.
  - Resolve the bundle's variation from that line's attribute values, and add it.
  - If no line gives a Size, answer `ok: false` and `needs_choice: true` with the bundle's URL. Ticket 08 stops showing the swap button in that case; this is the fallback.

**Buy Now (`inc/shop/buy-now.php`):**
- `?ol_buy_now=<parent ID>` reads `attribute_*` and `variation_id` from the same query.
- `?ol_buy_now=<variation ID>` is accepted directly.
- Resolve the variation **before** emptying the cart. A Buy Now with a missing or impossible choice redirects to the Product's permalink with the chosen `attribute_*` kept in the query and `#blej`. It shows "Choose your size first." or "This size is not available.", and leaves the cart as it was.
- `optimum_lift_buy_now_url($p)`: for a Product that needs a choice, its permalink plus `#blej`. For a variation, `/?ol_buy_now=<variation ID>`.

**Tracking:**
- The thank-you payload's items add `variant` from the line's variation (`woocommerce/checkout/thankyou.php:50`).
- `track.js` passes `variant` through to the dataLayer. Meta still gets the parent's ID in `content_ids`.

## Acceptance criteria

Checked on the owner's PC against the seeded demo. Give the exact URLs and steps in the comment.

- [ ] `/?ol_buy_now=<12-javor ID>&attribute_pa_gjinia=femer&attribute_pa_pesha=60-70` reaches checkout with exactly that Size. A cash-on-delivery order, set to Processing, gives the "Femër · 60–70 kg" placeholder on the thank-you page, in the email and in My Account › Downloads.
- [ ] `/?ol_buy_now=<variation ID>` does the same for that variation.
- [ ] Buy Now with no choice, with only Pesha, with `attribute_pa_pesha=99`, or with another Product's variation ID: back on the Product page at `#blej` with the right notice. A cart that held a Training Plan beforehand still holds it.
- [ ] The add endpoint (call it from the browser console with `fetch` and FormData): a Size adds it; the same Size again gives `added: false` and one line; a different Size leaves one line, the new Size, with "Changed to …".
- [ ] With a diet Size in the cart, adding the bundle replaces the diet line. With the bundle in the cart, adding a diet Size gives "already included".
- [ ] Swap to bundle with "Mashkull · 80–90 kg" of a component diet in the cart adds the bundle's "Mashkull · 80–90 kg".
- [ ] The drawer upsell for a cart holding only a diet variation proposes the same thing it does for the Simple diet on `main` (the Training Plan or the bundle).
- [ ] The offer timer on a variable diet counts down to its variations' sale end.
- [ ] Training Plan Products behave exactly as before: Buy Now, add, the drawer upsell, swap and remove.
- [ ] `npm run lint:php` and `npm run analyse:php` pass.

## Comments

### 2026-10-07 (Claude)

Built in the "Diets 06" commit; the boxes stay open until the owner's PC run below.

**What WooCommerce 11.1 does** (read in its source):
- `WC_Product_Variable` has no `get_price()`/`get_regular_price()` of its own: both read the parent's synced `_price`/`_regular_price` props, and the seed clears the parent's prices (ticket 05), so `get_regular_price()` is `''`. `optimum_lift_current_price()` and `optimum_lift_anchor_price()` therefore use `get_variation_price('min', true)` and `get_variation_regular_price('min', true)` for variable Products. `is_on_sale()` is the variable class's own, from the variation prices.
- `WC_Cart::add_to_cart($product_id, $qty, $variation_id, $variation)`: a variation ID passed as `$product_id` is mapped to its parent; a variable parent without a variation is refused ("Please choose product options…"); posted attributes are sanitised with `sanitize_title()` for taxonomy attributes and `html_entity_decode(wc_clean())` otherwise, and checked against the variation. The cart ID is `generate_cart_id($product_id, $variation_id, $variation, $data)`, so the old `find_product_in_cart(generate_cart_id($id))` check never matched a variation line.
- `WC_Form_Handler::add_to_cart_handler_variable()` runs `woocommerce_add_to_cart_validation` with `($passed, $product_id, $qty, $variation_id, $variations)` and then refuses a request without a non-empty `variation_id`. So `?add-to-cart=<parent>&attribute_pa_…=…` (the picker's no-JS form) never works on its own: `sizes.php` hooks `wp_loaded` at 19, just before WooCommerce's 20, fills in `variation_id` from the values, or stops the add with "Choose your size first." / "This size is not available.".
- `WC_Product_Data_Store_CPT::find_matching_product_variation()` matches published children only, and treats a variation's empty ("Any") value as matching anything. It is reached through `WC_Data_Store::__call`, which PHPStan cannot see, and "Any" must not match here, so `optimum_lift_resolve_variation()` compares each child's `wc_get_product_variation_attributes()` with the chosen values itself.
- `wc_update_total_sales_counts()` counts `$item->get_product_id()`, the parent, so best-seller and proof numbers stay per Product.

**Changed:**
- New `inc/shop/sizes.php` (loaded after `product-data.php`): `optimum_lift_needs_choice()`, `optimum_lift_base_product()`, `optimum_lift_base_id()`, `optimum_lift_size_label()`, `optimum_lift_resolve_variation()`, plus `optimum_lift_requested_size()`, `optimum_lift_size_complete()`, `optimum_lift_size_error()`, `optimum_lift_matching_variation()` and the `wp_loaded` pre-handler.
- `product-data.php`: `optimum_lift_field()` reads a variation's parent; `product_kind()`, `goal()`, `category_label()` read the parent's terms; `bundle_components()`, `find_bundle()` and `cross_sells()` work on the parent; `buy_now_url()` gives `permalink#blej` for a Product that needs a choice.
- `pricing.php`, `offer.php` (new `optimum_lift_sale_end()`).
- `cart.php`: `optimum_lift_cart_add($cart, $product, ?$variation)`, `optimum_lift_cart_item_payload()` (parent `id`, `variant`), `optimum_lift_cart_needs_choice()`; the add endpoint reads `variation_id`/`attribute_*` (or a variation ID as `product_id`); swap-to-bundle takes the Size of a component line.
- `bundle.php`: `cart_has_product()` matches `product_id` or `variation_id`; "already in your cart" only for the same Size; a new Size replaces the Product's other line with "Changed to %s" (`woocommerce_add_to_cart` at 5, after the add).
- `buy-now.php`: resolves before `empty_cart()`; failures go to `permalink?attribute_…#blej`.
- Consumers of `optimum_lift_cart_lines()`: `upsell.php` (line and component IDs, cross-sells by parent), `checkout.php` `cross_sells_for()` (owned IDs), `cart/foot.php` (begin_checkout items: parent `id` + `variant`). `cart/body.php`, `cart/line.php` and `review-order.php` need nothing: kind, category, thumb and prices already resolve. The Size on the line is ticket 08.
- `thankyou.php`: purchase items get `variant`; the "Complete your system" owned list uses parent IDs. `track.js` already sends the whole payload to the dataLayer and gtag; its comment now says so, and Meta's `content_ids` stay parent IDs.

Lint: `phpcs` 0 errors, `phpstan` no errors, `npm run build` ok. Known gap until ticket 08: a drawer upsell or card that offers a variable Product still sends its parent ID; the endpoints answer `needs_choice` with the Product's URL.

**PC test** (after `ol-shop seed`; IDs from `wp post list --post_type=product_variation --fields=ID,post_parent,post_excerpt`):
1. `/?ol_buy_now=<12-javor ID>&attribute_pa_gjinia=femer&attribute_pa_pesha=60-70` reaches checkout with that Size; pay cash on delivery, set the order to Processing; the thank-you page, the email and My Account › Downloads give the "Femër · 60–70 kg" placeholder.
2. `/?ol_buy_now=<variation ID>`: same.
3. With a Training Plan in the cart: `/?ol_buy_now=<12-javor ID>`, `…&attribute_pa_pesha=60-70`, `…&attribute_pa_gjinia=femer&attribute_pa_pesha=99`, `…&variation_id=<bundle variation ID>`: back on the Product at `#blej` with "Choose your size first." twice, then "This size is not available." twice; the Training Plan is still in the cart.
4. Console on any page:
   `const add = (b) => { const f = new FormData(); Object.entries(b).forEach(([k, v]) => f.append(k, v)); return fetch('/?wc-ajax=ol_add_to_cart', {method: 'POST', body: f}).then((r) => r.json()); };`
   then `await add({product_id: <12-javor ID>, attribute_pa_gjinia: 'mashkull', attribute_pa_pesha: '80-90'})` (`ok`, `added: true`, `item.variant`), the same again (`added: false`, one line), then `attribute_pa_pesha: '70-80'` (one line, notice "Changed to Mashkull · 70–80 kg").
5. Bundle: with a diet Size in the cart, add the bundle's same Size: the diet line goes. With the bundle in the cart, add a diet Size: "already included".
6. Swap: with "Mashkull · 80–90 kg" of a component diet in the cart, the drawer's switch button adds the bundle's "Mashkull · 80–90 kg".
7. The drawer upsell for a cart with only a diet Size matches `main`'s for the Simple diet.
8. Set a sale end on the 12-javor variations; the offer timer counts to the earliest.
9. A Training Plan: Buy Now, add, upsell, swap and remove as before.
