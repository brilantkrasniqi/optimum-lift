# Review and fix: honesty rules (ADR-0008)

Type: task
Status: resolved
Wave: 3
Parent: 11
Blocked by: 11b

Working rules: `spec.md` › "Sub-tickets". Paths are relative to `themes/optimum-lift/`. Run the 11 sub-tickets one at a time.

## What to do

Check that every urgency or social-proof element in ADR-0008 disappears when its data doesn't support it. Push the data below each threshold using WP-CLI or the `optimum_lift_*` threshold filters in a temporary mu-plugin, check the element, and restore everything afterwards with `wp ol-shop seed` and by deleting the mu-plugin.

**Proof thresholds** (`inc/shop/proof.php`):
- Product rating: needs at least 3 reviews.
- Store rating.
- Sold count: at least 25.
- Recent orders: at least 10 in 24 h.
- Bestseller badge.
- Bundle share "X in 10".
- Customer count, including its floors.

**Offers** (`inc/shop/offer.php`):
- Expire the Product sale and the site `offer_ends_at`. The urgency bar, every countdown, the pricing countdown chip and "only tonight" must go, and no price may rise because a countdown ended.
- A bundle priced without a sale has no offer.

**Prices and tokens:**
- `{regular_price}`, `{saving}` and the struck anchors disappear when there is no saving.
- A phrase holding an empty token is dropped (the `" · "` rule).

**Other claims:**
- Guarantee copy follows `guarantee_days`, including 0.
- Payment badges follow the Customizer.
- "Blerje e verifikuar" appears only on WooCommerce-verified reviews.

Verify each finding before fixing it.

## Acceptance criteria

- [x] The Answer has a table: rule, how it was pushed below threshold, element hidden yes/no, and the fix.
- [x] The demo data is restored, with no temporary mu-plugin left in `mu-plugins/`.
- [x] Build, lint and PHPStan pass.

## Answer

Reviewed 2026-09-24. `probe.mjs` (in `C:\Users\Work\.claude\jobs\ee423a8e\tmp\11c\`) fetches `/`, `/shop/`, 60, 63, 64 and `/product-category/paketa/` and prints every urgency and proof marker:

- the bar, the countdowns and "Only tonight";
- stars, "(N reviews)", "N sold", badges, "X in 10", "N customers started…", "N+ customers" and the store rating;
- `<del>` anchors, "Save" pills and percents;
- guarantee phrases and payment badges.

`tok.mjs` prints the hero, final CTA and value stack text of a page. Thresholds were moved with a temporary `mu-plugins/zz-11c-thresholds.php`, switched by a temporary `ol11c_mode` option (`high` = 1 000 000, `low` = 1); data was changed with `wp eval`.

| Rule | How it was pushed below threshold | Hidden? | Fix |
| --- | --- | --- | --- |
| Product rating (≥ 3 reviews) | `optimum_lift_min_reviews` → 1e6 | Yes: every "(N reviews)" and summary star row gone. Individual native reviews and editor testimonials still show their own stars. At the default, 63 (exactly 3 reviews) shows | — |
| Store rating | Same filter | Yes: the hero's "4,8/5 · 45 reviews", the `{store_rating}` stat and the reviews summary are gone | — |
| Sold count (≥ 25) | `optimum_lift_min_sold` → 1e6; at the default, 63 has 13 sales | Yes: none shown; 63 shows none at the default. At `low` it shows "13 sold" | — |
| Recent orders (≥ 10 in 24 h) | Default (4 paid orders today) and `low` | Hidden at the default; "4 customers started in the last 24 hours" at `low` | — |
| Bestseller badge (≥ 10 sales) | `optimum_lift_min_bestseller_sales` → 1e6 | Yes: the badge is gone from cards and the hero (the remaining "Best seller" match is the sort option "Best sellers") | — |
| Bundle share | Default and `low` | Hidden in both: 6 paid orders, 0 with the bundle → `null` | — |
| Customer count and floors | `optimum_lift_format_count_plus()` over sample values | Always shown (the baseline is real): 606 → "600+"; 15 → 10+, 99 → 90+, 149 → 100+, 999 → 950+, 1099 → 1.000+, 12 450 → 12.400+; below 10 the exact number | — |
| **Offer: every Product sale expired** (`date_on_sale_to` yesterday on 60–63; the site `offer_ends_at` still 3 days away) | — | **No.** Product pages lost their bar, but `/`, `/shop/` and the archives still counted down to "Launch offer · up to −72%". The only saving left was the bundle's permanent one against its components, so nothing would change when the countdown ended | `optimum_lift_offer()` without a Product needs `optimum_lift_sale_running()`, i.e. some published Product on sale (new helper in `inc/shop/offer.php`, cached like `optimum_lift_best_sale_percent()`). After: no bar and no countdown on any page. The remaining hero "72% off" / "−72%" is the bundle's real saving against buying the parts, with no countdown attached |
| Offer: site `offer_ends_at` expired, Product sales running | Theme mod set to an hour ago | Home, shop and archive bars gone, and the pricing countdown chip with them. 60 and 63 keep their own bar (their scheduled sales are real). 64 has no bar | — |
| "Only tonight" | `offer_ends_at` today at 23:59, then tomorrow at 00:30 | Shown only for today | — |
| Countdown reaching zero | `offer_ends_at` two minutes ahead; waited in the browser (`w11\zero.mjs`) | Both `[data-countdown-scope]` hidden at 0; every `.woocommerce-Price-amount` unchanged | — |
| Bundle priced without a sale | Seed state (64 has no sale price) | No bar or countdown on 64, and no final-CTA countdown | — |
| `{regular_price}`, `{saving}` and anchors with no saving | 60's sale removed; 64 at 60 € (above its components) | Yes: 60's hero has no `<del>`, no "Save" pill, no percent. 64 has no anchor, no "Best value" and no struck value-stack total | — |
| The `" · "` phrase rule | Same | The final CTA note "Në vend të {regular_price} · Garanci 30 ditë · Akses i menjëhershëm" becomes "Garanci 30 ditë · Akses i menjëhershëm". No `{token}` leftovers (the only `{string}` match is a JSDoc comment in an inline script) | — |
| Guarantee follows `guarantee_days` | 7, then 0 | 7: header, trust lines, cards, the `{guarantee_days}` stat and the drawer all say "7-day" / "7 ditë". 0: every token-driven guarantee element is gone (header chip, trust lines, guarantee block) | Seeded editor copy keeps "30 ditë", as the spec says (hero reassurance line, pricing intro, a FAQ answer, the comparison cell). See follow-ups |
| Payment badges follow the Customizer | "Visa"; ""; "Visa, Mastercard, Apple Pay" | Exactly those, on the Product price box and the homepage trust row; empty prints none | — |
| "Blerje e verifikuar" | Reviews on 60 from two buyers (users 9 and 5) and a guest who never bought, verified by `WC_Comments::add_comment_purchase_verification()` | Only the buyers' reviews carry "Verified purchase"; manual testimonials never do | — |

Restored: `wp ol-shop seed` (sales, prices, `offer_ends_at`, coupon); `payment_badges` theme mod removed (default Visa, Mastercard); `guarantee_days` 30; the three test reviews deleted; the `ol11c_mode` option deleted. **`mu-plugins/` holds only `ol-dynamic-host.php` and `.gitkeep`.** After the restore, `probe.mjs` matches the baseline. Build, lint (exit 0) and PHPStan pass; `debug.log` has no new lines (157).

Follow-ups for the owner:
- **The site percent includes the bundle's permanent saving.** The spec asks the site label's "up to −X%" to include bundles' computed savings. While sales run, that is the bundle's saving against the components' sale prices, and it doesn't end with the offer. It is kept as specified. Consider limiting it to Products on sale.
- **A site offer next to a sale without an end date.** A Product with a sale but no sale end date still gets the site countdown on its page, although its price won't rise when that date passes. Give every sale an end date equal to `offer_ends_at` (the seed does).
- **Seeded "30 ditë" outside tokens.** It has to follow `guarantee_days` by hand (spec, "Placeholder content"); at 0 those lines still promise a guarantee.
