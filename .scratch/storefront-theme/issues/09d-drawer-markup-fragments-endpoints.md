# Cart drawer: server-rendered markup, fragments and the three `wc-ajax` endpoints

Type: task
Status: resolved
Wave: 2
Parent: 09
Blocked by: 09a, 09c

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Architecture: ADR-0007. Spec: "Cart (09)" and "Markup contracts". Mock: `C:\Users\Work\Desktop\Projects\ol-design\cart.js`. Read every `[PORTABLE AS-IS]` / `[SPEC ONLY — REBUILD]` note in it, and BUILD-BRIEF.md §6.

## What to build

**Shell.** Print it on `wp_footer`, except when `optimum_lift_is_checkout_chrome()` (`inc/template-tags.php:64`) or WooCommerce is missing:

```html
<div class="olc-backdrop" data-cart-close></div>
<aside id="ol-cart-drawer" class="olc-drawer" role="dialog" aria-modal="true" aria-labelledby="ol-cart-title" inert>
  head: <h2 id="ol-cart-title">Your cart</h2> + <button type="button" data-cart-close aria-label="Close cart">
  <div data-cart-notice role="status" aria-live="polite"></div>   (never replaced by fragments)
  <div class="olc-body-inner">…</div>
  <div class="olc-foot-inner">…</div>
</aside>
```

**Body fragment** (`template-parts/cart/body.php`):
- **Lines** (`line.php`), each with:
  - the thumb (`template-parts/product/thumb.php` with small `class`/`icon_size` overrides);
  - `optimum_lift_category_label()`;
  - the name;
  - the price: `wc_price(optimum_lift_current_price())`, plus a `<del>` of `optimum_lift_anchor_price()` when there's a saving;
  - `<button type="button" data-cart-remove="{cart_item_key}" data-nonce="…">` labelled "Remove %s".
- **Then exactly one upsell box** (`upsell.php`) when `optimum_lift_cart_upsell()` (09c) returns one; otherwise nothing. Copy comes from the mock, in English source with the mock's Albanian in the JSON. Names and prices come from the Products; never hard-code "Transformimi Total".
  - `swap`: label "Ofertë më e mirë". Text: "**{bundle}** i përmban të gjitha programet dhe të gjitha dietat për **{bundle price}** — më lirë se sa ke në shportë." Button `data-cart-swap="{bundle_id}"`: "Kalo te paketa e plotë dhe kurse {amount}".
  - `complement`: label "Shkon bashkë me këtë".
    - Text for a program in the cart: "Stërvitja pa plan ushqimor ecën përgjysmë. Shto **{name}** dhe mbylle sistemin."
    - Text for a diet: "Ushqimi pa stërvitje ecën përgjysmë. Shto **{name}** dhe mbylle sistemin."
    - Button: `<a href="{add_to_cart_url()}" data-add-to-cart="{id}" data-cta="drawer-complement">`, "Shto {name} — {price}".
  - `upgrade` (new copy): "Vetëm +{amount} më shumë për gjithçka", with the bundle name and its anchor value. Button: `data-cart-swap="{bundle_id}"`.
- **Empty state** (`empty.php`): "Shporta jote është bosh. Zgjidh një program ose një plan ushqimor dhe fillo sot." plus a link to the shop, "Vazhdo të shfletosh produktet".

**Foot fragment** (`foot.php`, empty when the cart is empty):
- "Vlera pa ulje": the anchors' sum, struck. Only when it is greater than the total.
- "Kursen" (acid).
- A coupon row when `WC()->cart->get_discount_total() > 0`.
- The total.
  - **Deviation:** the total is `WC()->cart->get_total('edit')`, not the subtotal the spec says, so the exit-intent coupon (07b) shows in the drawer. Record this in your Answer for 13.
- `<a class="btn btn-primary btn-block" href="{checkout}" data-cta="cart-checkout" data-begin-checkout='{"value":…,"currency":…,"items":[{"id","name","price"}]}'>`, "Vazhdo te pagesa".
- "Vazhdo të shfletosh" (continue browsing: a shop link with `data-cart-close`).
- The trust line: `template-parts/product/trust-line.php`.

**Count badge.**
- Move the class string at `template-parts/header/site-header.php:18` into a helper such as `optimum_lift_cart_badge_html(int $count): string`, and use it in the header too.
- Visibility is the **`is-on`** class through `[&.is-on]:grid`. Don't port the mock's `.olc-badge`/`.on` CSS.
- The header has two `span[data-cart-count]`: the no-JS cart link and the drawer toggle.

**Fragments.** Hook `woocommerce_add_to_cart_fragments`:
- unset `div.widget_shopping_cart_content` (WooCommerce always renders a mini-cart there, `class-wc-ajax.php:262-280`);
- add `div.olc-body-inner`, `div.olc-foot-inner` and `span[data-cart-count]`.

**Endpoints**, `wc_ajax_ol_add_to_cart`, `wc_ajax_ol_remove_from_cart` and `wc_ajax_ol_swap_to_bundle`: POST only, `wp_send_json()`.
- `ol_add_to_cart` (`product_id`):
  - Already in the cart → `ok: true`, no change, fresh fragments.
  - Otherwise, apply `woocommerce_add_to_cart_validation` **yourself** (`WC_Cart::add_to_cart()` doesn't; `WC_AJAX` does at `class-wc-ajax.php:520`), so 09a's bundle rules run. Then call `WC()->cart->add_to_cart()`.
  - Collect `wc_get_notices()` and call `wc_clear_notices()`. Errors and "already included" notices become `notice` HTML with `ok: false`. The coupon's success notice may ride along as a non-error notice.
  - Return `{ok, notice, fragments, cart_hash, count, item: {id, name: optimum_lift_plain_text(name), price: optimum_lift_current_price(), currency}}`.
- `ol_remove_from_cart` (`cart_item_key`, `nonce`).
- `ol_swap_to_bundle` (`bundle_id`, `nonce`): add the bundle. 09a's cleanup removes the covered lines. On failure, leave the cart unchanged.
- **Nonces.** Remove and swap verify `wp_verify_nonce($nonce, 'ol-cart')`. The nonce is printed inside the fragments, which are always fetched fresh over AJAX, so a page cache can't stale it. Add takes no nonce: it matches WooCommerce's own `add_to_cart` and keeps cached page HTML working.

**`assets/src/css/drawer.css`:**
- Port the mock's §4 CSS to tokens: `.olc-backdrop`, `.olc-drawer`, `.olc-head`, `.olc-title`, `.olc-close`, `.olc-body`, `.olc-item*`, `.olc-thumb`, `.olc-rm`, `.olc-empty*`, `.olc-up*`, `.olc-foot`, `.olc-row`, `.olc-saved`, `.olc-checkout`, `.olc-cont`, `.olc-reassure`, and the reduced-motion rule.
- Token mapping: `#0D1012` → `surface`, `#14181B` → `raised`, plus `accent` and `acid`.
- Open state is `html.olc-open`, already defined with `overflow:hidden` at `base.css:34-37`. Use z-index 70/71, above the exit modal (60), the menu (55/56) and the header (40).

## Files you own

- `inc/shop/cart.php`
- `template-parts/cart/{drawer,body,line,upsell,empty,foot}.php`
- `assets/src/css/drawer.css`
- `template-parts/header/site-header.php` (only the badge refactor)
- `languages/src/09d.json`

Already translated, so don't redefine: "Open cart", "View cart", "Secure payment" (01); "Instant access", "Save %s", "Value without discount:" (04); "Training program", "Meal plan", "Complete bundle" (02).

## Acceptance criteria

- [x] Cookie-jar curl `POST /?wc-ajax=ol_add_to_cart` with `product_id=60` returns `ok: true`, the three fragment keys, `count: 1` and `item`. A second call returns `ok: true` with no change.
- [x] With 64 in the cart, adding 62 returns `ok: false` and the "already included" notice.
- [x] With [61, 62], the body fragment shows the swap box, and `ol_swap_to_bundle` with the nonce leaves only 64. Remove or swap without a valid nonce returns `ok: false` and changes nothing.
- [x] The drawer HTML is absent on `/checkout/` and present on `/`, Products and the shop.
- [x] The header badges look identical before and after the refactor (screenshot).
- [x] `09d.json` parses.

## Answer

Built (2026-09-24):

- **`inc/shop/cart.php`**:
  - Helpers: `optimum_lift_cart_badge_html()`, `optimum_lift_cart_part()`, `optimum_lift_cart_fragments()`, `optimum_lift_cart_lines()`.
  - The `woocommerce_add_to_cart_fragments` filter unsets the mini-cart and adds the three keys.
  - The shell on `wp_footer`, except under checkout chrome.
  - The three `wc_ajax_*` endpoints, all through `optimum_lift_cart_endpoint_start()`: POST only (405 otherwise), a session cookie for new visitors, and the nonce `ol-cart` for remove and swap. Responses go through `optimum_lift_cart_respond()`, which calls `calculate_totals()`, sends notices through `wc_print_notices(true)` and adds a generic error when a failure raised no notice.
  - Add and swap apply `woocommerce_add_to_cart_validation` themselves.
- **`template-parts/cart/{drawer,body,line,upsell,empty,foot}.php`**, following the contracts in the ticket.
  - Upsell copy: *swap* and *complement* follow the mock. *upgrade* reads "Only +X more for everything" / "{bundle} includes every program and every diet, worth ~~anchor~~, for {price}." / "Switch to {bundle}".
  - The empty state's shop link is a `btn-ghost` inside the body, because the foot is hidden when the cart is empty.
- **`assets/src/css/drawer.css`**: mock §4 ported to tokens. There's also an `.olc-notice` style for the live region, and `visibility` added to the transition so a closed drawer can't be tabbed into even without `inert`. The foot hides via `.olc-foot:has(.olc-foot-inner.is-empty)`.
- **Header**: both badges now use `optimum_lift_cart_badge_html()`.
- **`languages/src/09d.json`**: 25 strings. I reused "This product is no longer available." (09b) and "Secure payment" (01) without redefining them.

Evidence (cookie-jar curl against `/?wc-ajax=…`):

- `ol_add_to_cart` 60 → `ok:true`, keys `div.olc-body-inner`, `div.olc-foot-inner`, `span[data-cart-count]`, `count:1`, `item {id:60, name, price:7.99, currency:"EUR"}`. A second call → `ok:true, added:false`, cart still `60`. GET → 405.
- With 64 in the cart, add 62 → `ok:false`, notice "Plani Ushqimor 12-Javor is already included in Transformimi Total in your cart.", cart `64`.
- Remove with `nonce=bad` → `ok:false` "Your session has expired…", cart unchanged. With the fragment's nonce → cart empty.
- [61, 62]: the body fragment shows "Better deal … Switch to the full bundle and save 0,99 €" (`data-cart-swap="64"`). Swap with a bad nonce → `ok:false`, cart `61,62`. With the nonce → `ok:true`, cart `64`.
- Drawer markup: present on `/`, the Product, `/shop/` and `/cart/`; absent on `/checkout/`.
- Header badge: the rendered `<span data-cart-count class="olc-badge … [&amp;.is-on]:grid is-on">N</span>` has exactly the class string from `HEAD`, printed twice. Screenshot at 390px shows the red count bubble on the toggle as before. I also took a drawer screenshot (390px, 1440px) with [61, 62].
- `09d.json` parses; lint exit 0; PHPStan OK; build OK; `debug.log` unchanged.

Deviation (for 13): the drawer's total is `WC()->cart->get_total('edit')`, not the subtotal, so a coupon from the coupon link (07b) shows in the drawer as a "Coupon discount" row and in the total. "You save" is Σ anchors − Σ current prices (Product discounts only). "Value without discount" shows when Σ anchors > total.

Notes for 09e:
- The drawer is printed with `inert`; remove it on open and set it again on close.
- `[data-cart-close]` is on the backdrop, the close button and the foot's "Keep browsing" link (a real shop link, so `preventDefault` it with JS).
- The remove and swap buttons carry `data-nonce`; `[data-begin-checkout]` holds the JSON payload for `begin_checkout`.
- `[data-cart-notice]` is outside the fragments.
