# Thank-you page: success hero, next steps, Plans, cross-sells, purchase tracking

Type: task
Status: resolved
Wave: 2
Parent: 10
Blocked by: 10c

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. This ticket shares `woocommerce.css` with 10c, which is why it waits for 10c.

## What to build

**`woocommerce/checkout/thankyou.php`**: copy `plugins/woocommerce/templates/checkout/thankyou.php` (`@version 8.1.0`) and keep the header.

- **Success hero** with the page's `h1`: "Thank you, %s!" using the billing first name. `page.php:27` deliberately skips its own `h1` on the order-received page, so this one must be there. Include the order number, the date and the total.
- **What happens next:**
  - The confirmation email arrives within minutes.
  - For a new account, an email to set the password. The Plans plugin creates the account for guest orders (`plugins/optimum-lift-plans/src/Access/OrderAccess.php:115-153`).
  - Where to open the Plans: `wc_get_account_endpoint_url('plans')`.
- **Order details:** keep the `woocommerce_thankyou` hook, which prints `woocommerce_order_details_table` at priority 10. The Plans plugin prints `<section class="ol-order-plans">` after that table (`src/Download/Delivery.php:171-206`), with "Open" and "Download PDF" links. Nothing styles it yet, so style it here along with the order-details table and the customer details.
- **Cross-sells:** the union of `optimum_lift_cross_sells()` over the ordered Products, minus the Products bought, capped at 3. Use `compact` cards (`template-parts/product/card.php`) in the grid from `template-parts/single-product/cross-sells.php`.
- **Purchase payload:** print it for orders that haven't failed. `modules/track.js` reads it and tracks it once per browser:

  ```html
  <script type="application/json" data-ol-track="purchase">{"once": true, "transaction_id": "<order number>", "eventID": "<order key>", "order_key": "<order key>", "value": <total>, "currency": "EUR", "items": [{"id": 60, "name": "…", "price": 7.99}]}</script>
  ```

  Names go through `optimum_lift_plain_text()`. Build it with `wp_json_encode()`.
- **Failed-order branch:** keep WooCommerce's pay-again and account links, restyled.
- **`woocommerce.css`:** add a thank-you section (hero, next steps, order details, `.ol-order-plans`).
- **`woocommerce/README.md`:** add the override.
- **`languages/src/10d.json`:** your strings.

The order-received page shows the full site header and footer without the urgency bar (`header.php:18`). Leave that as it is.

## Files you own

- `woocommerce/checkout/thankyou.php`
- `assets/src/css/woocommerce.css` (thank-you section)
- `woocommerce/README.md`
- `languages/src/10d.json`

## Acceptance criteria

- [x] With COD temporarily enabled (see 10a), a logged-out guest order for 60 reaches a styled thank-you page. Check the hero with the first name, the next steps, the order table, the **Plans section** (60 is linked to a Training Plan) and the cross-sells. Screenshot it at 390px and 1440px.
- [x] `dataLayer` gets exactly one `ol_purchase` with the order key as `event_id`. Reloading the page doesn't push a second one.
- [x] A failed order (set with `wp wc shop_order update <id> --status=failed --user=1`) shows the styled failed branch, with no purchase payload.
- [x] COD is disabled again. Lint and PHPStan pass, and `10d.json` parses.

## Answer

Built (2026-09-24): `woocommerce/checkout/thankyou.php` (`@version 8.1.0`), a thank-you section in `woocommerce.css`, a README row and `languages/src/10d.json` (14 strings).

The success page, in order:
1. **Hero**: acid check, h1 "Thank you, {first name}!" ("Thank you!" without one), and a lead line. WooCommerce's overview list is kept and restyled as a 4-cell grid: number, date, total, method.
2. **What happens next**: email; set your password (only when the order has an account and the visitor isn't logged in); open your plan, linking to `wc_get_account_endpoint_url('plans')`.
3. **Gateway text and `woocommerce_thankyou`**: the order table, then the Plans plugin's `.ol-order-plans` with "Open" as the acid primary and "Download PDF" as a ghost button, then customer details.
4. **Cross-sells**: the union of `optimum_lift_cross_sells()` over the bought Products, without anything bought or covered by a bought bundle, capped at 3, as `compact` cards (`cta_prefix` `thankyou`).
5. **Purchase payload**: `wp_json_encode` with `once`, `transaction_id`, `eventID` = `order_key` = the order key, `value`, `currency` and `items` (plain-text names, prices including tax).

**The failed branch** gets its own card with the h1 "Your payment did not go through", WooCommerce's message and its Pay / My account links as buttons. It prints no payload and no cross-sells.

**Deviation: one file outside this ticket's list.** I edited `inc/shop/checkout.php` (owned by resolved 10a/10c) because the acceptance criterion was blocked. The Plans plugin creates an account for every guest order, so WooCommerce ≥ 8.4 treats the order as a known shopper's. The buyer then saw "Please log in to your account to view this order" and a login form instead of the thank-you page (order 79). The new `woocommerce_order_received_verify_known_shoppers` filter returns false only when all of these hold:
- the order key matches;
- this browser's WooCommerce session has the order's billing email;
- the order is inside WooCommerce's own `woocommerce_order_email_verification_grace_period` (10 minutes).

That's the same allowance WooCommerce gives guests. Anyone else still gets the login wall.

Evidence (Playwright, logged-out guest, COD enabled temporarily):

- Order 80 via `/?ol_buy_now=60` → `/checkout/order-received/80/…`. h1 "THANK YOU, ARTA!", three next steps, the order table, **Your Plans: "Plani 12-javor: forcë dhe çeliçim — Open · Download PDF"**, and cross-sells 62, 63, 64. Screenshots at 390px and 1440px.
- `dataLayer` has exactly one `ol_purchase` with `event_id: "wc_order_dDWAtVGYzKWMr"`, `value 7.99`, `items [{id:60,…}]`. After a reload: 0 new pushes.
- Order 81 set to `failed` with `wp wc shop_order update 81 --status=failed` → h1 "YOUR PAYMENT DID NOT GO THROUGH", a red card, a "Paguani" button, no `script[data-ol-track=purchase]`, 0 pushes.
- COD disabled again (no gateway enabled). lint exit 0; PHPStan OK; build OK; `10d.json` parses; `debug.log` unchanged.

Follow-ups:
- **Plans plugin**: a failed order still shows "Your Plans" with working links. Access granted at `processing` isn't revoked when the order moves to `failed`.
- Test orders 79–81 (guest accounts `guest…@example.com`) remain in the local database.
