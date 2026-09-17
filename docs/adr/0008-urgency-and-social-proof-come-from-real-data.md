# ADR-0008: Urgency and social proof are rendered only from real data

**Status:** Accepted (2026-09-16)

## Context

The storefront design leans on persuasion: a countdown bar, struck-through
prices, star ratings, "1.940 të shitura", "12.400+ klientë", "Mbi 40 klientë e
kanë filluar në 24 orët e fundit", "8 nga 10 klientë". In the mock every one of
these is a hard-coded number, and the countdown resets at midnight forever.

The brand sells repeat Products to a small, word-of-mouth market, and has real
goodwill (600+ physical plans sold). A countdown that never ends, a "was"
price that was never charged, or a review count nobody wrote is the kind of
thing regulars notice, and EU diaspora buyers are protected against it by the
Omnibus Directive. The design brief (§4.2) asks for a decision.

## Decision

Every persuasive number is computed, and hidden when the real value is too
weak to help:

| Element | Source | Shown when |
| --- | --- | --- |
| Countdown and offer bar | The Product's scheduled sale end (`date_on_sale_to`), else the site offer end date set in the Customizer | The end date is in the future. When it passes, the bar disappears and WooCommerce ends the sale. |
| Struck-through price and "Kursen X" | Regular vs. current price; for a bundle, the sum of its components' current prices (ADR-0006) | The saving is greater than zero |
| Stars and "(N vlerësime)" | Native WooCommerce reviews, fractional stars | At least 3 reviews |
| "N të shitura" | `total_sales` | At least 25 sales |
| "N+ klientë" | Customizer baseline (default 600, the physical sales) + paid online orders, rounded down | Always (the baseline is real) |
| "N klientë në 24 orët e fundit" | Paid orders in the last 24 hours | At least 10 |
| "X nga 10 klientë e zgjedhin" | Share of paid orders containing the bundle | At least 20 orders and 5 in 10 |
| "Më i shituri" | Highest `total_sales` among non-bundle Products | At least 10 sales |
| Payment badges | Customizer list, default Visa and Mastercard | Only methods actually offered (card only at launch) |
| Exit-intent coupon | The Customizer's coupon code | The coupon exists and is valid |

The thresholds are filterable (`optimum_lift_min_reviews`, …).

## Consequences

- A new store looks quieter than the mock: no stars, no sold counts, no
  countdown unless an offer with an end date is running. That is intended.
- Running a launch offer is a data task: schedule the sale (or set the site
  offer end), and the urgency appears and ends on its own.
- Testimonials, before/after results and the marquee are editor content. The
  theme cannot verify them; the seed data marks them as placeholders to replace
  with real, consented customer stories before launch.
- The 30-day lowest-price rule of the Omnibus Directive is not enforced by
  code. Whoever schedules a sale must set a regular price that was actually
  charged.
