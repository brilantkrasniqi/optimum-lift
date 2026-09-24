# Review and fix: behaviour over HTTP and in the browser

Type: task
Status: resolved
Wave: 3
Parent: 11
Blocked by: 11a

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Run the 11 sub-tickets one at a time.

## What to do

Exercise every purchase path with a cookie jar (`curl -c jar -b jar`) and with Playwright, looking for broken states, not happy paths:

- **Buy Now:** `?ol_buy_now=` with an empty cart, a full cart, a bundle, an invalid id, and a Product that's out of stock.
- **Add to cart:** the drawer (AJAX) and `?add-to-cart=` without JS. Also a double click, and adding a Product that's already in the cart.
- **Drawer upsells:** the ladder for every one- and two-Product cart (09c's matrix), then swap, remove, and emptying the cart.
- **Bundle rules:** on both paths.
- **Coupon:** `?ol_coupon=` valid, invalid, expired and already applied; applied after Buy Now and after an add; removed at checkout.
- **Checkout:** the fields for a cart that needs no shipping; validation errors; country change; coupon.
- **Thank-you:** with COD temporarily enabled, check the Plans grant, the purchase payload, and that a reload doesn't repeat the payload. Disable COD afterwards.
- **No JavaScript:** every path above that doesn't need JS.
- **Cached page:** load a cached-looking page with a full-cart cookie, and check the drawer refreshes its fragments.

Verify each finding before fixing it. Fixes may touch any file.

## Acceptance criteria

- [x] The Answer has a findings table (path, steps, expected, actual, verified, fix) and the curl/Playwright scripts used. Keep the scripts in the scratchpad, not the repo.
- [x] Every verified finding is fixed or explicitly deferred with a reason.
- [x] Build, lint and PHPStan pass, and `debug.log` is clean.

## Answer

Reviewed 2026-09-24. The scripts stay in the scratchpad:

- **curl** (a cookie jar per scenario): `C:\Users\Work\.claude\jobs\ee423a8e\tmp\11b\lib.sh`, with `parse.cjs` (summarises a wc-ajax answer: ok, count, added, notice, upsell type → Product and label) and `notices.cjs`. The scenarios were run inline from that library.
- **Playwright**, in `C:\Users\Work\.claude\jobs\dd052601\tmp\pw\w11\`:
  - `order.mjs`: Buy Now → checkout → COD order → thank-you → reload.
  - `nojs.mjs`, `nojs2.mjs`: JavaScript off.
  - `cached.mjs`: a cached page with a full-cart cookie.
  - `drawer.mjs`: complement → upgrade → remove in the drawer.

| Path | Steps | Expected | Actual | Verified | Fix |
| --- | --- | --- | --- | --- | --- |
| Buy Now | `?ol_buy_now=60`, empty cart | 302 to checkout, cart = 60 | Same | — | — |
| Buy Now | Cart 60 + 62, then `?ol_buy_now=61` | Cart = 61 only | Same | — | — |
| Buy Now | Cart 61, then `?ol_buy_now=64` | Cart = 64 | Same | — | — |
| Buy Now | `=99999`, `=abc`, `=1` (a post) | Back to the shop with "no longer available"; the old cart kept | Same; cart 60 kept | — | — |
| Buy Now | 63 set out of stock | Back to 63 with "can't be bought right now"; cart untouched | Same | — | — |
| Add (no JS) | `?add-to-cart=60` twice | Added, then "already in your cart" | Same | — | — |
| Add (AJAX) | 60, then 60 again | `ok`, `added:false`, no notice | Same | — | — |
| Add (AJAX) | 1, 0, 63 out of stock; a GET request | `ok:false` with a notice; 405 for GET | Same | — | — |
| Double click | Two concurrent adds, and `dblclick` in the browser | One line | One line (sold individually); `cart.js` ignores clicks while `aria-busy` | — | — |
| Upsell ladder | Every one- and two-Product cart of 60–63, plus 64 and 60+64 | 09c's matrix | [60] complement→62; [61] upgrade; [62], [63] complement→60; [60 61] swap, save 1,99; [60 62] upgrade (+0,01); [60 63], [61 63], [62 63] upgrade; [61 62] swap, save 0,99; [64], [60 64] none | — | — |
| Swap / remove | Swap with the fragment's nonce; without a nonce; to a non-bundle. Remove twice, then remove all | Swap to 64 alone; "session expired"; "no longer available"; idempotent remove; empty state | Same. The nonce from the server-rendered page and from fragments both work | — | — |
| Bundle rules | 64 in the cart, then add 62 via AJAX and via `?add-to-cart=` | Refused with "already included in Transformimi Total" | Same on both paths. 64 added over 60 + 62 removes them on both paths | — | — |
| Coupon | `?ol_coupon=` valid (empty cart, then add), lowercase, full cart; invalid, expired, used up, again; then Buy Now; then a no-JS add; with other query args | Stored and applied on add; invalid ones ignored silently; the argument stripped, other args and `#` kept | Same | — | — |
| **Coupon, minimum spend** | Temporary coupon with a 12 € minimum. Cart 7,99, `?ol_coupon=`, then add 62 (14,98) | Applied at that add | **Not applied.** `optimum_lift_apply_stored_coupon()` runs on `woocommerce_add_to_cart` and validated against totals from before the add (7,99) | Yes: AJAX and no-JS both failed | `calculate_totals()` before `is_coupon_valid()` (`inc/shop/coupon.php`). After: applied on both paths (13,48 €). Dropping below the minimum removes it, and it is forgotten (by design). OPTIMUM10 still applies |
| Coupon at checkout | Remove it (`wc-ajax=remove_coupon`), then add a Product | It doesn't come back | Same | — | — |
| Checkout | Buy Now 60 | Only `billing_email`, `billing_first_name`, `billing_last_name`, `billing_country` | Same | — | — |
| Checkout | Submit empty; bad email; valid with no gateway; country → DE | Field errors; email error; "invalid payment method"; review fragments refresh | Same | — | — |
| **Checkout coupon, no JS** | JavaScript off, open checkout | A way to enter a code | **None.** WooCommerce prints `form.checkout_coupon` with inline `display:none` and opens it from JS. The form itself posts and applies fine | Yes (`nojs.mjs`; a curl POST applies it) | Without `html.js`: form shown, toggle hidden (`woocommerce.css`). After: applied, 7,19 € (screenshot `11b\coupon-nojs-after.png`). With JS nothing changes |
| Thank-you | COD on; order 85 (60) through the browser | `processing`; Plan granted; `purchase` tracked once | `processing`; `wp_ol_access` row 11 (user 9, Plan 31, order 85); dataLayer `ol_purchase` and `fbq Purchase` with `eventID` = order key; the Plans section on the page. **Reload:** no `purchase` in dataLayer, 0 `fbq` calls | — | — |
| Checkout, no JS | Form POST to `/checkout/` with `woocommerce_checkout_place_order` | Order placed | 302 to order-received 86, `processing` | — | — |
| No JS | Product page | Menu and drawer buttons hidden, `/cart/` link shown, `.reveal` visible, add via href, FAQ answers readable, tab panels all shown | Same. Cart page: add, remove, coupon all work | — | — |
| Cached page | `/shop/` HTML for an empty cart, served to a visitor with 60 + 62 | The drawer refreshes | One `get_refreshed_fragments` call; badges 2, two lines | — | — |

Deferred, with reasons:

- **No-JS homepage sticky CTA always shown.** Without JS the bar is always visible. It is a working link, and the footer spacer keeps content clear, so it's left as the no-JS fallback.
- **`/cart/?add-to-cart=62` keeps its query argument.** This is WooCommerce's own no-JS behaviour; a reload only prints "already in your cart".
- **wc-ajax calls take about 1.7 s locally.** That's the Docker-on-Windows stack; see 11e for weights.

Restored: COD disabled again (`enabled: no`), 63 back in stock, the three temporary coupons deleted. Test orders 85 and 86 stay, like earlier tickets' test orders. Build, lint (exit 0, warnings only) and PHPStan pass; `debug.log` has no new lines (157).
