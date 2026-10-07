# ADR-0012: Diets are sold in Sizes as WooCommerce variable Products, chosen with the theme's own picker

**Status:** Accepted (2026-10-07). Amends ADR-0006 and ADR-0007.

Spec and tickets: `.scratch/diet-plans/`. The diet renderer and its guide: `content/diets/`.

## Context

Nutrition Plans are written as data and rendered into PDFs (`content/diets/`).
One plan renders in up to 10 **Sizes**: Mashkull and Femër, each at 50–60,
60–70, 70–80, 80–90 and 90+ kg. Portions scale to the Size, so each PDF has
different grams. The Customer must receive the Size they are, and only that
one.

ADR-0006 made every Product Simple. Nothing on a page could look like a choice
that changes the purchase; the design's selectors became a non-interactive
"included versions" list. A Size *is* such a choice, because it changes the
file the Customer receives.

Every buy control in the theme sent only the Product's ID: Buy Now, add to
cart, the drawer, the sticky buy bar and the cards. For a variable Product,
WooCommerce then refuses the add ("Please choose product options") or the
theme adds nothing.

Options considered:

1. **A Simple Product with all 10 PDFs.** Nothing to build, but the Customer
   gets nine files that are not theirs and must find their own. It also hands
   one buyer the whole set to share.
2. **WooCommerce's variations form** (`add-to-cart-variation.js`). It works,
   but it needs jQuery and WooCommerce's front-end scripts, which the theme
   does not load (ADR-0002, ADR-0007). Its dropdowns and price swap fight
   the price box design. It only exists on the Product page, so every other
   buy surface would still be broken.
3. **The theme's own picker** on a variable Product: radio pills in the price
   box, a plain GET form that works without JavaScript, with every buy path
   taught to carry a variation.
4. **A separate Simple Product per Size.** Ten Products per diet in the shop,
   ten pages to keep in sync, and sales proof and reviews split ten ways.

## Decision

Option 3.

- **A sized diet is a WooCommerce variable Product.**
  - It uses the global attributes **Gjinia** (`pa_gjinia`: `mashkull`, `femer`)
    and **Pesha** (`pa_pesha`: `50-60`, `60-70`, `70-80`, `80-90`, `90plus`),
    "Used for variations".
  - The term slugs are the renderer's file codes, so a PDF's name names its
    variation.
  - A one-gender diet has only the variations it sells, or only Pesha.
  - Each variation is Virtual and Downloadable, with its Size's PDF.
- **The theme does not hard-code these attributes.** The helpers
  (`inc/shop/sizes.php`), the picker and the buy paths handle any variable
  Product.
- **Every Size of a Product costs the same.** The page shows one price, and
  nothing changes when a Size is picked. The price box, buy bar, saving, sale
  timer and tracking stay one value per Product.
- **The picker is the theme's markup**
  (`template-parts/product/size-picker.php`):
  - a `fieldset` of `required` radios per attribute, with nothing chosen
    unless the URL names a Size (`?attribute_pa_pesha=60-70`);
  - a GET form to the Product's own page, which the price box's and the buy
    bar's buttons submit through their `form` attribute;
  - `modules/size-picker.js` only greys out combinations that are not sold,
    fills in `variation_id` and shows the Size in the bar;
  - `modules/cart.js` sends the add through the drawer.
- **Every other buy surface sends the buyer to the picker:** the cards, the
  bundle banner, sections, the mobile menu and the drawer's suggestions say
  "Choose your size" and link to `#blej`. The exception is the drawer's bundle
  swap: when the cart already holds a Size of one of the bundle's diets, it
  adds the bundle in that Size.
- **One Size per Product in the cart.** A different Size replaces the one
  there ("Changed to …"). The same Size again changes nothing.
- **A bundle containing a sized diet is variable too**, with the same
  attributes. Each of its variations carries the PDF of every diet in it for
  that Size.
- **Everything but price and Size comes from the parent.** That covers kind,
  category, ACF fields, cross-sells, thumbnail, permalink, sales counts and
  the tracking ID; `variant` carries the Size. Variations are named like their
  parent (`woocommerce_product_variation_title_include_attributes` is off), so
  WooCommerce lists the Size once, as the line's attributes, on its own pages
  and emails.
- **PDFs are attached by file name** in the "Size PDFs" box
  (`inc/shop/size-files.php`). It stores them in `woocommerce_uploads/diets/`
  and keeps each download's ID across re-uploads.
- **Amends ADR-0006:** Products are Simple, except Products sold in Sizes. The
  "included versions" list stays non-interactive. **Amends ADR-0007:** Buy Now
  also takes `attribute_*` or `variation_id`, or a variation's own ID, and
  resolves the Size before it empties the cart.

## Consequences

- Prices must be equal across Sizes. If they differ, the page shows the
  lowest, and the Size PDFs box and a notice after save warn.
- **"Any …" variations are not supported.** Every variation needs one value
  per attribute. One with "Any" is never resolved, so it cannot be bought, and
  the box warns about it.
- **A bundle must sell every Size of its sized diets.** A men-only diet cannot
  go in a Gjinia × Pesha bundle. The box warns when a bundle Size has fewer
  files than its sized diets.
- A third attribute (a vegetarian version, say) needs no theme change: the
  picker renders one group per variation attribute.
- Training Plans could be sold in Sizes the same way. That would still need
  per-variation Plans in the Plans plugin's Access, which grants by parent
  Product today, and its own button texts.
- A buy link from anywhere but the Product page costs the buyer one more step
  (the picker). An ad can skip it by linking to a Size:
  `?attribute_pa_gjinia=femer&attribute_pa_pesha=60-70`.
