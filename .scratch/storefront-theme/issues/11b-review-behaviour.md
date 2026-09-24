# Review and fix: behaviour over HTTP and in the browser

Type: task
Status: ready-for-agent
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

- [ ] The Answer has a findings table (path, steps, expected, actual, verified, fix) and the curl/Playwright scripts used. Keep the scripts in the scratchpad, not the repo.
- [ ] Every verified finding is fixed or explicitly deferred with a reason.
- [ ] Build, lint and PHPStan pass, and `debug.log` is clean.
