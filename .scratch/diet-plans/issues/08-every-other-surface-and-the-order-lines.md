# Every other buy surface, and the Size on cart and order lines

Type: task
Status: claimed
Blocked by: 07

## What to build

Read spec Decisions 14, 17 and 18. After ticket 07, only the Product's own page can ask for a Size. This ticket makes every other surface send the buyer there, and shows the Size wherever a line is listed.

**"Choose your size" links.** For a Product that needs a choice, rendered anywhere except its own picker:
- `product/add-to-cart.php` and `product/buy-buttons.php` without a `form` argument render one link instead of their buttons: "Choose your size" to `optimum_lift_buy_now_url($product)` (the permalink plus `#blej`), with `data-cta="<prefix>-choose-size"`. This covers:
  - the Product cards (`product/card.php:187`);
  - the bundle banner (`product/bundle-banner.php:97`);
  - the final CTA's add button (`blocks/final-cta.php:121`).
- `blocks/final-cta.php` (lines 49, 116, 133), `blocks/value-stack.php:180` and `header/mobile-menu.php` (lines 21, 87): when the href is not a Buy Now URL, drop `data-buy-now` and `rel="nofollow"`. `final-cta.php` already does this for `#…` hrefs. Keep their labels on the Product's own page, where the link leads to the picker. Use "Choose your size" when the Product is another page's.
- The drawer (`cart/upsell.php`):
  - The complement suggestion links to the Product's `#blej` with "Choose your size", without `data-add-to-cart`.
  - The bundle swap button renders only when the cart's lines give the bundle a Size (ticket 06's resolution, as a helper the template can call). Otherwise it links to the bundle's `#blej`.

**The Size on lines (Decision 17).**
- The drawer line (`cart/line.php`):
  - thumbnail, category, name and permalink come from `optimum_lift_base_product()`;
  - the Size label goes on its own line under the name (`.olc-item-size`);
  - the remove button's accessible label includes the Size.
- The checkout summary (`woocommerce/checkout/review-order.php`): the same.
- WooCommerce's own lines:
  - Set `woocommerce_product_variation_title_include_attributes` to `false`, so a variation's name, and with it the stored order item name, stays the diet's name.
  - WooCommerce then lists the attributes under it on the classic cart page, the order received page, order emails, My Account › Orders and the wp-admin order.
  - Check every one of these shows the Size exactly once, and style the item meta where it looks wrong in the theme's CSS.
- The thank-you cross-sells and "what you bought" read the parent.

**Archive, cards and structured data:**
- Check that a variable diet's card shows its one price, the saving, its badge and the sales proof like a Simple diet.
- Check that the shop's sort by price still orders it.
- Check that the Product page's JSON-LD (WooCommerce's own) is valid in Google's Rich Results Test or its schema validator.

Fix only what is wrong, and list what you checked in the comment.

## Acceptance criteria

On the seeded demo:

- [ ] The shop page, the front page and a Training Plan Product page show "Choose your size" on every surface for the variable diets and the bundle, and each link lands on that Product's picker. Their Simple Products' buttons are unchanged.
- [ ] Final CTA, value-stack and the mobile menu on the variable diet's own page lead to its picker.
- [ ] The drawer, with a Training Plan in the cart, suggests the diet with "Choose your size". With a diet Size and a Training Plan in the cart, it offers the swap to the bundle, and the swap gives the bundle the same Size.
- [ ] One order for a diet Size and a Training Plan shows the Size exactly once on the line on each of these:
  - the drawer;
  - the checkout summary;
  - the classic cart page;
  - the thank-you page;
  - the customer email and the admin email;
  - My Account › Orders and the order view;
  - the wp-admin order.
- [ ] The thank-you `purchase` payload has the parent's `id` and the `variant` for the diet line (read it in the page source).
- [ ] No PHP notice in `wp-content/debug.log` while doing all of the above.
- [ ] `npm run build`, `npm run lint:php` and `npm run analyse:php` pass.

## Comments

### 2026-10-07 (Claude)

Built in the "Diets 08" commit; the boxes stay open until the owner's PC run.

**Changed:**
- `product/buy-buttons.php` and `product/add-to-cart.php`: without `form`, a Product that needs a choice gets one "Choose your size" link to `optimum_lift_buy_now_url()` (`#blej`). This covers the cards, the bundle banner and the final CTA's add button.
- New `optimum_lift_buy_link()` in `sizes.php`: Buy Now, or the picker. On the Product's own page that is `#blej` and the label stays; elsewhere it is the page's `#blej` and the label becomes "Choose your size".
  - `blocks/final-cta.php` uses it. Without a Buy Now URL there is no `data-buy-now`/`rel`, and no add button for a sized Product, so the paired layout keeps one button.
  - So does `blocks/value-stack.php`.
- `header/mobile-menu.php`: on a sized Product's page the CTA goes to `#blej`, without `data-buy-now`, priced with `optimum_lift_current_price()`.
- `cart/upsell.php`: a sized complement links to its picker with "Choose your size". The bundle swap/upgrade stays a `data-cart-swap` button only when `optimum_lift_cart_bundle_size()` (moved out of the swap endpoint) finds a Size in the cart's lines; otherwise it links to the bundle's picker.
- `cart/line.php` and `checkout/review-order.php`: thumb, category, name and link come from the parent. The Size is on its own line (`.olc-item-size`, `.ol-review-size`) and in the remove button's label. The checkout summary passes the cart item to `wc_get_formatted_cart_item_data()` without its `variation`, so the Size is not printed twice. Other item data still shows.
- `woocommerce_product_variation_title_include_attributes` is now `false` (`sizes.php`), so variations and new order items carry the diet's name. WooCommerce's variation data store regenerates a variation's stored title on read and on save (`generate_product_title()` in `read()`, `create()` and `update()`), so the seeded variations follow without a re-seed.
- CSS: `.wc-item-meta` (order received, My Account) and `dl.variation` (classic cart) put each "Gjinia: Mashkull" pair on one line.

**Checked in code, no change needed:**
- Cards price through `optimum_lift_current_price()`/`anchor_price()` (ticket 06).
- The badge and sales proof read the parent's `total_sales`, which `wc_update_total_sales_counts()` increments.
- `product/price.php`, `value-stack.php` and `comparison.php` test `get_price() !== ''`. That is WooCommerce's synced `_price` on the parent, which the seed's sync fills.
- The shop's price sort uses WooCommerce's lookup table, which holds a variable Product's min and max price.
- The thank-you cross-sells use parent IDs (ticket 06).

**PC test** (after `ol-shop seed`, with `WP_DEBUG_LOG` on):
1. On the shop page, the front page and a Training Plan page, every card, banner and section for 12-javor, mesdhetare and the bundle says "Choose your size" and lands on that Product's picker. Simple Products are unchanged.
2. On 12-javor's own page, the final CTA, value stack and mobile menu go to `#blej`.
3. Drawer:
   - With only a Training Plan in the cart, the complement diet says "Choose your size".
   - With 12-javor "Mashkull · 80–90 kg" plus a Training Plan, the swap offers the bundle and adds its "Mashkull · 80–90 kg".
4. Order a diet Size and a Training Plan, paying cash on delivery, and set the order to Processing. The Size shows exactly once on each of these:
   - the drawer;
   - checkout;
   - the classic cart (`/cart/`);
   - the thank-you page;
   - both emails (WP Mail Logging or Mailpit);
   - My Account › Orders and the order view;
   - wp-admin.
5. View the thank-you page's source. The `purchase` items have the parent `id` and `variant`.
6. The Product page's JSON-LD passes https://validator.schema.org.
7. `debug.log` stays empty.
