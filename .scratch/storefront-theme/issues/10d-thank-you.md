# Thank-you page: success hero, next steps, Plans, cross-sells, purchase tracking

Type: task
Status: ready-for-agent
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

- [ ] With COD temporarily enabled (see 10a), a logged-out guest order for 60 reaches a styled thank-you page. Check the hero with the first name, the next steps, the order table, the **Plans section** (60 is linked to a Training Plan) and the cross-sells. Screenshot it at 390px and 1440px.
- [ ] `dataLayer` gets exactly one `ol_purchase` with the order key as `event_id`. Reloading the page doesn't push a second one.
- [ ] A failed order (set with `wp wc shop_order update <id> --status=failed --user=1`) shows the styled failed branch, with no purchase payload.
- [ ] COD is disabled again. Lint and PHPStan pass, and `10d.json` parses.
