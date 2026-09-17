# ADR-0007: A server-rendered cart drawer, a separate Buy Now endpoint, and the classic checkout

**Status:** Accepted (2026-09-16)

Resolves the open consequence of ADR-0002 about WooCommerce's stylesheets.

## Context

The storefront has two buttons on every Product surface with different jobs:
**"Bli tani"** takes one Product straight to checkout; **"Shto në shportë"**
stays on the page and opens a cart drawer that always proposes one next step
(swap to the bundle, or add the complementary Product). Acquisition is paid
Meta traffic, mostly on phones, in a market where card entry is the main
friction. The payment gateway is not chosen yet (launch issues 02/03); the
leading candidates include Kosovo bank gateways whose WooCommerce plugins are
classic-checkout plugins.

Options for the drawer:

1. **Store API** (`/wc/store/v1/cart`) driven by a client-side renderer. More
   JavaScript to own, and prices would be formatted in two places.
2. **Classic session cart with server-rendered fragments.** The drawer's HTML
   is a PHP template part; every cart change returns fresh fragments.

Options for checkout:

1. **The Checkout block** (WooCommerce's default for new installs). Heavy
   React bundle on the page that matters most on slow phones; gateways must
   ship a block integration; field changes go through a separate API.
2. **The classic `[woocommerce_checkout]` shortcode.** Works with every
   gateway, and `woocommerce_checkout_fields` can remove the address fields a
   digital Product never needs.

## Decision

- **Drawer: option 2.** The theme renders `template-parts/cart/*` on the
  server and returns them through `woocommerce_add_to_cart_fragments`. The
  theme's own JavaScript (no jQuery) calls three `wc-ajax` endpoints the theme
  registers, `ol_add_to_cart`, `ol_remove_from_cart` and `ol_swap_to_bundle`,
  plus WooCommerce's `get_refreshed_fragments`. The browser never computes a
  price or holds cart state.
- **The upsell decision is server-side** (`optimum_lift_cart_upsell()`), from
  the real cart lines, their categories and the bundle's real price. Native
  **Cross-sells** are its candidate source, so there is one list of "goes
  with" Products, used by the drawer, the Product page and the thank-you page.
- **Buy Now is its own endpoint**, `?ol_buy_now=<id>`, handled on `wp_loaded`:
  empty the cart, add the one Product, redirect to checkout. It shares no
  handler with add-to-cart.
- **Bundle rules live on the server:** adding a bundle removes its component
  lines; adding a component already covered by a bundle in the cart is a no-op
  with a notice.
- **Checkout and cart use the classic shortcodes.** For carts that need no
  shipping, checkout asks only for email, name and country. The checkout
  page renders with a distraction-free header.
- **WooCommerce's front-end stylesheets are dequeued.** The theme styles
  WooCommerce markup in its own CSS, so Tailwind utilities and the dark design
  are not fighting unlayered plugin CSS.

## Consequences

- An existing install whose Cart/Checkout pages contain the blocks must swap
  them for `[woocommerce_cart]` / `[woocommerce_checkout]`. `wp ol-shop seed`
  does this locally; the theme shows an admin notice when it finds the blocks.
- Express wallets (Apple Pay, Google Pay) that only exist as block
  integrations are unavailable until a gateway provides a classic integration.
- Every WooCommerce screen the theme shows (notices, forms, account, order
  tables, the Plans Portal) needs theme CSS; nothing falls back to
  WooCommerce's look.
- A page cache must not cache the drawer's contents. The drawer refreshes its
  fragments over AJAX whenever WooCommerce's `woocommerce_items_in_cart`
  cookie says the cart has items, so a cached page self-corrects.
