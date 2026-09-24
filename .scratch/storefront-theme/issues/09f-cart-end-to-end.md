# Cart end-to-end acceptance (plus the checks deferred from 05 and 07)

Type: task
Status: ready-for-agent
Wave: 2
Parent: 09
Blocked by: 05b, 07b, 09b, 09e

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`.

## What to do

Run ticket 09's acceptance criteria end to end through the real UI (Playwright), plus the checks earlier sub-tickets deferred here. Fix whatever fails in 09's files (`inc/shop/{cart,bundle,buy-now,upsell}.php`, `template-parts/cart/*`, `drawer.css`, `cart.js`). If the fix belongs in another sub-ticket's file, name that file and the change in the Answer.

Re-seed first if `on_sale` is false.

## Acceptance criteria

- [ ] **Upsell ladder, through the drawer.** Each cart shows the box listed:

  | Cart | Box |
  | --- | --- |
  | Program 60 alone | complement → 62 |
  | 61 alone | upgrade +6,00 € |
  | 60 + 62 | upgrade +0,01 € |
  | 61 + 62 | swap, "kurse 0,99 €" |

  Clicking the swap leaves only the bundle.
- [ ] **Bundle rules on both add paths** (drawer AJAX and `?add-to-cart=` with JS disabled):
  - Adding the bundle removes its components.
  - Adding a component while the bundle is in the cart changes nothing and says why.
- [ ] **Buy Now:** `?ol_buy_now=ID` with other items in the cart lands on checkout with only that Product.
- [ ] **05 (deferred):** "Bli tani" in 60's price box, the buy bar and the mobile menu reaches checkout with only 60.
- [ ] **07 (deferred):** follow the exit modal's CTA (or open `/?ol_coupon=OPTIMUM10`), then add a Product. The coupon shows applied in the drawer (discount row, lower total) and at checkout.
- [ ] **No price computed in JavaScript** (review `cart.js`).
- [ ] **JavaScript disabled:**
  - The add-to-cart URLs and Buy Now work.
  - The header shows the cart link to `/cart/`, not the drawer toggle.
  - The cart page lists the lines.
- [ ] **Clean run:** lint, PHPStan and build pass, and `debug.log` has no new lines.
- [ ] **Close-out:** resolve parents 05, 07 and 09 if every sub-ticket they list is resolved.
