# Demo sized diets in the seed

Type: task
Status: claimed
Blocked by: none

## What to build

Read the spec's "For the agent building this" section and Decisions 9, 10 and 16 first. This ticket gives every later Part B ticket something real to test against. After it, the demo diets cannot be bought until tickets 06 and 07 land; that is expected on the branch.

**Global attributes**, created by `wp ol-shop seed` when missing (`wc_create_attribute()`), and left alone when present:
- **Gjinia** (slug `gjinia`): terms `mashkull` "Mashkull" and `femer` "Femër".
- **Pesha** (slug `pesha`): terms `50-60` "50–60 kg", `60-70` "60–70 kg", `70-80` "70–80 kg", `80-90` "80–90 kg" and `90plus` "90+ kg".

Both use custom ordering, with terms in the order listed, so every picker shows Mashkull first and 50–60 first.

**The two demo diets become variable Products:**
- `plani-ushqimor-12-javor` uses Gjinia × Pesha (10 variations).
- `dieta-mesdhetare` uses only Pesha (5 variations): a women-only diet with no Gjinia attribute, which exercises the one-attribute picker.

**The bundle `transformimi-total` becomes variable too.** It uses Gjinia × Pesha (10 variations), and each variation carries the files of both diets for that Size (spec Decision 16).

A bundle may only contain diets that come in every Size it sells, so give `dieta-mesdhetare`'s bundle files from the same Pesha for both Gjinia values. It is demo data. Say so in a code comment, because a real women-only diet does not belong in a Gjinia bundle.

**Every variation:**
- Virtual and Downloadable.
- The demo diet's current prices: regular, and sale with the seed's sale dates. Every Size has the same price (Decision 10).
- Exactly one placeholder PDF, or two for the bundle.
- Status `publish`.
- The parent keeps everything the Simple Product had (fields, categories, goal attribute, cross-sells, reviews, `total_sales`). Gjinia and Pesha are added next to `pa_objektivi`, which stays `set_variation(false)`.

**Placeholder PDFs:** the seed writes a minimal one-page PDF per Size, by hand with no library, to `uploads/woocommerce_uploads/ol-demo/<product-slug>-<values joined by ->kg.pdf`. Its only text is the Product's name and the Size ("Plani ushqimor 12-javor · Femër · 60–70 kg"), so a test purchase proves which file arrived. Use the same naming the renderer uses, so ticket 09 can also test against these files. The bundle's variations reuse the two diets' placeholder files for that Size.

**Re-seeding:** an install seeded before this change has these three as Simple Products. Seeding again must convert them in place (same post IDs, so the bundle's components and cross-sells survive) and must not duplicate variations on a second run. `--reset` must delete the variations along with the parents (check what `WC_Product::delete(true)` does for a variable Product in the container's WooCommerce source).

Update the seed's success message and the CLAUDE.md line about the demo storefront to mention the sized diets.

## Acceptance criteria

- [ ] On a fresh database, `docker compose --profile cli run --rm wpcli ol-shop seed` creates both attributes with ordered terms, and the three variable Products with 10, 5 and 10 published, virtual, downloadable variations at equal prices.
- [ ] Each variation's file opens in the browser (as admin) and names that Size. Check three at random per Product.
- [ ] Re-running the seed changes nothing: same variation IDs and file count (`wp post list --post_type=product_variation --format=count` before and after).
- [ ] Seeding a database seeded by the old seed converts the three Products in place, with the same IDs as before.
- [ ] `ol-shop seed --reset` leaves no orphan `product_variation` posts.
- [ ] Training Plan Products 60 and 61 are unchanged (compare `wp wc product get` output before and after, or the Product edit screens).
- [ ] `npm run lint:php` and `npm run analyse:php` pass.

## Comments
