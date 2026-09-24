# Homepage: exit-intent modal and the coupon hand-off

Type: task
Status: resolved
Wave: 2
Parent: 07
Blocked by: 07a

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. This ticket waits for 07a because both edit `home.css`.

## What to build

- **`assets/src/js/modules/exit-intent.js`** is a stub. It drives `template-parts/home/exit-modal.php:42-62`, where the markup is:
  - `div[data-exit-modal][hidden]`: the fixed full-screen overlay. A click whose target is this element is a backdrop click.
  - `div[role=dialog][aria-modal][aria-labelledby=ol-exit-title][aria-describedby=ol-exit-text]`: the dialog inside it.
  - Two `button[data-exit-close]`: the X icon and "No thanks…".
  - `a[data-exit-cta][data-cta=exit-modal]`: the CTA, with `href` `/?ol_coupon=CODE#<pricing anchor>`.

  The JS must:
  - Arm only when `matchMedia('(pointer: fine)').matches`, and only after 8 s on the page.
  - Open on a document `mouseout` where `!e.relatedTarget && e.clientY <= 0`.
  - Limit it to once per 7 days, using a timestamp in `localStorage` under `ol_exit_seen`. Wrap every storage access in try/catch, and if storage is blocked, treat the modal as seen.
  - On open:
    - remove `hidden`;
    - add `html.ol-modal-open` (define it in `home.css` as `overflow: hidden`);
    - set `inert` on the other `body` children;
    - trap focus by copying the pattern in `modules/menu.js:11-57`;
    - focus the first `[data-exit-close]`.
  - Close on Escape, on a backdrop click (`e.target === overlay`) and on `[data-exit-close]`, then restore focus.
  - On a CTA click, record "seen" and let the link navigate.
- **Coupon.** `inc/shop/coupon.php` already implements the spec:
  - `optimum_lift_offerable_coupon()` validates the code.
  - The `?ol_coupon` handler on `wp_loaded` stores the code in the WooCommerce session and redirects without the query argument.
  - `woocommerce_add_to_cart` at priority 20 and `woocommerce_before_checkout_form` at priority 5 apply it.

  The modal renders only while the code is offerable and not yet stored or applied (`exit-modal.php:17-21`). WooCommerce's own "Coupon code applied successfully." notice is the confirmation. It shows on the next page that prints notices (the Product hero or checkout); `front-page.php` prints none. Confirm this is enough, or add a notice where the spec needs one.

## Files you own

- `assets/src/js/modules/exit-intent.js`
- `template-parts/home/exit-modal.php`
- `inc/shop/coupon.php`
- `assets/src/css/home.css` (add an exit-modal section)
- `languages/src/07b.json`, only if you add strings (07a already covers the modal's current ones)

## Acceptance criteria

- [x] Playwright, desktop (1440px, fine pointer): nothing happens before 8 s. After 8 s, a `mouseout` at `clientY` 0 opens the modal. Focus is trapped, and Escape, the backdrop and both buttons close it and return focus. A reload within 7 days doesn't reopen it. At 390px with touch emulation it never arms.
- [x] With a cookie jar: `curl /?ol_coupon=OPTIMUM10` → 302 without the argument, then `/?add-to-cart=60`. The Store API cart shows `OPTIMUM10` applied. An invalid code is never applied and never errors.
- [x] The modal isn't rendered when `exit_coupon` is empty or the coupon is expired. Test both with WP-CLI, then restore.
- [x] The "coupon applied in the drawer" check is in 09f.

## Answer

Built (2026-09-24):

- **`modules/exit-intent.js`**:
  - **Arming**: only with `(pointer: fine)`, only when not seen in the last 7 days (`localStorage.ol_exit_seen`, every access in try/catch; blocked storage counts as seen), and only 8 s after load. It opens on a document `mouseout` with no `relatedTarget` and `clientY <= 0`, and then stops listening.
  - **Open**: records "seen", removes `hidden`, adds `html.ol-modal-open`, makes every other `body` child inert (except ones already inert), traps Tab (menu.js pattern) and focuses the first `[data-exit-close]`.
  - **Close**: Escape, a backdrop click (`target === overlay`) or `[data-exit-close]`. It reverses everything and restores focus.
  - **CTA click**: records "seen", unlocks the page and lets the coupon link navigate.
- **`home.css`**: an exit-modal section with `html.ol-modal-open { overflow: hidden }`.
- **No changes needed** to `exit-modal.php`, `coupon.php`, or strings (07a covered them).
- **Confirmation**: WooCommerce's "Coupon code applied successfully." notice is enough. `front-page.php` now prints notices (07a), as do the Product hero and checkout. The drawer shows a "Coupon discount" row and the discounted total (09d), and the checkout review shows the coupon row. So the buyer sees the discount wherever they next look at a price.

Evidence:

- **Playwright, 1440px, fine pointer, with Playwright's clock**:
  - At 5 s a top-edge `mouseout` does nothing. At 9 s it opens: focus on the X, `main.inert`, `html.ol-modal-open`.
  - Tab cycles X → "Use it now" → "No thanks" → X. (My first check reported an "escape" only because `[role=dialog]` matched the cart drawer, which comes first in the DOM. The trace shows focus never leaving the modal's three controls.)
  - Escape, a backdrop click at (20, 20), the X and "No thanks" each close it and return focus to the element focused before. `main` is no longer inert.
  - A reload within 7 days → a top-edge exit doesn't open it.
  - 390px with `hasTouch`/`isMobile` (`pointer: fine` false) → never opens.
  - No JS errors.
- **Cookie jar**: `/?ol_coupon=OPTIMUM10` → `302 -> /`. Then `/?add-to-cart=60` → Store API cart `60`, coupons `optimum10`, total 7,19 €. The modal is no longer rendered for that visitor. `/?ol_coupon=NOPE123` → `302 -> /`, the add works, no coupon, no error notice, total 7,99 €.
- **Not rendered** when `exit_coupon` is empty (`wp theme mod set exit_coupon ''`) or when OPTIMUM10 has expired (`date_expires` = yesterday). Restored afterwards.
- lint exit 0; PHPStan OK; build OK; `debug.log` unchanged.

Follow-up (`inc/cli.php`, ticket 03): `wp ol-shop seed` doesn't reset the OPTIMUM10 coupon's `date_expires`, so an expiry set during testing survives a re-seed. I restored it by hand with `wp post meta delete <id> date_expires`. The seed should clear the expiry, since it claims to be idempotent.
