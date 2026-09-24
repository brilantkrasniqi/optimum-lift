# Cart end-to-end acceptance (plus the checks deferred from 05 and 07)

Type: task
Status: resolved
Wave: 2
Parent: 09
Blocked by: 05b, 07b, 09b, 09e

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`.

## What to do

Run ticket 09's acceptance criteria end to end through the real UI (Playwright), plus the checks earlier sub-tickets deferred here. Fix whatever fails in 09's files (`inc/shop/{cart,bundle,buy-now,upsell}.php`, `template-parts/cart/*`, `drawer.css`, `cart.js`). If the fix belongs in another sub-ticket's file, name that file and the change in the Answer.

Re-seed first if `on_sale` is false.

## Acceptance criteria

- [x] **Upsell ladder, through the drawer.** Each cart shows the box listed:

  | Cart | Box |
  | --- | --- |
  | Program 60 alone | complement → 62 |
  | 61 alone | upgrade +6,00 € |
  | 60 + 62 | upgrade +0,01 € |
  | 61 + 62 | swap, "kurse 0,99 €" |

  Clicking the swap leaves only the bundle.
- [x] **Bundle rules on both add paths** (drawer AJAX and `?add-to-cart=` with JS disabled):
  - Adding the bundle removes its components.
  - Adding a component while the bundle is in the cart changes nothing and says why.
- [x] **Buy Now:** `?ol_buy_now=ID` with other items in the cart lands on checkout with only that Product.
- [x] **05 (deferred):** "Bli tani" in 60's price box, the buy bar and the mobile menu reaches checkout with only 60.
- [x] **07 (deferred):** follow the exit modal's CTA (or open `/?ol_coupon=OPTIMUM10`), then add a Product. The coupon shows applied in the drawer (discount row, lower total) and at checkout.
- [x] **No price computed in JavaScript** (review `cart.js`).
- [x] **JavaScript disabled:**
  - The add-to-cart URLs and Buy Now work.
  - The header shows the cart link to `/cart/`, not the drawer toggle.
  - The cart page lists the lines.
- [x] **Clean run:** lint, PHPStan and build pass, and `debug.log` has no new lines.
- [x] **Close-out:** resolve parents 05, 07 and 09 if every sub-ticket they list is resolved.

## Answer

End-to-end run through the real UI (Playwright, 390px, 2026-09-24). **No code changes were needed**; everything passed on the builds from 09a–09e, 05b and 07a/07b.

- **Upsell ladder, through the drawer after clicking the page's "Add to cart"**:
  - 60 → "Goes well with this … Add Plani Ushqimor 12-Javor — 6,99 €"
  - 61 → "Only +6,00 € more for everything"
  - 60 + 62 → "Only +0,01 € more for everything"
  - 61 + 62 → "Better deal … Switch to the full bundle and save 0,99 €"
  - Clicking the swap → Store API cart `64`, 14,99 €.
- **Bundle rules, drawer AJAX**: with 64 in the cart, adding 62 → cart unchanged, and `[data-cart-notice]` reads "Plani Ushqimor 12-Javor is already included in Transformimi Total in your cart." 60, then the bundle from the shop banner → cart `64`.
- **Bundle rules, JS disabled**:
  - `?add-to-cart=64`, then `?add-to-cart=62` → the page it lands on (`/`, which now prints notices) shows the "already included" notice; `/cart/` lists only Transformimi Total.
  - `?add-to-cart=60` with the bundle in the cart → unchanged.
  - 60, then `?add-to-cart=64` → `/cart/` lists only Transformimi Total.
- **Buy Now with other items in the cart**:
  - 60's price-box "Buy now" (with 62 in the cart) → `/checkout/`, cart `60`, 7,99 €.
  - The sticky buy bar (with 61 in the cart) → `/checkout/`, cart `60`.
  - The mobile menu's `?ol_buy_now=60` (with 62 in the cart) → `/checkout/`, cart `60`.
  - No JS: `/?ol_buy_now=61` → checkout with 1 line.
- **Coupon**: `/?ol_coupon=OPTIMUM10`, then add 60 through the drawer. The drawer foot shows "Value without discount 14,99 € · You save −7,00 € · Coupon discount −0,80 € · Total 7,19 €". Checkout shows "Kupon: optimum10 −0,80 €", total 7,19 €, and 7,19 € in the collapsed summary bar.
- **No price math in JS**: `cart.js` only swaps server fragments; the grep for `toFixed|Intl.|parseFloat|* N` finds nothing. The only "price" is in the header comment.
- **JS disabled**: the header shows the cart link to `/cart/` (visible) and hides the drawer toggle; `/cart/` lists the lines.
- **Clean run**: build OK, lint exit 0, PHPStan OK, `debug.log` unchanged (157 lines). No page errors in the run.
- **Close-out**: 05 and 07 were resolved with their last sub-tickets (05b, 07b). 09 is resolved now.
