# The drawer's upsell decision: `optimum_lift_cart_upsell()`

Type: task
Status: resolved
Wave: 2
Parent: 09
Blocked by: 05a

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Spec: "Cart (09)" (the ladder). ADR-0007: the decision is server-side, and native Cross-sells are its candidate source.

## What to build

`inc/shop/upsell.php` (new, already required by `functions.php` behind `is_file()`):

```php
/** @return array{type: 'swap'|'upgrade'|'complement', product: WC_Product, amount: float}|null */
function optimum_lift_cart_upsell(): ?array
```

Walk the spec's ladder in order, from the real `WC()->cart` lines:

0. The cart is empty (or there's no cart) → `null`.
1. A bundle is in the cart → `null`.
2. Let `bundle` be `optimum_lift_find_bundle()`, preferring the bundle that contains the cart's lines, and let *covered* be the lines that are components of that bundle. If the covered lines' `optimum_lift_current_price()` sum is ≥ the bundle's price → `swap`, product = bundle, amount = that sum − the bundle's price.
3. The cart holds one kind but not its complement (`optimum_lift_product_kind()`, `optimum_lift_complement_kind()`). Let `c` be the best complement:
   - the first Product of the complement kind in the lines' `get_cross_sell_ids()` order that is published, purchasable and not in the cart;
   - otherwise the best-seller of that kind.

   Then:
   - If subtotal + price(c) ≥ the bundle's price → `upgrade`, product = bundle, amount = the bundle's price − the covered subtotal.
   - Otherwise → `complement`, product = `c`, amount = price(c).
4. Otherwise, if a bundle exists and isn't in the cart → `upgrade` (amount = the bundle's price − the covered subtotal).

Also add `optimum_lift_bestseller_of_kind(string $kind): ?WC_Product`:
- modelled on `optimum_lift_bestseller_id()` (`inc/shop/proof.php:222`);
- loops `optimum_lift_query_products(['category' => [optimum_lift_kind_slugs()[$kind]], 'visibility' => 'catalog'])` (`product-data.php:127`, `:52`);
- picks the highest `get_total_sales()`, with ties going to `menu_order`;
- is cached with `optimum_lift_proof_cached('bestseller_' . $kind, …)` (`proof.php:23`).

Don't use `optimum_lift_sold_count()`: it returns null below 25 sales, so it's useless for ranking.

No user-facing strings, so there's no JSON file.

## Files you own

- `inc/shop/upsell.php`

## Acceptance criteria

- [x] Write a `wp eval-file` script (in the scratchpad, not the repo) that loads a cart in CLI (`wc_load_cart()`), fills it, prints `optimum_lift_cart_upsell()`, and empties it between cases. With the seeded sale prices (60: 7.99, 61: 8.99, 62: 6.99, 63: 5.99, bundle 64: 14.99) it must print:

  | Cart | Expected |
  | --- | --- |
  | [] | null |
  | [60] | complement → 62, 6.99 (7.99 + 6.99 = 14.98 < 14.99) |
  | [61] | upgrade → 64, 6.00 (8.99 + 6.99 ≥ 14.99) |
  | [62] | complement → 60, 7.99 |
  | [63] | complement → 60, 7.99 |
  | [60, 62] | upgrade → 64, 0.01 (rule 4; covered 14.98 < 14.99) |
  | [61, 62] | swap → 64, 0.99 |
  | [64] | null |

- [x] If a case differs because of how "best complement" is read, explain the reading in the Answer rather than bending the code to the table.

## Answer

Built `inc/shop/upsell.php` (2026-09-24):

- `optimum_lift_cart_upsell()`: the ladder, from the real cart lines.
- `optimum_lift_bestseller_of_kind(string $kind): ?WC_Product`: cached with `optimum_lift_proof_cached('bestseller_' . $kind, 12h, …)`. It stores the ID; ties go to menu order; with no sales at all, it returns the first Product in menu order.
- `optimum_lift_best_complement($lines, $kind, $line_ids)`.

How I read the ladder:

- *Bundle*: the first bundle that contains any line, else the first bundle. *Covered*: lines that are components of it.
- *Subtotal* in step 3: the sum of `optimum_lift_current_price()` over all lines. I used display prices, not `WC()->cart` totals, so coupons don't move the decision.
- *One kind*: the set of non-null kinds in the cart has exactly one member. Lines with no kind are ignored.
- *Best complement*: the lines' cross-sells in order, skipping anything in the cart or unpublished/unpurchasable, then the kind's best-seller.
- Step 4's amount is clamped at ≥ 0.

Evidence: `wp eval-file -` with a scratch script (`wc_load_cart()`, empty the cart, add, print) gives exactly the table:

```
[] => null
[60] => complement -> 62, 6.99
[61] => upgrade -> 64, 6.00
[62] => complement -> 60, 7.99
[63] => complement -> 60, 7.99
[60, 62] => upgrade -> 64, 0.01
[61, 62] => swap -> 64, 0.99
[64] => null
```

lint exit 0; PHPStan OK; `debug.log` unchanged.
