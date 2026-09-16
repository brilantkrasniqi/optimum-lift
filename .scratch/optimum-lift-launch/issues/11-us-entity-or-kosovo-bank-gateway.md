# US entity (Stripe Atlas) or Kosovo bank gateway?

Type: grilling
Status: open
Blocked by: 01

## Question

Ticket 01 narrowed the field to two viable routes and one fallback. This is the decision between them, and it is the heaviest one on the map.

**Route A — Stripe via Stripe Atlas.** Form a Delaware entity, then run standard Stripe with its official WooCommerce plugin.

- For: best-in-class card acceptance for cold traffic — 3DS/SCA, Apple Pay, Google Pay, fraud tooling, a first-party Woo plugin, and a checkout experience buyers already recognise. This matters more than usual here, because card entry is the identified friction.
- Against: $500 up front, $100/yr, and a real ongoing compliance burden — Delaware franchise tax, registered agent, and likely IRS Form 5472/1120 as a foreign-owned US entity. Stripe explicitly does not guarantee approval after incorporation, so the $500 can be spent and the answer still be no. Adds a second tax jurisdiction to a personal project.

**Route B — Kosovo bank gateway (Raiffeisen Kosovo, or ProCredit).** Native in-page WooCommerce checkout via a bank-maintained plugin.

- For: no foreign entity, no US tax exposure, works with the Kosovo business that already exists, and keeps checkout on-site.
- Against: no evidence in either direction about how these gateways convert cold cross-border Meta traffic. Fees, 3DS behaviour, supported currencies and settlement timing are all unpublished — they need a conversation with the bank.

**Fallback — Paddle.** Absorbs EU VAT entirely and may accept Kosovo, but has no working WooCommerce checkout, so it costs the platform decision.

## What would settle it

Two facts, both cheap to get:

1. **Call or visit Raiffeisen Kosovo and ProCredit.** Ask for e-commerce acquiring terms: setup fee, per-transaction fee, monthly minimum, settlement period, 3DS support, whether foreign-issued cards are accepted, and whether digital goods are permitted. This is a phone call, and it decides Route B outright.
2. **Ask around locally.** Kosovo e-commerce shops exist and take card payments. Whoever runs them already solved this, and their answer is worth more than any documentation.

## Definition of done

One route chosen, with the reasoning recorded. This unblocks *Choose the payment rail and open the account*.

## Notes

HITL — steps 1 and 2 are human work.

Weigh against the EUR 7.99 price point: Atlas's $500 plus roughly $100/yr in fixed cost is about 85 sales a year at net margin *before* the ads are paid for. That is not disqualifying, but it is not trivial at this price either. Route B has no fixed cost.

Do not skip the local-knowledge step. It is the cheapest input on this entire map and it may reveal a route the research could not see from primary sources alone.
