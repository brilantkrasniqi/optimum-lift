# Does WooCommerce survive the payment decision?

Type: grilling
Status: open
Blocked by: 02

## Question

WordPress + WooCommerce was originally chosen for two reasons: it is free, and it is customizable enough for a future fitness tracker. The tracker is now **out of scope**, so that second reason is gone. The surviving argument is WordPress expertise and the ability to debug it — a good argument, but a different one.

Now test it against the payment rail actually chosen in ticket 02:

- If the rail is a normal gateway with a maintained Woo plugin, WooCommerce holds comfortably. Confirm and move on.
- If the rail is a **Merchant of Record** that hosts its own checkout, WooCommerce is doing much less work — it becomes a catalogue and a download-delivery mechanism, with the money moving elsewhere. At that point a far simpler setup may serve better, and the "highly customizable" argument is buying nothing.

## Definition of done

Either WooCommerce is confirmed with the reasoning written down, or an alternative is chosen. Either way this is hard to reverse and the result of a real trade-off — **write an ADR** under `docs/adr/`.

## Sharpened by ticket 01

The research confirmed this ticket's worst case is real, not hypothetical. **No Merchant of Record has a working native WooCommerce checkout** — Paddle disclaims third-party stores outright, Lemon Squeezy's plugin runs standalone, Gumroad has none, FastSpring deprecated its own.

So if the map lands on an MoR, WooCommerce becomes a catalogue in front of someone else's hosted checkout, and "highly customizable" buys nothing. In that branch, seriously weigh a simple landing page plus the MoR's own checkout instead of a full WordPress/Woo install.

If instead the map lands on Stripe-via-Atlas or a Kosovo bank gateway, both have real first-party WooCommerce plugins and this ticket should confirm quickly.
