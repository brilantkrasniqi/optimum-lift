# Diets

The Nutrition Plans the store sells, as data. One JSON file per diet in `plans/`, rendered to a branded A4 PDF by `render.js`. The PDF has a cover, a "Si ta përdorësh" page, one page per day and a shopping list per week. Spec and tickets: `.scratch/diet-plans/`.

Run these from this folder (`content/diets/`); from the repo root, prefix the paths with `content/diets/`.

```
node render.js plans/<plan>.json                  # every size: out/<plan>/<plan>-<gender>-<weight>kg.pdf
node render.js plans/<plan>.json --only m-80-90   # one size
node render.js plans/<plan>.json --png            # also one PNG per page of the reference size (m-80-90)
node render.js plans/<plan>.json --out <dir>      # write to <dir>/<plan>/ instead of out/<plan>/
node render.js plans/<plan>.json --dump           # each size's computed plan as JSON instead of a PDF
```

- `--out`: in a project thread, Claude renders with `--out /mnt/project-files/diet-plans/out` so the PDFs can be attached to a reply.
- `--dump`: writes `<plan>-<gender>-<weight>kg.json` per size, with every gram, count and total the PDF would print (rounded to 0.1). It needs no Playwright, and two runs give the same bytes, so diffing two dumps proves a change moved no number.

Needs Node, and Playwright for the PDFs. Claude's cloud sessions have a global Playwright, which `render.js` falls back to; elsewhere run `npm install` here once (`npm run render -- plans/<plan>.json` works too). The fonts are the theme's own (`themes/optimum-lift/assets/fonts/`), so the PDFs and the site share one copy.

## Files

| File | What it is |
| --- | --- |
| `foods.json` | The food table: Albanian name, group, kcal and macros per 100 g. The only place numbers come from. |
| `plans/*.json` | One diet each. Holds foods and grams, never calories. |
| `plans/djegie-yndyre.json` | The worked example (2 days). Copy it to start a new diet. |
| `sizes.json` | The genders and weight ranges every plan is built in, and how portions scale. |
| `style.css` | The look. Brand colours and fonts from the theme. |
| `photos/` | Meal photos, referenced from a meal's `photo` field. |
| `render.js` | The renderer. `package.json` pins its Playwright. |
| `tests/` | Fixtures and checks for the renderer. |
| `out/<plan>/` | Rendered PDFs, one per size. Not committed. |

## Instructions for Claude filling a diet

1. Copy `plans/djegie-yndyre.json` to `plans/<slug>.json`, where `<slug>` is the diet's name in lowercase with hyphens.
2. Fill `plan`: name, tagline, who it is for, water. Write the plan for an 80 kg man; the other sizes are generated. Write `how_to` (4–6 rules) and `adjust` (2–4 rules) for this diet specifically.
3. Write 7 days for Java 1 unless told otherwise. Use different meals across days; repeating a breakfast twice a week is fine. Training and rest days differ in carbs, not in protein.
4. Each meal: `slot` (breakfast, snack, lunch, dinner, preworkout), `time`, `minutes`, `name`, 3–6 ingredients, 2–3 short steps, one `swap` with grams that keep calories roughly equal.
5. Ingredients use `food` keys from `foods.json` and `grams` (raw weight). Pieces and spoons are printed automatically for foods with a `unit`; `note` adds a remark such as "i papjekur". Spices and herbs use `"food": "spice"` with a `label` and a `household` text.
6. A food that is missing from `foods.json`: add it with values per 100 g from USDA FoodData Central or CIQUAL, and say which in your summary. Never change an existing food's numbers.
7. Set `kcal_target` per day for the 80 kg man and adjust grams until the render reports every day of every size within 5%. Protein should be about 2 g per kg of the reference weight on cutting and recomposition plans, 1.6–2 g on others.
8. Run `node render.js plans/<slug>.json --png`, look at every page of the reference size, check the smallest size (`--only f-50-60 --png`) too, and fix any `WARNING` (a day off target, a page too full, a missing photo) and every `ERROR`.
9. Write in Albanian, short sentences, informal "ti". Use local foods and dishes first.
10. Hand back the PDF and a short list of anything Brilant should check: new foods added, unusual choices, wording you're unsure of.

## Photos

Leave `photo` empty and the page shows a placeholder strip. To use a photo, put a JPG or PNG in `photos/` (landscape, at least 1200 px wide) and set `"photo": "photos/<file>.jpg"`. The photo strip grows a little and darkens at the bottom so the meal label stays readable.

Best: your own phone photos of the dishes on a plain plate. Second best: free stock photos from Unsplash or Pexels, whose licences allow commercial use without credit; check that the photo actually matches the recipe.

## Sizes: one plan, ten PDFs

A plan is written once, for a man of 80 kg (`reference_weight` in `sizes.json`). The renderer builds it in every size: Mashkull and Femër, each in 50–60, 60–70, 70–80, 80–90 and 90+ kg.

- Each size multiplies every portion by (middle of the weight range / 80) ^ 0.75, and by 0.88 for women. Calorie needs grow more slowly than body weight, so a 95 kg man gets about 14% more than the reference, not 19%.
- Portions are rounded to what a cook can measure: whole eggs, half slices of bread, half spoons, 5 g steps. Foods with a `unit` in `foods.json` are printed in pieces ("1½ fetë · 45 g").
- Rounding moves a day off its target, so each day is nudged back; the report warns if any size still ends more than 5% off.
- Grams in a `swap` text scale too.
- The cover says who the copy is for ("Përgatitur për ty: Femër · 50–60 kg"), and so does every page header.
- A women-only diet sets `"sizes": { "genders": ["female"] }` in its plan file, and can set its own `weights` too.

## Selling the sizes in WooCommerce

1. Make the diet Product a **Variable product**.
2. Under Attributes add **Gjinia** (Mashkull | Femër) and **Pesha** (50–60 kg | 60–70 kg | 70–80 kg | 80–90 kg | 90+ kg), both "Used for variations". A women-only diet gets only Pesha.
3. Under Variations choose "Generate variations", then use the bulk actions to toggle **Virtual** and **Downloadable** and set the price on all of them at once. Open each variation and attach its matching PDF.
4. To update a plan, re-render and replace each variation's file in the same slot.
