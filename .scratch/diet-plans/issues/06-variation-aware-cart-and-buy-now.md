# Variation-aware cart, Buy Now and helpers

Type: task
Status: ready-for-agent
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
