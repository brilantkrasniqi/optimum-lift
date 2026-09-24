# Review and fix: honesty rules (ADR-0008)

Type: task
Status: ready-for-agent
Wave: 3
Parent: 11
Blocked by: 11b

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Run the 11 sub-tickets one at a time.

## What to do

Check that every urgency or social-proof element in ADR-0008 disappears when its data doesn't support it. Push the data below each threshold using WP-CLI or the `optimum_lift_*` threshold filters in a temporary mu-plugin, check the element, and restore everything afterwards with `wp ol-shop seed` and by deleting the mu-plugin.

**Proof thresholds** (`inc/shop/proof.php`):
- Product rating: needs at least 3 reviews.
- Store rating.
- Sold count: at least 25.
- Recent orders: at least 10 in 24 h.
- Bestseller badge.
- Bundle share "X in 10".
- Customer count, including its floors.

**Offers** (`inc/shop/offer.php`):
- Expire the Product sale and the site `offer_ends_at`. The urgency bar, every countdown, the pricing countdown chip and "only tonight" must go, and no price may rise because a countdown ended.
- A bundle priced without a sale has no offer.

**Prices and tokens:**
- `{regular_price}`, `{saving}` and the struck anchors disappear when there is no saving.
- A phrase holding an empty token is dropped (the `" · "` rule).

**Other claims:**
- Guarantee copy follows `guarantee_days`, including 0.
- Payment badges follow the Customizer.
- "Blerje e verifikuar" appears only on WooCommerce-verified reviews.

Verify each finding before fixing it.

## Acceptance criteria

- [ ] The Answer has a table: rule, how it was pushed below threshold, element hidden yes/no, and the fix.
- [ ] The demo data is restored, with no temporary mu-plugin left in `mu-plugins/`.
- [ ] Build, lint and PHPStan pass.
