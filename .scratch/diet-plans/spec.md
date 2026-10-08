# Spec: Diet plans: a shared recipe list and sized diet Products

Status: planned (2026-10-07). Tickets 01 to 11 in `issues/`, in build order.

- **Part A (01–04)** brings the diet template into the repo and gives it a shared recipe list. Node only; it runs in a cloud session.
- **Part B (05–10)** makes a diet sellable in sizes: a WooCommerce variable Product with a gender picker and a weight picker, and every buy path adds the variation the buyer chose. PHP and JavaScript, checked on the owner's PC.
- **Ticket 11** is the owner's check.

Parts A and B do not depend on each other and can be built in parallel by two agents. Ticket 10 (docs, translations) needs both.

Read `CONTEXT.md` first. This spec uses its words: **Nutrition Plan**, **Product**, **Customer**, **Download**, **Access**. It adds three, which ticket 10 writes into `CONTEXT.md`:

- **Food:** one entry in the food table (`foods.json`): an Albanian name, a group, and kcal and macros per 100 g.
- **Recipe:** one dish written once (Foods with grams, steps, a swap) and used by many Nutrition Plans. Named by its **recipe key**, which never changes.
- **Size:** one gender and weight range that a Nutrition Plan is rendered for ("Mashkull · 80–90 kg"). Each Size is its own PDF, sold as one WooCommerce variation of the diet Product.

## Why this exists

The owner sells Nutrition Plans as PDFs. Claude writes each one as data (Foods and grams), and a renderer turns it into a branded A4 PDF. The renderer computes every number, so the numbers always match the grams printed. One plan renders in 10 Sizes (Mashkull and Femër × 50–60, 60–70, 70–80, 80–90, 90+ kg) by scaling portions. Two plans exist: a 2-day example and "Djegie e shpejtë", a 14-day cut (men only, so 5 Sizes).

Two things stop this from scaling to the planned 6–7 diets:

1. **Every plan writes its meals out in full.** Nothing tells the Claude writing diet #3 that "Omëletë me spinaq e djathë" already exists. The two plans already disagree about it (100 g vs 150 g of egg white), and each copy needs its own photo and its own proofreading. Meals should be written once, as Recipes, and plans should name them.
2. **The store cannot sell Sizes.** The obvious setup is a WooCommerce variable Product with Gjinia and Pesha dropdowns and one PDF per variation. But every theme button sends only the parent Product's ID: Buy Now, add to cart, the cart drawer, the sticky buy bar and the Product cards. WooCommerce then refuses the add ("choose product options"), or the theme adds nothing. The buyer must be able to pick a Size, and every path must deliver exactly that Size's PDF.

## For the agent building this

**Read first, in this order:** `CONTEXT.md`, then this spec. For Part A, read the template's `README.md` and `render.js` once ticket 01 has moved them. For Part B, read these ADRs: `docs/adr/0006-*.md` (Products are Simple, bundles), `0007-*.md` (drawer, Buy Now, classic checkout), `0008-*.md` (proof from real data) and `0009-*.md` (withdrawal waiver). Then read the theme code listed under "What exists today".

**Code conventions.** Part A is plain Node (CommonJS, no build step, no dependencies besides Playwright), matching `render.js`. Part B follows the theme:
- `declare(strict_types=1)` and functions prefixed `optimum_lift_`.
- Hooks registered in the `inc/shop/*.php` file that owns the topic.
- Vanilla ES modules in `assets/src/js/modules/`. No jQuery, and no WooCommerce front-end scripts.
- Templates under `template-parts/`.
- PSR-12 via PHPCS and PHPStan, as in `phpstan.neon.dist`.
- English source strings in the `optimum-lift` text domain.

**Where the work can run.** Checked from a cloud session on 2026-10-07:
- The cloud container has Node 22 and a global Playwright with Chromium (`/opt/node-tools/node_modules/playwright`, browsers in `/opt/pw-browsers`). Part A runs entirely there.
- The cloud has **no Docker daemon and no ACF Pro**, so it cannot run WordPress, PHPCS or PHPStan. Part B's checks run on the owner's PC:
  - Path: `C:\Users\Work\Desktop\Projects\optimum-lift`.
  - Windows with Docker Desktop, the running stack and ACF Pro.
  - Use a Remote Control session in that folder if the session can start one; otherwise give the owner exact commands.
  - Push the branch first and `git pull` it there.
- The owner's shell is **PowerShell**. Its `>` writes UTF-16, so never redirect output into files there.
- To read WooCommerce's source, use the container (`docker compose exec wordpress cat wp-content/plugins/woocommerce/...`), not `./plugins/woocommerce` (CLAUDE.md).

**Working the tickets:**
- One ticket at a time, in order within each part.
- Set `Status: claimed` before starting and `Status: resolved` once every acceptance box is ticked.
- Then append a dated `## Comments` entry: what changed (files), how each criterion was verified (commands and results), and anything worth knowing.
- One commit per ticket.
- Part B tickets need `npm run lint:php` and `npm run analyse:php` to pass, and `npm run build` to succeed.
- Ask the owner before pushing to a shared branch, opening a PR, or merging to `main`.

## What exists today (checked 2026-10-07 on `main` at `0169190`)

**The diet template** lives outside the repo, in the project's shared folder `/mnt/project-files/diet-plans/template/`:

| File | What it is |
| --- | --- |
| `render.js` (347 lines) | `node render.js plans/<slug>.json [--only m-80-90] [--png]`. Builds every Size, prints a calorie table, `WARNING`s (a day >5% off target, a page too full, a missing photo) and `ERROR`s (unknown Food, missing grams), then writes `out/<slug>/<slug>-<gender>-<weight>kg.pdf` with Playwright. |
| `foods.json` | 58 Foods, values per 100 g (USDA), optional `unit` (pieces or spoons, with a rounding `step`). `spice` has kcal 0 and `"shop": false`. |
| `sizes.json` | Reference 80 kg man. Factor = (weight-range middle / 80)^0.75, × 0.88 for women. Genders `male`/`female` with file names `mashkull`/`femer`. Weight codes `50-60`, `60-70`, `70-80`, `80-90`, `90plus`. |
| `style.css` | The look. Fonts are injected as base64 from `fonts/` (the same two files as `themes/optimum-lift/assets/fonts/`). |
| `plans/djegie-yndyre.json` | The 2-day worked example, all 10 Sizes. |
| `plans/djegie-e-shpejte.json` | 14 days, 56 meals, `"sizes": {"genders": ["male"]}`. |
| `README.md` | Usage, the authoring steps Claude follows, Sizes, and the WooCommerce setup. |

A meal today is written in full inside a day: `slot`, `time`, `minutes`, `name`, `photo`, `ingredients[]` (`food`, `grams`, optional `note`, `label`, `household`), `steps[]`, `swap`. Six of the 8 meals in the example also appear by name in Djegie e shpejtë. One is identical; five differ only in some grams, or in a spice line.

The owner's account skill `write-diet-plan` points Claude at `/mnt/project-files/diet-plans/template/`.

**The theme sells Simple Products only.** `git grep -n "variable\|variation" themes/` finds nothing but `inc/cli.php`'s `set_variation(false)`. Every place that sends a Product to the cart or to Buy Now sends the parent ID:

| Where | What it sends |
| --- | --- |
| `template-parts/product/buy-buttons.php:46` | `<a href="?ol_buy_now=ID" data-buy-now="ID">`, then the add-to-cart partial. Used by the price box (`single-product/price-box.php:39`), the sticky buy bar (`single-product/buy-bar.php:46`) and the bundle banner (`product/bundle-banner.php:97`). |
| `template-parts/product/add-to-cart.php:20` | `<a href="$product->add_to_cart_url()" data-add-to-cart="ID">`. Also used by the Product cards (`product/card.php:187`) and the final CTA (`blocks/final-cta.php:121`). |
| `blocks/final-cta.php:49,116,133`, `blocks/value-stack.php:180`, `header/mobile-menu.php:21,87` | `optimum_lift_buy_now_url($product)` with `data-buy-now`. `final-cta.php` already handles an `#…` href. |
| `template-parts/cart/upsell.php:69,71` | The drawer's next step: `data-add-to-cart="ID"` for the complement, `data-cart-swap="ID"` for the bundle. |
| `inc/shop/cart.php:148-185` | `wc_ajax_ol_add_to_cart` reads only `$_POST['product_id']`. `optimum_lift_cart_add()` calls `$cart->add_to_cart($id)`. |
| `inc/shop/buy-now.php` | `?ol_buy_now=ID` on `wp_loaded`: empty the cart, validate, `add_to_cart($id)`, redirect to checkout. |
| `inc/shop/product-data.php:464` | `optimum_lift_buy_now_url()` returns `/?ol_buy_now=ID`. |
| `assets/src/js/modules/cart.js:219` | Posts `{ product_id: el.dataset.addToCart }`. If the request fails, it follows the link's href. |

Code that reads a cart line's Product would break on a variation, because a variation has no categories, ACF fields or cross-sells of its own:
- `optimum_lift_cart_lines()` returns `$item['data']`, which is the variation.
- `optimum_lift_product_kind()` reads `get_the_terms($p->get_id(), 'product_cat')`, so for a variation it returns `null`. So do `optimum_lift_category_label()`, the drawer upsell (`inc/shop/upsell.php:108`) and `template-parts/cart/upsell.php:55`.
- `optimum_lift_cart_has_product()` (`bundle.php:45`) checks `generate_cart_id($product_id)`. That never matches a variation's line.

These already work for variations (checked):
- The Plans plugin grants Access by `$item->get_product_id()`, the parent (`src/Access/OrderAccess.php:42,97`).
- The thank-you payload uses `get_product_id()` (`woocommerce/checkout/thankyou.php:50`).
- Bundle component removal compares `$item['product_id']`, also the parent (`bundle.php:105`).
- `WC()->structured_data->generate_product_data()` handles variable Products itself.

Other things to know:
- `inc/woocommerce.php:68` makes every virtual Product sold individually.
- `inc/shop/withdrawal.php:30` shows the withdrawal waiver whenever the cart needs no shipping. That stays true only if every Size variation is Virtual.
- `inc/shop/offer.php:51` reads `get_date_on_sale_to()` from the Product. On a variable parent that is empty, because the dates live on the variations.
- `ol-shop seed` (`inc/cli.php`) builds Products 60–64 as `WC_Product_Simple`. Two of them are diets: `plani-ushqimor-12-javor` and `dieta-mesdhetare`. The bundle `transformimi-total` contains both diets and two Training Plan Products.

## Decisions

### Part A: the template and Recipes

1. **The template moves into the repo, at `content/diets/`.** Plans need history, other sessions and the owner's PC get the repo but not the shared folder, and a future generator plugin can read the same files. It sits next to `content/plans/` (Training Plan files, `.scratch/plan-files/`). Rendered PDFs are never committed (`content/diets/out/` is gitignored). In a project thread, Claude renders with `--out /mnt/project-files/diet-plans/out` so the PDFs can be attached.
2. **One file per Recipe**, `content/diets/recipes/<recipe-key>.json`. The file name is the key: lowercase ASCII words joined by hyphens, from the dish's name (`omelete-me-spinaq-e-djathe`). One file each, so two agents writing two diets in parallel never conflict on a shared file. A key is never renamed or reused once a plan names it, like the Exercise library keys (ADR-0010).
3. **A plan names Recipes; it never writes a meal out.** Each meal in a plan is `slot`, `time`, `recipe`, plus optional `portion` and `grams`.
   - `portion` multiplies the whole Recipe (default 1, allowed 0.5–2).
   - `grams` sets one Food's grams for this plan only (`{"rice": 85}`). `0` leaves that Food out of the meal.
   - This is how a plan fits a Recipe to its calories, or trims carbs on a rest day, without forking the Recipe.
   - A plan cannot add a Food to a Recipe. That needs a new Recipe.
4. **Recipes are strict.** An unknown property, an unknown Food or a Food listed twice in one Recipe is an `ERROR`. So are an unknown recipe key in a plan, and a `grams` key the Recipe does not contain. Spices are the exception to "listed twice": each `spice` line has its own `label`.
5. **Changing a Recipe changes every plan that uses it.** The catalogue (ticket 04) shows which plans use each Recipe. After changing a Recipe, re-render every plan it is in and tell the owner which Products need new PDFs. A change that suits only one plan goes in that plan's `portion`/`grams`, or into a new Recipe.
6. **Migration must not change a single number.** Ticket 01 adds a `--dump` option that writes each Size's computed plan as JSON. Ticket 03 migrates both plans to Recipes and proves the dumps are identical. The only allowed difference is a spice line gained by merging two copies of a meal.
7. **Tags are a short fixed list** for finding Recipes when writing a themed diet: `vegetarian`, `pescatarian`, `dairy-free`, `gluten-free`, `meal-prep` (keeps 3 days in the fridge), `balkan` (a local dish). Calories and macros are never tags; they come from the numbers.
8. **The photo belongs to the Recipe.** A dish photographed once shows in every plan.

### Part B: sized diet Products

9. **A sized diet is a WooCommerce variable Product.**
   - It uses two global attributes, **Gjinia** (`pa_gjinia`) and **Pesha** (`pa_pesha`), both "Used for variations".
   - Each variation is one Size: Virtual, Downloadable, with that Size's PDF.
   - Term slugs equal the renderer's codes, so a PDF's file name names its variation without a lookup table:
     - `pa_gjinia`: `mashkull` (Mashkull) and `femer` (Femër).
     - `pa_pesha`: `50-60`, `60-70`, `70-80`, `80-90` and `90plus`, named "50–60 kg" … "90+ kg".
   - A women-only or men-only diet has only the variations it sells. A diet with one gender may drop Gjinia altogether.
   - The theme does not hard-code these attributes. It handles any variable Product, so a later dimension (a "vegetarian" version, say) needs no theme change.
10. **Every Size of a diet costs the same.** The page shows one price, and the price box never changes when a Size is picked. If the variations' prices differ anyway, the page shows the lowest price, and the Product edit screen warns (ticket 09). This keeps the price box, the buy bar, the saving, the sale timer and tracking one value per Product.
11. **This amends ADR-0006**, which made every Product Simple and said nothing on the page may look like a choice that changes the purchase. A Size *is* such a choice: it changes which file the Customer receives. The "included versions" pills stay non-interactive for everything else. Ticket 10 writes ADR-0011.
12. **The Size picker is the theme's own markup, not WooCommerce's variations form.**
    - WooCommerce's form needs jQuery and `add-to-cart-variation.js`, and its dropdowns would fight the design (ADR-0002, ADR-0007).
    - The theme renders one `<form>` in the hero's price box: a `fieldset` per attribute with radio pills, `required`, nothing pre-selected.
    - Nothing pre-selected is deliberate. Nobody should end up with the wrong Size because they didn't notice a default.
    - Query arguments in WooCommerce's own format (`?attribute_pa_gjinia=femer&attribute_pa_pesha=60-70`) pre-select a Size, so an ad can link straight to one.
13. **The picker works without JavaScript.**
    - The form is `method="get"` to the Product's own permalink, so a no-JavaScript add comes back to the same page with WooCommerce's notice. Its buttons are submit buttons: `name="ol_buy_now" value="<parent ID>"` and `name="add-to-cart" value="<parent ID>"`.
    - The browser refuses to submit until both radios are chosen and scrolls to the missing one.
    - Buy Now and WooCommerce's own add-to-cart handler then find the variation from the `attribute_*` arguments.
    - The sticky buy bar's buttons submit the same form through the HTML `form` attribute, so the bar needs no picker of its own.
    - JavaScript only improves on this:
      - It greys out combinations that have no variation.
      - It shows the chosen Size in the buy bar.
      - It sends add-to-cart through the drawer instead of a page load.
14. **Buy paths outside the Product page send the buyer to the picker.**
    - These surfaces cannot ask for a Size: Product cards, the final CTA and value-stack sections, the mobile menu, the bundle banner, and the drawer's complement and bundle-swap suggestions.
    - For a Product that needs a choice, their button becomes **"Choose your size"** and links to the Product page's `#blej`.
    - The one exception is the drawer's bundle swap when the cart already holds a Size of a diet in that bundle. It swaps to the bundle's variation of the same Size.
15. **One Size per diet in the cart.** Adding a different Size of a diet that is already in the cart replaces the earlier Size, with a notice ("Changed to Femër · 60–70 kg"). Reason: the common case is a buyer correcting a mistake, and paying twice for one diet is worse than making a couple order twice. Adding the same Size again is a no-op, as today.
16. **A bundle that contains a sized diet is itself variable, with the same attributes.**
    - Each bundle variation carries the PDF of every diet in the bundle for that Size.
    - Training Plans keep reaching bundle buyers through the Plans plugin's `plans` field on the parent, which already works.
    - The anchor price stays the sum of the components' current prices (ADR-0006). A sized diet's current price is its one price.
    - A bundle only contains sized diets that come in every Size the bundle sells. A men-only diet does not go in a Gjinia × Pesha bundle. The Size PDFs box warns when a bundle Size lacks a component's file (ticket 09).
17. **Everything except price and Size comes from the parent Product.** That covers kind, category label, ACF fields, cross-sells, thumbnail, permalink, sales count and tracking ID.
    - The helpers resolve a variation to its parent themselves, so call sites keep working unchanged.
    - The cart drawer, checkout summary and cart page show the parent's name, with the Size on its own line ("Mashkull · 80–90 kg").
    - Order emails, order details and My Account keep WooCommerce's default line, after checking that the Size appears there exactly once.
18. **Tracking stays one ID per diet.** `add_to_cart`, `view_item` and `purchase` send the parent ID (what the ads catalogue knows), with the Size as `variant`.
19. **PDFs are attached by file name, not by hand (ticket 09).** A "Size PDFs" box on the variable Product's edit screen takes the renderer's PDFs in one upload and puts each file on the variation its name matches. WP-CLI cannot help on production, which has none.
    - Attaching 10 files by hand per diet is how a buyer of "Femër · 60–70" gets the 90+ kg file.
    - On a re-upload, each variation keeps its download ID, so Customers who already bought get the new file through their existing link.
    - The box also lists, per Size, its price, whether it is Virtual and Downloadable, and its file. Anything that would break checkout or delivery is a warning.

## Recipe file format

```json
{
  "name": "Omëletë me spinaq e djathë",
  "slots": ["breakfast"],
  "minutes": 10,
  "tags": [],
  "photo": "",
  "ingredients": [
    { "food": "egg", "grams": 150 },
    { "food": "egg_white", "grams": 150 },
    { "food": "spinach", "grams": 60 },
    { "food": "white_cheese", "grams": 25 },
    { "food": "wholegrain_bread", "grams": 70 }
  ],
  "steps": [
    "Rrihi vezët bashkë me të bardhat.",
    "Kaurdis spinaqin 1 minutë në tigan pa vaj.",
    "Hidh vezët, thërrmo djathin sipër, mbuloje 3 minuta."
  ],
  "swap": "Djathë i bardhë → gjizë, 40 g"
}
```

| Property | Rules |
| --- | --- |
| `name` | Required. The dish's Albanian name, as printed. Unique across Recipes. |
| `slots` | Required, at least one of `breakfast`, `snack`, `lunch`, `dinner`, `preworkout`. A plan using the Recipe in another slot gets a `WARNING`, not an error. |
| `minutes` | Required integer, 0–240. |
| `tags` | Optional, from Decision 7's list. |
| `photo` | Optional path under `content/diets/photos/`. |
| `ingredients` | Required, 1–10 entries. Each has `food` (a `foods.json` key) and `grams` (required unless the Food has kcal 0), plus optional `note`, `label` and `household`, exactly as today. |
| `steps` | Required, 1–4 short sentences. |
| `swap` | Optional. Grams in it scale with the portion and the Size, as today. |

Grams are written for the plan's reference person (an 80 kg man), the same as in plans today.

## Plan meal format (replaces the inline meal)

```json
{ "slot": "lunch", "time": "13:30", "recipe": "pule-ne-skare-oriz-e-sallate-shope", "grams": { "rice": 85 } }
```

| Property | Rules |
| --- | --- |
| `slot` | Required, as today. |
| `time` | Required `HH:MM`. |
| `recipe` | Required, an existing recipe key. |
| `portion` | Optional number, 0.5–2, default 1. Multiplies every ingredient before `grams` is applied. |
| `grams` | Optional object `{ "<food key>": grams }`. Each key must be in the Recipe; `0` drops the Food from this meal. |

The rest of a plan file (`plan`, `sizes`, `how_to`, `adjust`, `days[]` with `week`, `title`, `type`, `kcal_target`, `tip`) is unchanged.

## Out of scope

- A recipe generator, a quiz, or per-order PDFs. That is the parked plugin route; Recipes are its groundwork.
- The buyer's name on the PDF.
- Photos (the owner dropped image work). Recipes keep the `photo` property for later.
- Selling Training Plans in Sizes. The picker is generic, but nothing here turns a Training Plan Product variable.
- Different prices per Size (Decision 10).
