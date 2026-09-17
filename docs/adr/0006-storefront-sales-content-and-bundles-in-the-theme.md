# ADR-0006: Storefront sales content is ACF Flexible Content registered by the theme; bundles are Simple Products validated against their components

**Status:** Accepted (2026-09-16)

## Context

The storefront design (`ol-design/`, see `.scratch/storefront-theme/spec.md`)
sells 3–8 digital Products through long-form sales pages. A Training Plan
buyer and a Nutrition Plan buyer have different objections, so the middle of
each Product page differs: a Phase breakdown and a sample Workout on one, a
full day of eating and a priced shopping list on the other. The homepage is a
curated funnel built from many of the same pieces (results, testimonials,
comparison, guarantee, FAQ, final call to action).

The design brief requires that adding Product #6 never means writing a
template, and that the bundle's "cheaper together" claim cannot silently stop
being true.

Options considered for the sales content:

1. **Two PHP templates** (program, diet). Rejected by the brief: Product #6
   with a third kind of objection means a third template.
2. **Gutenberg blocks in the Product description.** WooCommerce Products use
   the classic editor; enabling the block editor for Products is unsupported,
   and custom blocks need a React build that ADR-0002 avoided.
3. **ACF Flexible Content** (ACF Pro is already load-bearing, ADR-0003): one
   library of section layouts, each Product (and the front page) picks and
   orders the ones it needs, rendered by PHP template parts.

Where to register the field groups: in the Plans plugin, a new plugin, or the
theme. The layouts *are* the design (a `sample_day` layout exists because the
design has that section), so the data model and its templates change
together. The Plans plugin stays about Plans (ADR-0003). Plan content never
moves into these fields.

Options considered for the bundle ("Transformimi Total"):

1. **A Simple Product with a hand-set price and a hand-set "was" price.**
   Rejected by the brief: both numbers drift from the parts.
2. **WooCommerce Grouped Product.** A grouped product cannot be added to the
   cart as one line and has no bundle price, so the one-card, one-price,
   one-button design is impossible.
3. **WooCommerce Product Bundles** (paid extension). Correct, but premium,
   which ADR-0001's `plugins.txt` cannot hold yet, for a catalogue that will
   have one bundle.
4. **A Simple virtual Product in the `paketa` category with a relationship
   field listing its component Products.** Its selling price is a business
   decision; its comparison price is *computed* as the sum of the components'
   current prices, and a bundle that is not cheaper than its parts shows no
   saving and raises an admin warning.

## Decision

- The theme registers, with `acf_add_local_field_group()`, the Product sales
  fields and one Flexible Content field, `ol_blocks`, shown on Products and on
  the static front page. Each layout renders from
  `template-parts/blocks/<layout>.php`. Field and layout names are in the spec.
- Badges ("Më i shituri", "I ri", "Vlera më e mirë") are **computed** from
  `total_sales`, publish date and bundle savings. There is no manual badge
  field, so an editor's badge can never be overridden by the computed one.
- Products are **Simple, virtual** Products. The design's "Niveli yt" / "Ku
  stërvitesh" / "Objektivi yt" / "Preferencat" selectors become a
  non-interactive "included versions" list: every version ships in the one
  Product, and nothing on the page looks like a choice that changes the
  purchase.
- The bundle is option 4. `ol_bundle_components` lists the components; the
  anchor price and saving are computed at render time from the components'
  current prices, never stored.
- The goal ("Humbje yndyre", …) is the global product attribute
  `pa_objektivi`, so it can drive a shop-by-goal filter later without a data
  migration.

## Consequences

- Without ACF Pro the storefront still renders (hero, price, buttons, product
  description) but loses the section stack. Every `get_field()` call goes
  through a theme helper that returns `null` when ACF is missing.
- Switching themes keeps the data (post meta) but loses the editing UI and the
  rendering. Acceptable: the sections have no meaning without their design.
- A bundle's Access to Plans is still set on the Product's `plans` field by the
  Plans plugin. Keeping it equal to the union of its components' Plans is a
  manual step today (follow-up in the spec).
- Changing a component's price changes the bundle's displayed saving at once.
  If the bundle stops being cheaper, the saving disappears from the site and
  the Product edit screen shows a warning.
