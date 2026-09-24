# Spec: the storefront theme

Status: done (2026-09-24). Every ticket is resolved; see "As built" for what differs from this spec.

Read first: `CONTEXT.md`, ADR-0002, ADR-0003, ADR-0006, ADR-0007, ADR-0008, and
the design at `C:\Users\Work\Desktop\Projects\ol-design\` (`BUILD-BRIEF.md`,
`index.html`, `dyqani.html`, `produkt.html`, `produkt-dieta.html`, `cart.js`).
The design is the Figma file: match its layout, spacing, type and copy. Where
this spec deviates from it, this spec wins; each deviation is listed with its
reason below.

## Goal

Turn the static design into the `optimum-lift` WooCommerce theme, optimised
for conversion from cold Meta traffic on phones, without persuasion that
would cost trust (ADR-0008).

| Mock | WordPress |
| --- | --- |
| `index.html` | `front-page.php`, sections from the front page's `ol_blocks` |
| `dyqani.html` | Shop and `product_cat` archives, via `woocommerce.php` → `template-parts/shop/archive.php` |
| `produkt.html`, `produkt-dieta.html` | One single-Product template, via `woocommerce.php` → `template-parts/single-product/layout.php`, middle from the Product's `ol_blocks` |
| `cart.js` drawer | `template-parts/cart/*` + `assets/src/js/modules/cart.js` + `inc/shop/cart.php` |
| (no mock) checkout, thank-you, account, cart page, 404, blog | Classic WooCommerce templates styled to match |

## Decisions and deviations from the mock

| Mock / brief | Here | Why |
| --- | --- | --- |
| Level / location / goal / preference selector chips | Non-interactive "included versions" pills (`ol_versions`), Simple Products | A selector that changes nothing is a refund request (brief §4); variations add a choice before Buy Now and break AJAX add-to-cart. |
| Countdown resets daily; fixed "−40%" | Countdown only to a real end date; percent computed | ADR-0008 |
| Hard-coded ratings, sold counts, "12.400+ klientë", "Mbi 40 klientë…", "8 nga 10" | Computed with thresholds | ADR-0008 |
| Payment badges VISA / Mastercard / PayPal / Transfertë | Customizer list, default Visa + Mastercard | Launch map: card only; PayPal is unavailable in Kosovo. A badge for a method we do not offer destroys trust at the worst moment. |
| Manual `badge` | Computed badges | ADR-0006 |
| Bundle as a flat product with `allOld` | Simple Product + components; anchor = sum of components' current prices | ADR-0006 |
| Grouped/Bundles extension | Not used | ADR-0006 |
| Lekë prices "4.900 L" | `wc_price()` in the store currency (EUR today) | Currency is a store setting (launch map: €7.99). The seed sets Albanian number formatting: `.` thousands, `,` decimals, symbol after. |
| Google Fonts CDN | Self-hosted Anton + Inter (latin subset, woff2) declared in `theme.json`, Anton preloaded | Speed on mobile; no third-party request (GDPR for EU diaspora). |
| Tailwind CDN | Compiled Tailwind v4 (ADR-0002) | |
| jQuery-free mock JS | Same: vanilla ES modules bundled by esbuild | Speed. |
| Checkout links `#checkout` | `?ol_buy_now=ID` (Buy Now) and `wc_get_checkout_url()` | ADR-0007 |
| Checkout not designed | Classic checkout, email + name + country only, distraction-free header, order summary with savings, trust block | ADR-0007; every removed field raises completion. |
| Homepage exit modal with hard-coded `OPTIMUM10` | Desktop only, once per 7 days, rendered only when the Customizer coupon exists and is valid; the CTA applies the coupon automatically | ADR-0008; typing a code is friction. |
| Mobile product hero: gallery first, title below it | Title + rating above the gallery on mobile (CSS grid areas), gallery sticky on desktop | The first mobile screen must say what the Product is and show proof. |
| Homepage cards list "Pa plan ushqimor" by hand | The "not included" line is computed from the complementary category when a bundle exists | Nudges toward the bundle and stays true. |
| Testimonials hard-coded | Native WooCommerce reviews first, manual testimonials second | Real reviews are the durable proof; aggregate rating comes from them. |
| Product JSON-LD absent | WooCommerce structured data output on the Product page | Rich results (price, rating) raise ad-landing and organic CTR. |
| Nav links hard-coded per page | In-page nav built from blocks that set `nav_label`, after a "Dyqani" link; other pages use the `primary` menu | Adding a section adds its nav link. |
| Mobile menu has longer labels ("Zgjidh objektivin", "Pyetje të shpeshta") and a "Çmimet" link | The mobile menu repeats the header's `nav_label`s; pricing is reached through the menu's CTA | One label per section; the CTA already goes to pricing. |
| Menu button always visible | Shown only once JavaScript runs (`js:`); without it the footer carries the links | A button that cannot open anything is a dead end. |
| UI copy in Albanian in the HTML | English source strings, Albanian shipped as the theme's `sq` translation; site language `sq` | WordPress convention and consistency with the Plans plugin; the Albanian copy from the mock is the translation. |

Placeholder content (testimonials, before/after results, the marquee, the
homepage hero's before/after and progress card, the goal tabs' "customer
average" and "typical result" figures, trainer name and certificates,
"WhatsApp 7/7", the shopping-list cost, value-stack values, the legal pages'
text) is seeded as in the mock and must be replaced with real, consented
content before launch. The theme cannot verify it. Guarantee copy outside a
`{guarantee_days}` token (headings, intros, reassurance lines, comparison
cells, FAQ answers, Product descriptions) is seeded as "30 ditë" and must be
kept equal to the Customizer's `guarantee_days`.

## Design tokens

`theme.json` is the source of truth (ADR-0002). The Plans plugin's `portal.css`
reads the `ink`, `ink-muted`, `surface`, `line`, `paper` and `accent` presets,
so those slugs keep their *meaning* (text, muted text, raised surface, border,
page background, accent) with dark values.

| Mock class colour | `theme.json` slug | Hex | Tailwind utility |
| --- | --- | --- | --- |
| `ink` #07080A (page background) | `paper` | #07080A | `bg-paper`, `text-paper`, `ring-paper` |
| `coal` #0D1012 | `surface` | #0D1012 | `bg-surface` |
| `steel` #14181B | `raised` | #14181B | `bg-raised` |
| `brand` #E41B23 | `accent` | #E41B23 | `bg-accent`, `border-accent`, `text-accent` |
| `brandl` #FF3B4A | `accent-light` | #FF3B4A | `text-accent-light`, `hover:bg-accent-light` |
| `acid` #C6FF3D | `acid` | #C6FF3D | `bg-acid`, `text-acid`, `border-acid/30` |
| — | `ink` | #E4E4E7 | none (portal + editor only) |
| — | `ink-muted` | #A1A1AA | none |
| — | `line` | #24292E | `border-line` |

**Translating mock markup:** `bg-ink` → `bg-paper`, `text-ink` (dark text on a
light button) → `text-paper`, `ring-ink` → `ring-paper`, `bg-coal` →
`bg-surface`, `bg-steel` → `bg-raised`, `brand` → `accent`, `brandl` →
`accent-light`. `zinc-*`, `white`, `black` utilities stay as in the mock.
**There is no `ink` Tailwind colour.** Any `-ink` class in a template is a bug.

Fonts: `theme.json` families `sans` (Inter, variable, 300–900) and `display`
(Anton); Tailwind `font-sans`, `font-display`. Shadows: `--shadow-glow: 0 20px
70px -20px rgba(228,27,35,.65)` and `--shadow-card: 0 30px 80px -40px
rgba(0,0,0,.9)` → `shadow-glow`, `shadow-card`.

Tailwind v4 notes: write full class names in PHP (no string-built classes);
`bg-linear-to-*` for gradients; arbitrary values as in the mock work.

### Component classes (`assets/src/css/base.css`, `components.css`, `@layer components`)

Owned by ticket 01. Other tickets use them and do not redefine them.

- `.h-display`: Anton, uppercase, `letter-spacing: -.01em`, `line-height: 1.06`.
- `.eyebrow`: the section chip (`rounded-full border border-white/10 bg-white/5 px-3.5 py-1.5 text-[10px] font-extrabold uppercase tracking-[.2em] text-zinc-400`). `.eyebrow--acid` and `.eyebrow--accent` variants.
- `.btn` base (inline-flex, centred, gap, `font-extrabold`, transition, focus ring) with `.btn-primary` (accent → accent-light), `.btn-light` (white → acid, text paper), `.btn-ghost` (`border-white/15 bg-white/5` → `bg-white/10`), `.btn-outline` (the mock's `.cbtn`), sizes `.btn-sm` (`px-4 py-2.5 text-[12px] rounded-xl`), `.btn-md` (`px-5 py-3 text-[13px] rounded-xl`), `.btn-lg` (`px-7 py-4 text-base rounded-2xl`), `.btn-block` (full width).
- `.pill` (small rounded label) and `.pill-acid`, `.pill-accent`.
- `.grain`, `.ph-photo`, `.ph-photo--acid` (diet gradient).
- `.reveal` (only hidden when `html.js`), `.pulse`, `.floaty`, `.marquee` and their keyframes; all disabled under `prefers-reduced-motion`.
- `.olstars` (fractional stars via `--pct`).
- `.acc`, `.acc-btn`, `.acc-body`, `.acc-ico` (accordion).
- `.scroll-x`, `.scroll-hint`.
- `.prose-ol` (dark prose for editor content).
- `[hidden] { display: none !important }`, `html.menu-open`, `html.olc-open` (no scroll), scrollbars, `::selection`, `scroll-padding-top`, `color-scheme: dark`, body `bg-paper text-zinc-300 font-sans antialiased`.
- `.screen-reader-text`, `.skip-link`.

## File ownership

Tickets run in parallel, so each file has one owner. **Do not edit a file you
do not own.** If you need a change there, say so in your final report. Stubs
are created by ticket 01 so everything compiles before its owner fills it in.

| Path (theme = `themes/optimum-lift/`) | Owner |
| --- | --- |
| `theme.json`, `style.css`, `functions.php`, `header.php`, `footer.php`, `woocommerce.php`, `inc/setup.php`, `inc/assets.php`, `inc/customizer.php`, `inc/icons.php`, `inc/template-tags.php`, `inc/woocommerce.php` | 01 |
| `assets/fonts/*`, `assets/icons/*.svg` (01 creates the set; **any ticket may add new icon files**) | 01 |
| `assets/src/css/main.css`, `base.css`, `components.css`; `assets/src/js/main.js`, `modules/{track,countdown,menu,reveal}.js` | 01 |
| `template-parts/header/*`, `template-parts/footer/*`, `template-parts/components/countdown.php` | 01 |
| `inc/shop/{fields,product-data,pricing,proof,offer,blocks}.php` | 02 |
| `inc/cli.php`, `docker/setup.sh` (price format and language lines only) | 03 |
| `template-parts/product/*` | 04 |
| `template-parts/single-product/*`, `assets/src/css/product.css`, `modules/{gallery,buybar}.js` | 05 |
| `template-parts/blocks/{qualification,phases,preview,sample-day,shopping-list,value-stack,rich-text}.php`, `assets/src/css/blocks.css` | 06a |
| `template-parts/blocks/{results,reviews,comparison,credibility,guarantee,faq,final-cta}.php`, `assets/src/css/blocks-proof.css`, `modules/accordion.js` | 06b |
| `front-page.php`, `template-parts/blocks/{home-hero,marquee,problem,steps,goal-tabs,pricing}.php`, `template-parts/home/*`, `inc/shop/coupon.php`, `assets/src/css/home.css`, `modules/{tabs,sticky-cta,exit-intent}.js` | 07 |
| `template-parts/shop/*`, `inc/shop/archive.php`, `assets/src/css/shop.css`, `modules/shop-sort.js` | 08 |
| `inc/shop/{cart,bundle,buy-now,upsell}.php`, `template-parts/cart/*`, `assets/src/css/drawer.css`, `modules/cart.js` | 09 |
| `inc/shop/checkout.php`, `woocommerce/checkout/**`, `woocommerce/cart/**`, `woocommerce/notices/**`, `woocommerce/README.md`, `assets/src/css/woocommerce.css` (notices, forms, cart page, checkout, thank-you) | 10a |
| `woocommerce/myaccount/**`, `woocommerce/global/**`, `optimum-lift-plans/**` (Portal template overrides, only if CSS cannot do it), `page.php`, `index.php`, `single.php`, `archive.php`, `search.php`, `searchform.php`, `404.php`, `comments.php`, `template-parts/content*.php`, `assets/src/css/account.css`, `assets/src/css/pages.css`, `assets/css/editor.css` | 10b |
| `languages/**` | 12 (tickets 01–10 write only their own `languages/src/<ticket>.json`, e.g. `06a.json`) |

`functions.php` (01) requires every `inc/` and `inc/shop/` file above with
`require_once` guarded by `is_file()`, so a missing module never fatals.

## PHP conventions

PSR-12 (`npm run lint:php`), PHPStan level 6 (`npm run analyse:php`), both
must pass. `declare(strict_types=1);` in `inc/` files. Functions are prefixed
`optimum_lift_`; no classes needed. Escape at output (`esc_html`, `esc_attr`,
`esc_url`, `wp_kses_post` for editor HTML). Template parts receive data through
`get_template_part($slug, null, $args)` and declare it:
`/** @var array{product: WC_Product, variant?: string} $args */`. Every helper
that reads ACF goes through `optimum_lift_field()`, which returns `null` when
ACF is inactive. Text domain `optimum-lift`; **source strings in English**.

**Translations.** For every user-facing string you add, add
`"English source": "Albanian"` to `themes/optimum-lift/languages/src/<ticket
number>.json` (e.g. `05.json`). Use the mock's Albanian verbatim where it
exists. Plurals: `"%s review": {"plural": "%s reviews", "sq": ["%s vlerësim",
"%s vlerësime"]}`. Context: key `"context\u0004source"`. Ticket 12 turns these
into `languages/sq.po`, `sq.mo` and `sq.l10n.php`.

## Theme settings (ticket 01, `inc/customizer.php`)

Customizer section "Optimum Lift". Read with `optimum_lift_setting(string
$key): mixed`, which returns the default when unset.

| Key | Type | Default |
| --- | --- | --- |
| `tagline` | text | `Trupi · Plani · Rezultati` |
| `contact_email` | email | `info@optimumlift.com` |
| `whatsapp` | text (international digits) | `''` (WhatsApp elements hidden) |
| `instagram_url`, `tiktok_url` | url | `''` (icon hidden) |
| `guarantee_days` | int | `30` |
| `offer_label` | text | `Launch offer` (translatable) |
| `offer_ends_at` | datetime-local, site timezone | `''` |
| `customers_baseline` | int | `600` |
| `exit_coupon` | text | `''` |
| `payment_badges` | text, comma-separated | `Visa, Mastercard` |

## Data layer (ticket 02)

### Product fields (`inc/shop/fields.php`)

Keys: groups `group_olt_*`, fields `field_olt_*`, layouts `layout_olt_*`. Meta
names start with `ol_`. (The Plans plugin uses `group_ol_*` / `field_ol_*`;
never reuse those.) Keys are derived mechanically, so the seed can write by key:

- top-level field: `field_olt_` + name without `ol_` (`ol_points` → `field_olt_points`, `ol_blocks` → `field_olt_blocks`);
- sub field of a top-level repeater: `field_olt_<parent>_<sub>` (`field_olt_points_text`, `field_olt_versions_options`);
- layout: `layout_olt_<layout>`; its sub fields: `field_olt_<layout>_<sub>` (`field_olt_faq_items`), including the common ones (`field_olt_faq_anchor`);
- sub field of a repeater inside a layout: `field_olt_<layout>_<repeater>_<sub>` (`field_olt_faq_items_question`, `field_olt_comparison_rows_cells_value`);
- groups: `group_olt_sales`, `group_olt_bundle`, `group_olt_sections`.

When seeding, pass the top-level selector as a key and rows keyed by sub field
**names** (`['acf_fc_layout' => 'faq', 'heading' => …, 'items' => [['question' => …]]]`);
ACF resolves names inside rows.

Group "Sales page" on `product`:

| Name | Type | Notes |
| --- | --- | --- |
| `ol_title_accent` | text | Suffix of the title shown in accent colour ("12-Javor"). |
| `ol_card_blurb` | text | Card blurb; falls back to the short description, trimmed to 16 words. |
| `ol_points` | repeater (`text`) | Benefit bullets. Cards show 3, the hero up to 6. |
| `ol_duration_weeks` | number | Drives "≈ X / javë". |
| `ol_media_label` | text | Gallery chip ("Produkt digjital · PDF + video"). |
| `ol_level_label` | text | Hero pill ("Fillestar → i avancuar"); falls back to the goal. |
| `ol_versions` | repeater (`label` text, `options` textarea, one per line) | Included versions. |
| `ol_versions_note` | text | "Të gjitha versionet përfshihen në çmim." |
| `ol_stats` | repeater (`value`, `label`), max 4 | Trust strip under the hero. Tokens allowed. |
| `ol_bundle_hint` | text | Sentence before the bundle name in the hero hint. |

Group "Bundle" on `product` in `product_cat` `paketa`: `ol_bundle_components`,
relationship to `product`, return IDs.

Group "Page sections" on `product` **and** on the page that is the front page:
`ol_blocks`, Flexible Content, button label "Add section".

Every layout has these sub fields first: `anchor` (text, becomes the section
`id`), `nav_label` (text, adds a header nav link), `eyebrow` (text), `heading`
(textarea: `*word*` renders in accent colour, a newline renders `<br>`),
`intro` (textarea), `tone` (select `default` | `alt`; `alt` is the
`border-y border-white/[.07] bg-surface/50` band).

Text fields marked *tokens* pass through `optimum_lift_replace_tokens()`:

| Token | Value | Empty when |
| --- | --- | --- |
| `{price}` | Context Product's current price | no context |
| `{regular_price}` | Context Product's anchor price (the struck "was" price) | no saving (ADR-0008) |
| `{saving}` | Context Product's saving amount | no saving |
| `{bundle_price}` | Context bundle, else the bundle containing the context, else the first bundle | no bundle |
| `{guarantee_days}` | Customizer `guarantee_days` | it is 0 |
| `{customers}` | `optimum_lift_format_count_plus(optimum_lift_customer_count())` | never |
| `{rating}`, `{reviews}` | Context Product's own rating (store-wide without a context) | below the review threshold |
| `{store_rating}`, `{store_reviews}` | Store-wide rating (use these on the homepage, whose context is the bundle) | below the review threshold |
| `{rest}` | `optimum_lift_catalog_rest()` (default 3 featured cards) | never |

The result is **HTML** (text escaped, prices as `wc_price()` markup): print it
without escaping again. A token without a value becomes `''`; in text made of
phrases separated by `" · "`, the phrase holding it is dropped instead
("Bli — {price} · Në vend të {regular_price}" → "Bli — 7,99 €" without a
saving). To hide a whole element whose token is empty (a stat, a note), test
the raw text with `optimum_lift_has_empty_token()` first. A `pricing` block
whose `featured_limit` is not 3 replaces `{rest}` with
`optimum_lift_format_number(optimum_lift_catalog_rest($limit))` before calling
`optimum_lift_replace_tokens()`.

| Layout | Own sub fields |
| --- | --- |
| `qualification` | `yes_title`, `yes_items` (repeater `text`, inline HTML allowed), `no_title`, `no_items` (repeater `text`) |
| `phases` | `style` (`numbers`\|`icons`), `cards` (repeater `label`, `title`, `text`, `bullets` textarea lines, `icon` select, `featured` bool), `checklist` (textarea lines) |
| `preview` | `workout_label`, `workout_tag`, `workout_rows` (repeater `name`, `scheme`), `workout_note`, `media_image` (image ID), `media_video_url` (url), `media_caption`, `progress_title`, `progress_rows` (repeater `label`, `value`, `percent` number), `progress_note` |
| `sample_day` | `day_label`, `kcal`, `protein`, `carbs`, `fat`, `meals` (repeater `time`, `name`, `description`, `kcal`, `macros`), `note` (textarea, `<strong>` allowed), `facts` (repeater `value`, `text`) |
| `shopping_list` | `body` (wysiwyg), `cost_label`, `cost_value`, `cost_suffix`, `cost_note`, `list_label`, `items` (textarea lines), `list_note` |
| `value_stack` | `layout` (`centered`\|`split`), `product` (post object → product, ID; default: page context product), `items` (repeater `text`, `value` number, `bonus` bool), `total_label`, `pay_label`, `cta_label` (tokens), `side_image` (image ID) |
| `results` | `items` (repeater `before_image`, `after_image`, `name`, `meta`, `result`, `quote`), `disclaimer`, `cta_label`, `cta_anchor` |
| `reviews` | `source` (`native`\|`manual`\|`both`), `limit` (number, default 3), `items` (repeater `quote`, `name`, `meta`, `rating` 1–5), `show_summary` (bool) |
| `comparison` | `columns` (repeater `label`, `product` post object, `highlight` bool), `rows` (repeater `label`, `type` `text`\|`price`\|`cta`, `cells` repeater `value`), `note` (textarea). A cell starting `✓` renders acid, `✕` muted. |
| `credibility` | `image` (image ID), `name`, `role`, `body` (wysiwyg), `badges` (repeater `title`, `text`, `icon`), `chips` (textarea lines) |
| `guarantee` | `style` (`band`\|`card`), `body` (textarea, tokens), `chips` (textarea lines), `cta_label`, `cta_anchor` |
| `faq` | `items` (repeater `question`, `answer` wysiwyg), `note` (wysiwyg; medical disclaimer box), `help_box` (bool; WhatsApp box when a number is set) |
| `final_cta` | `body` (textarea), `show_countdown` (bool), `product` (post object, default context product), `primary_label` (tokens), `primary_action` (`buy_now`\|`anchor`), `primary_anchor`, `secondary_add_to_cart` (bool), `note` (textarea, tokens) |
| `rich_text` | `body` (wysiwyg) |
| `home_hero` | `show_proof` (bool), `body` (wysiwyg), `primary_label`, `primary_anchor`, `secondary_label`, `secondary_anchor`, `reassurance` (textarea lines), `before_image`, `after_image`, `before_label`, `after_label`, `progress_label`, `progress_percent`, `progress_stats` (repeater `value`, `label`), `day_plan_label`, `day_plan` (repeater `meal`, `kcal`), `stats` (repeater `value`, `label`, tokens) |
| `marquee` | `items` (textarea lines) |
| `problem` | `cards` (repeater `icon`, `title`, `text`), `footer` (text, `*accent*`) |
| `steps` | `steps` (repeater `icon`, `title`, `text`), `cta_label`, `cta_anchor`, `show_recent` (bool) |
| `goal_tabs` | `tabs` (repeater `label`, `heading`, `text`, `bullets` textarea lines, `cta_label`, `product` post object, `cta_anchor`, `stat`, `image`, `image_label`, `image_value`) |
| `pricing` | `show_countdown` (bool), `bundle` (post object, default first bundle), `featured_limit` (number, default 3), `rest_heading`, `rest_text` (tokens), `show_trust` (bool) |

`icon` selects offer the names in `assets/icons/`.

### Helper API

All in `inc/shop/`, all prefixed, all safe without ACF.

```php
// product-data.php
optimum_lift_field(int $post_id, string $name): mixed
optimum_lift_product_kind(WC_Product $p): ?string          // 'program' | 'diet' | 'bundle' from product_cat slugs programe-stervitjeje | dieta | paketa (filter optimum_lift_kind_slugs)
optimum_lift_is_bundle(WC_Product $p): bool
optimum_lift_bundle_components(WC_Product $bundle): list<WC_Product> // published + purchasable
optimum_lift_find_bundle(?WC_Product $containing = null): ?WC_Product
optimum_lift_complement_kind(WC_Product $p): ?string        // program <-> diet
optimum_lift_goal(WC_Product $p): ?string                   // first pa_objektivi term name
optimum_lift_level_label(WC_Product $p): ?string            // ol_level_label, else the goal; plain text
optimum_lift_category_label(WC_Product $p): string
optimum_lift_card_blurb(WC_Product $p): string
optimum_lift_points(WC_Product $p): list<string>
optimum_lift_stats(WC_Product $p): list<array{value: string, label: string}>
// stats: value and label are HTML with tokens replaced (print without escaping);
// items with an empty token are dropped
optimum_lift_versions(WC_Product $p): list<array{label: string, options: list<string>}>
optimum_lift_title_html(WC_Product $p): string              // escaped, accent suffix wrapped in <span class="text-accent-light">
optimum_lift_featured_products(int $limit = 3): list<WC_Product> // featured, not bundles, menu_order
optimum_lift_catalog_count(): int
optimum_lift_cross_sells(WC_Product $p, int $limit = 3): list<WC_Product> // native cross-sells, else complement-kind products; bundle appended last unless $p is/was in it
optimum_lift_buy_now_url(WC_Product $p): string             // home_url('/?ol_buy_now=ID')
optimum_lift_heading_html(string $text): string
optimum_lift_lines(?string $textarea): list<string>
optimum_lift_replace_tokens(string $text, ?WC_Product $context): string   // HTML, see the token table
optimum_lift_has_empty_token(string $text, ?WC_Product $context): bool
optimum_lift_catalog_rest(int $featured_limit = 3): int     // catalogue count − featured shown − the bundle

// pricing.php — every amount is wc_get_price_to_display()-based
optimum_lift_current_price(WC_Product $p): float
optimum_lift_anchor_price(WC_Product $p): float            // bundle: sum of components' current prices; else regular price
optimum_lift_saving(WC_Product $p): ?array{amount: float, percent: int} // null when <= 0
optimum_lift_price_per_week(WC_Product $p): ?float

// proof.php — thresholds filterable
optimum_lift_rating(WC_Product $p): ?array{average: float, count: int}   // >= 3 reviews
optimum_lift_store_rating(): ?array{average: float, count: int}          // weighted, transient
optimum_lift_sold_count(WC_Product $p): ?int                              // >= 25
optimum_lift_customer_count(): int                                        // baseline + paid orders, transient
optimum_lift_format_number(float $n, int $decimals = 0): string           // plain text, WooCommerce price separators ("12.400", "4,8")
optimum_lift_format_count_plus(int $n): string                            // "600+", floors: <100 to 10, <1000 to 50, else 100; format_number
optimum_lift_recent_orders_count(int $hours = 24): ?int                  // >= 10, transient 15 min
optimum_lift_badge(WC_Product $p): ?array{label: string, tone: string}   // tone 'accent' | 'acid'
optimum_lift_bundle_share(WC_Product $bundle): ?int                      // X in 10, see ADR-0008
optimum_lift_format_rating(float $avg): string                            // format_number($avg, 1)

// offer.php
optimum_lift_offer(?WC_Product $p = null): ?array{ends_at: int, label: string, percent: ?int, scope: string}
// product on sale with future date_on_sale_to -> scope 'product'; else site offer_ends_at in the future
// (and, when $p is given, $p is on sale) -> scope 'site'; percent: product saving, or the highest
// saving among published products, bundles' computed savings included, for the site scope
// ("deri në −X%"); null otherwise. A Product not on sale itself (e.g. a bundle priced without a
// sale) has no offer: its price does not rise when the site offer ends. label is display-ready
// plain text ("Oferta e lansimit · deri në −50%"): escape it, do not append percent again.
// Callers: urgency bar optimum_lift_offer(optimum_lift_current_product()); final_cta countdown
// optimum_lift_offer($product) (its product, so none for the bundle); pricing countdown optimum_lift_offer().

// blocks.php
optimum_lift_blocks(int $post_id): list<array<string, mixed>>
optimum_lift_render_blocks(int $post_id, ?WC_Product $context): void
// get_template_part('template-parts/blocks/' . str_replace('_', '-', $layout), null,
//   ['block' => $row, 'product' => $context, 'post_id' => $post_id, 'index' => $i]); unknown layouts are skipped.
optimum_lift_block_nav(int $post_id): list<array{label: string, anchor: string}>
optimum_lift_block_id(array $block, string $fallback = ''): string   // sanitize_title of anchor
optimum_lift_block_tone_class(array $block): string                  // '' or the alt band classes
```

Context product: on a Product page, that Product; on the front page, the
bundle (`optimum_lift_find_bundle()`).

## Markup contracts

### Buttons (JS relies on these attributes)

```html
<!-- Buy now: plain navigation, works without JS -->
<a href="{optimum_lift_buy_now_url}" rel="nofollow" data-buy-now="{id}" data-cta="{cta-id}" class="btn btn-primary …">…</a>
<!-- Add to cart: JS intercepts; without JS WooCommerce adds via the URL -->
<a href="{$product->add_to_cart_url()}" rel="nofollow" data-add-to-cart="{id}" data-cta="{cta-id}" class="btn btn-outline …">…</a>
<!-- Drawer toggle and count badge (header) -->
<button type="button" data-cart-toggle aria-controls="ol-cart-drawer" aria-expanded="false">…<span data-cart-count class="olc-badge [is-on]">{n}</span></button>
<!-- Inside the drawer -->
<button type="button" data-cart-remove="{cart_item_key}">…</button>
<button type="button" data-cart-swap="{bundle_id}">…</button>
<button type="button" data-add-to-cart="{id}">…</button>
```

`data-cta` ids are kebab-case and unique per surface (`pdp-buy-now`,
`card-add-{id}`, …). User-facing strings never live in JS: render them in PHP
and read them from `data-*` attributes.

### Product components (ticket 04, `template-parts/product/`)

| Part | `$args` | Renders |
| --- | --- | --- |
| `card.php` | `product`, `variant` `shop`\|`featured`\|`compact`, `cta_prefix` | `shop`: the full dyqani card, element for element (brief §4.1). `featured`: the homepage card (kind icon + category, name, blurb, price + anchor, "Kursen X%" line, points + computed "not included" line, view + add). `compact`: the cross-sell card (kind icon + category, name, blurb, price, view + add; `border-2` accent frame for a bundle). |
| `bundle-banner.php` | `product`, `variant` `shop`\|`home` | The anchor card for the bundle: badge (share or "Vlera më e mirë"), category line, name, rating + sold, blurb, points (2 cols), price, anchor, saving pill, percent, Buy Now, add to cart, "Akses i menjëhershëm · Garanci N ditë". `home` adds the level label to the category line ("Paketa e plotë · Çdo program + çdo dietë"), `≈ X / javë` and the larger CTA copy. |
| `price.php` | `product`, `size` `sm`\|`md`\|`lg`\|`xl`, `show_saving` bool, `show_per_week` bool | Current price, struck anchor, "Kursen X" pill, optional "≈ X në javë · pagesë e njëhershme". |
| `rating.php` | `product` or `rating` array, `size`, `link` (a section anchor with or without `#`, e.g. `optimum_lift_block_id($block)`, a URL, or ''), `show_sold` bool | `.olstars` fractional stars, "4,9", "(N vlerësime)", optional sold count. Renders nothing below thresholds. |
| `badge.php` | `product`, `class` | Computed badge pill. |
| `versions.php` | `product` | Included-version groups as check pills + note. |
| `buy-buttons.php` | `product`, `layout` `stack`\|`row`, `size`, `cta_prefix`, `buy_label`, `add_label` | Buy Now primary + add-to-cart secondary per the contract. |
| `trust-line.php` | `items` list of strings or default | "Pagesë e njëhershme · Akses i menjëhershëm · Garanci N ditë". |
| `payment-badges.php` | `class` | Customizer payment badges + "Pagesë e enkriptuar SSL". |
| `thumb.php` | `product`, `size`, `class`, `icon_size` | Featured image, or the `.ph-photo` placeholder with the kind icon (barbell / bowl / bolt). |

### JavaScript

`assets/src/js/main.js` (01) imports every module and calls `init()` once the
DOM is parsed. Each module exports `init()` and returns immediately if its DOM
is absent. Ticket 01 creates stubs for all modules listed in the ownership
table. `modules/track.js` exports `track(event, data)`:

- pushes `{event: 'ol_' + event, ...data}` to `window.dataLayer`;
- calls `fbq('track', …)` for `view_item`→`ViewContent`, `add_to_cart`→`AddToCart`, `begin_checkout`→`InitiateCheckout`, `purchase`→`Purchase` (with `eventID` when given), `fbq('trackCustom', 'CTAClick', …)` for `cta_click`;
- calls `gtag('event', event, data)` when present;
- binds a delegated `[data-cta]` click listener;
- on load, reads every `<script type="application/json" data-ol-track="{event}">` and tracks it. The event name is the attribute value (else an `event` key in the payload; with neither, the payload is skipped). A payload with a truthy `once` is tracked once per browser (localStorage), keyed by `once` when it is a string, else by the first of `eventID`, `transaction_id`, `order_key`, else by the payload's JSON. The thank-you page prints e.g. `<script type="application/json" data-ol-track="purchase">{"once": true, "order_key": "wc_order_…", "eventID": "…", "value": 14.99, "currency": "EUR", "items": [{"id": 12, "name": "…", "price": 14.99}]}</script>`.

`window.optimumLift` (inline, before `main.js`): `{ wcAjaxUrl, checkoutUrl,
cartUrl, currency }`. Custom events on `document`: `ol:cart:updated`
(`detail.fragments`), `ol:cart:open`.

Countdown markup (01, `template-parts/components/countdown.php`, args
`ends_at` int, `variant` `inline`\|`boxes`): `<span data-countdown="{ISO 8601}">`
with `[data-cd="d|h|m|s"]` children; elements with
`[data-countdown-scope]` around it are hidden when it reaches zero.

## Behaviour

### Header and chrome (01)

Urgency bar (`optimum_lift_offer()`; hidden when null and on checkout,
order-received included) →
sticky header (logo + tagline, nav, contextual CTA, drawer toggle, menu
button) → mobile menu overlay (backdrop + right panel, focus trapped,
Escape closes, footer CTA). Nav: "Dyqani" then `optimum_lift_block_nav()` on
the front page and Products, else the `primary` menu. Header CTA: Product →
"Bli tani" to `#blej`; front page → "Fillo tani" to the pricing block anchor.
Checkout (not order-received): minimal header (logo, "Pagesë e sigurt",
guarantee) and minimal footer (legal links). Footer: brand + blurb + socials,
Products (all published, menu_order), Help (FAQ anchor, email, refund policy,
privacy, terms), copyright, medical/results disclaimer, mobile spacer when a
sticky bar exists.

### Product page (05, 06)

Hero (`#blej`): breadcrumb; mobile order title → gallery → body, desktop
gallery (sticky) left; pills (category, level/goal); `h1` via
`optimum_lift_title_html()`; rating linked to the reviews section; short
description; points (2 cols); versions; price box (price `xl`, saving,
per-week, Buy Now with `pulse`, add to cart, 3 delivery lines, payment
badges); bundle hint (not on the bundle itself). Under the gallery: guarantee
note. Trust strip from `ol_stats`. Then `optimum_lift_render_blocks()`; when a
Product has no blocks, its description in `.prose-ol`, then a guarantee band.
Then cross-sells ("Blihet shpesh bashkë me", `compact` cards). Sticky buy bar
shows once the price box is out of view (mobile bottom, desktop under the
header). WooCommerce structured data is printed. A `view_item` tracking
payload is printed. No jQuery: WooCommerce's `wc-single-product` script is
dequeued (01), so a native review form keeps its plain rating `<select>`; any
star widget for it is vanilla JS in the reviews block's module.

### Homepage (06, 07)

`front-page.php` renders the front page's `ol_blocks` with the bundle as
context; without blocks, the page content. Mobile sticky CTA (bundle price +
link to pricing anchor) after 700px. Exit intent: desktop, once per 7 days,
only with a valid coupon; its CTA is `?ol_coupon=CODE#<pricing anchor>`, and
`inc/shop/coupon.php` stores the code in the WooCommerce session and applies
it as soon as the cart has items.

### Shop (08)

Intro band (breadcrumb, `h1`, catalogue count sentence) → bundle banner
(`shop` variant; only on the shop page and the `paketa` archive) → filter
pills (links: all → shop, and one per kind category, `aria-current`) + sort
`<select>` (WooCommerce `orderby`: `menu_order` "Të zgjedhurat", `popularity`
"Më të shiturat", `price`, `price-desc`; auto-submits, with a no-JS submit
button) → result count → grid of `shop` cards (bundles excluded, since the
banner shows them) → empty state → guarantee band. Pagination if ever needed.

### Cart (09)

- Drawer shell printed on `wp_footer` (not on checkout): backdrop, `<aside
  id="ol-cart-drawer" role="dialog" aria-modal="true">`, head, body fragment
  `div.olc-body-inner`, foot fragment `div.olc-foot-inner`. Portable CSS from
  `cart.js` §4, converted to tokens.
- Body: lines (thumb, category, name, price + anchor, remove), then exactly one
  upsell box when `optimum_lift_cart_upsell()` returns one, else nothing. Empty
  state with a link to the shop.
- Foot: "Vlera pa ulje" (sum of anchors, only when > total), "Kursen", total
  (`WC()->cart` subtotal), checkout button (`begin_checkout` tracked),
  continue browsing, reassurance line.
- `optimum_lift_cart_upsell(): ?array{type: 'swap'|'upgrade'|'complement', product: WC_Product, amount: float}`, in order:
  1. a bundle is in the cart → null;
  2. covered lines (components of the bundle) cost ≥ the bundle → `swap`, amount = saving;
  3. cart has one kind but not its complement: let `c` be the best complement (from the lines' cross-sells, else best-selling of that kind). If subtotal + price(c) ≥ bundle price → `upgrade` (amount = bundle − covered subtotal, "for +X get everything"); else `complement` with `c`;
  4. otherwise, a bundle exists and is not in the cart → `upgrade`.
- Endpoints (`wc_ajax_*`, POST): `ol_add_to_cart` (`product_id`) → applies the
  bundle rules, returns `{ok, notice, fragments, cart_hash, count, item: {id,
  name, price, currency}}`; already-in-cart is `ok` with no change;
  `ol_remove_from_cart` (`cart_item_key`); `ol_swap_to_bundle` (`bundle_id`).
  Errors return `ok: false` and a notice rendered in the drawer.
- `?ol_buy_now=ID` on `wp_loaded`: validate, empty cart, add, redirect to
  checkout; on failure back to the Product with a notice.
- Fragments also update every `[data-cart-count]`. On load, if the
  `woocommerce_items_in_cart` cookie exists, fetch `get_refreshed_fragments`
  once. Focus moves into the drawer on open and back on close; Escape closes.
- Admin: saving a bundle whose price is not below its components' sum shows a
  warning notice.

### Checkout and after (10)

Converts nothing in the database (the seed does). Hooks: when the cart needs
no shipping, keep only `billing_email`, `billing_first_name`,
`billing_last_name`, `billing_country` (email first); remove order notes.
Admin notice when the Checkout or Cart page contains the block instead of the
shortcode. Overrides: `checkout/form-checkout.php` (two columns: form left;
order summary right, sticky on desktop, first and collapsible on mobile),
`checkout/review-order.php` (thumbnails, anchors, savings row), `checkout/
thankyou.php` (success hero, order summary, the Plans plugin's section,
next steps, cross-sells, `purchase` payload with `once` and the order key),
plus styling for notices, forms, cart page, My Account, login, and the Plans
Portal (dark-safe). Trust block under "Place order". Blog, pages, search and
404 restyled to the dark design.

### Seed (03)

`wp ol-shop seed` (local environment only), idempotent by slug:

- categories `programe-stervitjeje` "Programe stërvitjeje", `dieta` "Plane
  ushqimore", `paketa` "Paketa"; attribute `pa_objektivi` with terms "Humbje
  yndyre", "Masë & forcë", "Shëndet & mbajtje", "Transformim i plotë";
- Products (virtual, sold individually), content transcribed from the mocks:
  `programi-i-stervitjes-12-javor` (featured, menu 1, 14.99 → 7.99), `force-mase`
  (featured, 2, 15.99 → 8.99), `plani-ushqimor-12-javor` (featured, 3, 12.99 →
  6.99), `dieta-mesdhetare` (4, 9.99 → 5.99), `transformimi-total` (bundle, 0,
  14.99, all four as components); every sale ends in 3 days; the program page
  and diet page `ol_blocks` as in `produkt.html` and `produkt-dieta.html`;
  cross-sells as in the mocks; the first `ol_training_plan` linked to the
  program Product (Plans plugin `plans` field);
- demo `total_sales` and demo reviews (authors named "Demo …") so proof renders;
- page `kreu` with the homepage `ol_blocks` from `index.html`, set as the
  static front page;
- coupon `OPTIMUM10` (10 %, one use per customer) and `exit_coupon` set; site
  `offer_ends_at` in 3 days;
- legal pages "Kushtet e shërbimit", "Politika e privatësisë", "Politika e
  kthimit" published with marked placeholder text and set as the WooCommerce
  terms, WordPress privacy and WooCommerce refund pages; WooCommerce's terms
  checkbox text emptied, so checkout gains no required checkbox;
- `show_countdown` off on `final_cta` blocks whose Product is the bundle;
- Cart/Checkout pages switched to `[woocommerce_cart]` / `[woocommerce_checkout]`;
  WooCommerce "coming soon" off; default country `XK`; price format `.` / `,` /
  2 decimals / symbol right with space; site language `sq` (admin user locale
  `en_US`).

## Verification

From the repo root:

```sh
npm run build                     # Tailwind + esbuild
npm run lint:php                  # or: docker compose run --rm composer vendor/bin/phpcs <files>
npm run analyse:php
MSYS_NO_PATHCONV=1 docker compose --profile cli run --rm -T wpcli wp ol-shop seed
curl -s http://localhost:8080/ | grep -iE "fatal|warning|notice|deprecated"
docker compose exec -T wordpress tail -n 80 /var/www/html/wp-content/debug.log
"/c/Program Files/Google/Chrome/Application/chrome.exe" --headless=new --disable-gpu --hide-scrollbars \
  --window-size=390,9000 --screenshot="C:\\Users\\Work\\.claude\\jobs\\dd052601\\tmp\\<name>.png" http://localhost:8080/
```

Each `docker compose run` takes ~20 s: batch WP-CLI commands with `sh -s`
reading a heredoc.

Interactive checks (click, open the drawer, full-page screenshots, run JS):
`playwright-core` is installed in `C:\Users\Work\.claude\jobs\dd052601\tmp\pw`.
Write an `.mjs` script in that folder (or a subfolder) and run it with `node`;
launch with `chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe' })`.
See `pw/smoke.mjs`. `page.screenshot({ fullPage: true })` does not trigger
`.reveal`; scroll the page first or add `html.js .reveal { opacity: 1 }` via
`page.addStyleTag` for screenshots. Mock screenshots: the same Chrome command on
`file:///C:/Users/Work/Desktop/Projects/ol-design/<file>.html`. A tall window
reveals every `.reveal` section.

## Sub-tickets (2026-09-24)

Waves 1–2 landed in `1adeef1` and `a5fa5db`. Tickets 01–04 are resolved. The
remaining work in 05–11 is split into sub-tickets in `issues/`: `05a` … `11e`.
Each parent lists its sub-tickets on a `Split into:` line and stays `claimed`
until the last of them resolves.

**Order.** Work the frontier: the first file by name that is `ready-for-agent`
and whose `Blocked by` tickets are all `resolved`. `05a` (green lint, valid
JSON, live demo offer) comes first. If you pick by hand, go for the path to a
sale first: 09a, 09b, 09c, 09d, 09e, then 10a, 10c, 10d, 09f.

**Rules for every sub-ticket:**

- **Claim and resolve.**
  - Set `Status: claimed` before starting.
  - When done, set `Status: resolved` and add a `## Answer` covering what was built, the evidence for each acceptance criterion, deviations from this spec, and follow-ups.
  - If yours is the last open sub-ticket of its parent, resolve the parent too, with a short Answer.
- **Ownership.** Edit only the files the sub-ticket lists. It may name files owned by resolved tickets (01–04, 06a). If you need anything else changed, say so in the Answer. Line numbers in sub-tickets date from 2026-09-24 and may drift.
- **Translations.**
  - Strings go in `languages/src/<sub-ticket id>.json` (`05.json`, `06b.json`, `07.json`, `08.json`, `09a.json`, `10b.json`, …), in the format above.
  - The context separator is the six-character escape `\u0004` inside the JSON string, never a raw byte.
  - Don't redefine a key another file already has. If a key must repeat, use the identical translation.
  - Validate each file with `node -e "JSON.parse(require('fs').readFileSync(process.argv[1],'utf8'))" <file>`.
- **Seed data.** The seed's sales and site offer end 3 days after seeding. Before any visual or price check, confirm `on_sale` is true at `/wp-json/wc/store/v1/products`; if it isn't, re-seed with `wp ol-shop seed`. Seeded IDs are stable across re-seeds:

  | ID | Product | Kind | Price after re-seed |
  | --- | --- | --- | --- |
  | 60 | `programi-i-stervitjes-12-javor` | program | 7,99 € |
  | 61 | `force-mase` | program | 8,99 € |
  | 62 | `plani-ushqimor-12-javor` | diet | 6,99 € |
  | 63 | `dieta-mesdhetare` | diet | 5,99 € |
  | 64 | `transformimi-total` | bundle of 60–63 | 14,99 €, anchor 29,96 € |

  Mocks map as: 60 → `produkt.html`, 62 → `produkt-dieta.html`, front page → `index.html`.
- **Payments.** No payment gateway is enabled. To place a test order, enable Cash on delivery for virtual orders, then disable it again. Use COD because it moves the order to `processing`, which grants Plans; Check payments leaves it `on-hold`.
- **Done means:**
  - `npm run build`, `npm run lint:php` (exit 0) and `npm run analyse:php` pass.
  - `debug.log` has no new lines.
  - Every acceptance box is checked, with evidence in the Answer.
- **Commits.** One commit per sub-ticket on `main`, titled `<id>: <title>`, with no push. The owner reviews and pushes.

**New paths:**

| Path | Owner |
| --- | --- |
| `template-parts/checkout/*` | 10a |
| `woocommerce/checkout/**` | 10c, 10d |
| `woocommerce/cart/**`, `woocommerce/notices/**` | 10e |
| `woocommerce.css` | split into sections: notices, forms and checkout (10c); thank-you (10d); cart page (10e) |
| `account.css`, the Portal overrides | 10f |

## As built (2026-09-24)

Every ticket, 01–13, is resolved; each ticket's `## Answer` has the evidence. Where the code differs from the sections above, the code wins, as recorded here. Each item was checked against the code on 2026-09-24.

**Cart and checkout**

- **The drawer total is the cart total, not the subtotal** (09d). `template-parts/cart/foot.php` uses `WC()->cart->get_total('edit')`, so a coupon from the coupon link (07b) shows as a discount row and lowers the total; "Vlera pa ulje" and "Kursen" still compare against the anchors.
- **Program + diet is not a swap** (09c). With the seeded prices, 60 + 62 cost 14,98 €, 0,01 € under the bundle, so step 3 of the ladder offers "upgrade" (+0,01 €) rather than "swap".
- **Payment block and coupon form moved** (10a, 10c). `inc/shop/checkout.php` moves the payment block out of the order review to after the customer details (with its own "Payment" heading), and the coupon form to `woocommerce_after_checkout_form`, outside `form.checkout`. On desktop the coupon toggle sits at the bottom of the right column. Without JavaScript the form is shown directly (11b).
- **Checkout source order and headings** (11e). The order summary comes before the fields in the source, so focus follows the phone layout; the grid places it on the right from `lg`. "Your details", "Payment" and "Your order" are `h2`, which needs a `woocommerce/checkout/form-billing.php` override. Overrides and hooks are listed in `themes/optimum-lift/woocommerce/README.md`.
- **Thank-you without a login wall** (10d). The Plans plugin creates an account for every guest order, which makes WooCommerce ask for a password the buyer hasn't set. `woocommerce_order_received_verify_known_shoppers` lets the buyer through only when:
  - the URL carries the order key;
  - the session's email matches the order's;
  - the order is inside WooCommerce's 10-minute grace period.
- **A failed Buy Now leaves the cart empty** (09b). The cart is emptied before the add, so if a third-party validation filter refuses the Product, the previous cart is not restored. The buyer is sent back with a notice.

**Offers**

- **A site-wide offer needs a running sale** (11c). `optimum_lift_offer()` without a Product returns null unless at least one published Product is on sale (`optimum_lift_sale_running()`); otherwise the countdown would end with no price changing. A bundle's saving against its components still counts toward the site label's "up to −X%", as specified.

**Styling**

- **Muted text colours** (11e). `--color-zinc-500` is `#8a8a93`, not Tailwind's `#71717a` (under AA on the dark backgrounds). Small text the mock sets in `zinc-600` uses `zinc-500`; `zinc-600` remains for decorative separators, icons, gradients and underlines.
- **No `style.css` request** (11e). `style.css` only holds the theme header and is no longer enqueued; `assets/dist/main.css` is the one stylesheet.
- **Component classes removed** (11d). `.pill`, `.pill-acid`, `.pill-accent` and `.eyebrow--accent` were in the component list but unused. The badges use utilities.
- **`<main id="main" tabindex="-1">`** in every template, so the skip link moves focus (11e).

**Contracts**

- **dataLayer key** (11d). The `track()` dataLayer push carries `event_id` (GTM convention) rather than `eventID` and drops `once`; `fbq` still receives `{eventID}`.
- **`optimum_lift_shop_setting()` removed** (11d). It was a fallback from when ticket 02 ran without the Customizer module; everything calls `optimum_lift_setting()`.

**Translations**

- **`sq.po` replaces the JSON sources** (12). The per-ticket `languages/src/*.json` files were merged into `themes/optimum-lift/languages/sq.po`, then deleted. `sq.po` is the source; `optimum-lift.pot` sits beside it; `sq.mo` and `sq.l10n.php` are generated. To add a string:
  1. `wp i18n make-pot . languages/optimum-lift.pot --domain=optimum-lift --exclude=assets,languages`
  2. `wp i18n update-po`
  3. Translate the new entries.
  4. `wp i18n make-mo` and `make-php`.

  The "Translations" rule under PHP conventions no longer applies. PHPCS excludes `languages/`.

## Follow-ups (not in this effort)

- Real content: photos, testimonials, results, trainer, prices, offer dates.
- A bundle's Plans kept equal to the union of its components' Plans.
- Nutrition Plan structure (the diet Products grant nothing yet).
- EU withdrawal consent at checkout (launch issue 10) and marketing email
  consent (launch map: email capture).
- Server-side purchase tracking / Meta Conversions API (launch issue 09).
- Albanian translation of the Plans plugin.
- Omnibus lowest-price display.
- WooCommerce order attribution (`sourcebuster.js`, `sbjs_*` cookies) on every page: keep it for ad attribution or gate it behind consent (11e).
- The site offer label's "up to −X%" counts the bundle's permanent saving; consider only Products on sale. Every sale should end on `offer_ends_at` (11c).
- Seeded guarantee copy outside `{guarantee_days}` has to follow the setting by hand (11c).
