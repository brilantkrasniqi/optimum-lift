# Demo sized diets in the seed

Type: task
Status: resolved
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

- [x] On a fresh database, `docker compose --profile cli run --rm wpcli ol-shop seed` creates both attributes with ordered terms, and the three variable Products with 10, 5 and 10 published, virtual, downloadable variations at equal prices.
- [x] Each variation's file opens in the browser (as admin) and names that Size. Check three at random per Product.
- [x] Re-running the seed changes nothing: same variation IDs and file count (`wp post list --post_type=product_variation --format=count` before and after).
- [x] Seeding a database seeded by the old seed converts the three Products in place, with the same IDs as before.
- [x] `ol-shop seed --reset` leaves no orphan `product_variation` posts.
- [x] Training Plan Products 60 and 61 are unchanged (compare `wp wc product get` output before and after, or the Product edit screens).
- [x] `npm run lint:php` and `npm run analyse:php` pass.

## Comments

### 2026-10-08 (Claude, test run on the owner's PC)

The run used branch `claude/project-thread-rnm05a` at e067d80, with the database backed up first.
- **First seed:** "Seeded 5 Products (sized: plani-ushqimor-12-javor with 10 variations, dieta-mesdhetare with 5 variations, transformimi-total with 10 variations)", with no warnings.
- **Conversion in place:** Products 62, 63 and 64 kept their IDs, and the 25 variations are 799–823.
- **Attributes:** both have ordered terms (Mashkull, Femër; 50–60 … 90+ kg). Mesdhetare has only Pesha, and no Product has default attributes.
- **Variations:** all are published, virtual and downloadable. Prices are equal within each Product: 12.99, on sale at 6.99, for 12-javor; 9.99, on sale at 5.99, for Mesdhetare; and 14.99 for the bundle, which has no sale, as on `main`.
- **Files:** nine sampled variations each name their Size. The bundle's variations carry both diets' files, and "Femër · 60–70 kg" is encoded correctly.
- **Second seed:** a dump of all 25 variations shows no differences.
- **Programs 60 and 61:** only the usual seed churn changed (re-dated, sale dates and `total_sales` refreshed), as on `main`.
- **`--reset`:** no orphan variations, postmeta or lookup rows.
- **`debug.log`:** no PHP notices.

The bundle's download IDs equal the matching diet variations' IDs, because the demo ID is `md5(file)`. That is harmless: WooCommerce keys permissions by Product and download ID.

After the reset, the demo Products have new IDs (824–843), not 60–64.
