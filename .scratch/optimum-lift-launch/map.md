# Map: Optimum Lift — First Revenue Release

Label: `wayfinder:map`

## Destination

A buildable spec for the **first release of the Optimum Lift digital shop**: the smallest thing that can take money online for a Training Plan, sold to Albanian-speaking buyers in Kosovo/Albania/North Macedonia *and* the diaspora, acquired via Meta ads.

The map is done when nothing is left to decide before `/to-spec` can collapse it into a buildable plan.

## Notes

**Domain**: e-commerce for digital fitness and nutrition content. Read `CONTEXT.md` before any session — the Plan / Product / Download vocabulary is canonical and already settled.

**Skills every session should consult**: `grilling` and `domain-modeling` by default. `research` for research tickets, `prototype` for prototype tickets, `wizard` for HITL provisioning tasks.

**Standing preferences for this effort**:

- The user is a WordPress/PHP developer but wants **agents to write essentially all of the code**. Getting better at agentic coding is an explicit goal of the project itself, not just a means to it. Favour approaches that keep the human in the reviewing seat.
- Personal project. No deadline — "quickly" is a preference, not a constraint.
- The user will supply an `html.example` for the home page and product page when development starts. Treat it as a load-bearing input, not a mockup.
- **Plan, don't do.** These tickets produce decisions. Nothing here builds the shop.

**Validation is not an open question.** 600+ physical plans were sold profitably under this brand via Meta ads. Demand is proven. The physical constraints that capped profit — printing, shipping, a single SKU — are exactly what going digital removes.

## Decisions so far

<!-- the index: one line per closed ticket, enough to judge relevance, then zoom the link for detail -->

### Resolved tickets

- [Payment rails available to a Kosovo merchant](issues/01-payment-rails-available-to-a-kosovo-merchant.md): Kosovo is excluded from Stripe, PayPal, Mollie, Adyen, Gumroad, Lemon Squeezy and Razorpay. Two viable routes remain — Stripe via a Delaware entity (Stripe Atlas), or a Kosovo bank gateway (Raiffeisen/ProCredit, both with bank-maintained WooCommerce plugins). **Critical:** no Merchant of Record has a working native WooCommerce checkout, so falling back to one costs the platform decision.

### Settled during charting

- **Brand is "Optimum Lift"** — not "Optimal". Carries prior goodwill from 600+ physical sales. See `CONTEXT.md`.
- **Destination is the first revenue release**, not a full architecture. Validation was already achieved by the physical business.
- **Market is Albanian-language, Kosovo/Albania/North Macedonia plus the diaspora** (Germany, Switzerland, Nordics). Not English-language, not bilingual at launch.
- **Price: €7.99 per Product at launch.** User's decision, against a recommendation of €10–12 (the proven physical price was €10 + €2 shipping). Tripwire: net after VAT and fees is roughly €5.87, so a Meta CPA above ~€5.87 loses money per sale and above ~€3 leaves no real margin. Revisit on real CPA data, not opinion.
- **Card payments only at launch.** No manual bank-transfer path; it does not scale and delays delivery.
- **Delivery is a stock WooCommerce downloadable product** — email link plus account download. No custom account area.
- **Custom theme, not a customized base** — unique design is wanted, and for cold paid traffic the product page is the conversion machine. Boundary: custom *design*, not a custom *checkout*. Style Woo's checkout, do not rebuild it.
- **Conversion tracking is release-1 scope**, non-negotiable. Acquisition is entirely paid; Meta's optimization needs the purchase signal.
- **Email capture at checkout is release-1 scope.** 600+ past customers are unreachable today; that mistake is not being repeated.
- **PDF piracy is accepted**, not fought. Personalized-footer watermarking is noted as fog, not a launch feature.
- **First Product is the existing physical Training Plan, digitized.** It is the only content that exists and the only content with receipts behind it.

## Not yet specified

<!-- in-scope fog: real, but not yet sharp enough to ticket -->

- **Email service provider and consent flow.** Capture is decided; which tool, and how consent is worded and stored, is not. Sharpens once the checkout's final shape is known.
- **Theme template scope beyond home and product page.** Which templates release 1 actually needs (category, cart, account, thank-you) — waits on the `html.example` and on where checkout lives.
- **Nutrition Plan as Product #2.** The user believes one can be authored quickly. Not release 1, but close behind it.
- **Geo-differentiated pricing** — a lower price for Kosovo/Albania, the proven price for the diaspora. Attractive, but geo-detection, currency, VAT handling and ad-account separation make it its own effort.
- **A non-card payment path**, if checkout abandonment proves brutal. The user identified card entry as the real friction in a COD-native market. First lever to pull if the data demands it.
- **Plan-authoring and PDF generation tooling.** Wanted eventually for seasonal, multi-Product output. Constraint to preserve now: **the theme must never own Plan content**, so a plugin can take this over later without a migration. Never build it into the theme.
- **Piracy deterrence via personalized PDF footers** (buyer name and email stamped per download). Cheap, meaningful, not launch scope.
- **Meta campaign structure and ad creative** for the digital Product. The COD-to-card shift changes conversion dynamics; creative may need to change with it.
- **US entity compliance, if Stripe Atlas is chosen.** Delaware franchise tax, registered agent renewal, and likely IRS Form 5472/1120 as a foreign-owned US entity. Ongoing work in a second tax jurisdiction, sharpens only if Route A wins.
- **Plan quality.** The user described the physical plans as "not really solid." No software on this map fixes that; it is a content problem and it will cap the business before the shop does.

## Out of scope

<!-- ruled beyond the destination; never graduates. Returns only as a fresh effort. -->

- **The fitness tracker** — workout logging, set/rep history, progression stats, total-volume metrics. Ruled out by the user during charting. It was the original justification for choosing WooCommerce over Shopify; with it out of scope, the platform choice now rests on the user's WordPress expertise instead, and is tested by ticket 03.
- **On-site viewable Plans / a custom customer account area.** The first step toward the tracker, and the classic way a ship-fast project becomes a six-month one.
- **Trainer-facing tooling** — multi-author plan creation, external trainers building Plans in the dashboard.
- **Physical products.** The business being left behind.
