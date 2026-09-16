# Payment rails available to a Kosovo merchant

Type: research
Status: resolved

## Question

What payment rails can a **Kosovo-registered business** actually use to sell **digital downloads** to buyers in Kosovo/Albania/North Macedonia and the EU diaspora (Germany, Switzerland, Nordics), from **WooCommerce**?

Specifically: Stripe's Kosovo status; PayPal's ability to *receive* in Kosovo; Merchant-of-Record options (Paddle, Lemon Squeezy, Gumroad, FastSpring) and whether Kosovo is an accepted seller country for each; Kosovo/Balkan local acquirers and bank gateways (ProCredit, Raiffeisen Kosovo, TEB, BKT, NLB); EU VAT treatment of digital goods and which options absorb it; and for every viable rail, whether a maintained WooCommerce plugin exists.

Primary sources only. "UNCONFIRMED" is a better answer than a guess — a real business decision rests on this.

## Why this blocks so much

The user takes cash on delivery today and has no online rail to reuse. If the only viable option is a Merchant of Record that hosts checkout off-site, that changes the platform decision, the theme's scope, and how conversion tracking works.

## Notes

Findings land at `.scratch/optimum-lift-launch/research/01-payment-rails-kosovo.md`.

Other e-commerce shops operate in Kosovo, so this is solvable — the question is *how* they do it, not *whether* it can be done.

## Answer

Full findings, fully cited: [`.scratch/optimum-lift-launch/research/01-payment-rails-kosovo.md`](../research/01-payment-rails-kosovo.md)

**Kosovo is excluded from almost every major global rail.** Stripe, PayPal, Mollie, Adyen, Gumroad, Lemon Squeezy and Razorpay all exclude it per their own country lists. There is no easy answer here.

Two routes actually check out, plus one fallback:

1. **Stripe via Stripe Atlas** — form a Delaware LLC/C-Corp ($500 up front, $100/yr registered agent), then use standard Stripe with its officially co-maintained WooCommerce plugin. No SSN required; an ITIN works. Gets full card-acceptance stack: 3DS/SCA, Apple Pay, Google Pay, fraud tooling. **Catches:** Stripe explicitly will not guarantee the business gets approved to process after incorporation; running a US entity carries real ongoing obligations (Delaware franchise tax, registered agent, likely IRS Form 5472/1120 for a foreign-owned US company); and it does nothing for EU VAT.
2. **A Kosovo bank's own e-commerce gateway** — Raiffeisen Bank Kosovo has a genuinely bank-maintained WooCommerce plugin on WordPress.org; ProCredit Bank Kosovo is a close second. Native in-page Woo checkout, Visa/Mastercard from any country, no foreign entity needed. **Catches:** requires at least a registered "Biznes Individual" (satisfied here — the business is registered); leaves 100% of EU VAT on the merchant; and there is **no evidence either way** about how these gateways convert cold cross-border Meta traffic. That is untested risk, not merely unconfirmed.
3. **Paddle** — the one Merchant of Record whose unsupported-country list does not name Kosovo, so worth an actual signup attempt. Would absorb EU VAT entirely. **Catch:** see below.

**The decision-changing finding.** Every Merchant-of-Record option — Paddle, Lemon Squeezy, Gumroad, FastSpring — the category built precisely to solve "unsupported country + VAT" — has no working native WooCommerce checkout. Paddle's own help center states it "no longer supports 3rd party online stores directly" and disclaims its unofficial WordPress plugins. Lemon Squeezy's official plugin runs standalone and does not connect to Woo. Gumroad has none. FastSpring deprecated its own.

So if both the Atlas and bank routes fall through, an MoR means checkout on the provider's hosted page with WooCommerce demoted to a catalogue — an extra-hop checkout for cold impulse traffic. That is a materially worse conversion path, and it should be weighed **before** committing to WooCommerce as the platform.

**Confidence note:** the PayPal exclusion rests on an archived PayPal developer doc and a country-codes reference, because PayPal's live country page would not render. Weaker evidence than the Stripe finding, which came from Stripe's own live page. Unconfirmed items are marked as such in the research file (BKT Kosovo's page failed on a certificate error; Payoneer, Wise and 2Checkout eligibility for Kosovo specifically).
