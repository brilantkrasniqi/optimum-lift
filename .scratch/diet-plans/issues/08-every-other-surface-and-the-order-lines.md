# Every other buy surface, and the Size on cart and order lines

Type: task
Status: ready-for-agent
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
