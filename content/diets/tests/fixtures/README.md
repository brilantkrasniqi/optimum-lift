# Renderer fixtures

`node tests/run.js` (or `npm test`) from `content/diets/` renders these with `--dump` and compares the results. No Playwright needed.

**`recipes/`** holds three small Recipes for `plan-ok.json`: `tost-me-veze` (a unit Food, eggs and bread, plus a `spice` line and a swap with grams), `pule-me-oriz` (lunch or dinner, olive oil as a unit Food) and `kos-me-fruta` (a snack).

**`plan-ok.json`** is two days for a single fixture Size of exactly 80 kg (factor 1, no `kcal_target`, so nothing is nudged). Day 1 eats `tost-me-veze` at `portion` 1.5 and `pule-me-oriz` with `rice` set to 85 g. Day 2 eats `kos-me-fruta` at `portion` 0.5, `pule-me-oriz` with `olive_oil` set to 0 (dropped), and `tost-me-veze` at dinner, which is not one of its slots and must give a `WARNING`.

**`plan-ok.expected.json`** is that plan's dump, checked by hand once (see ticket 02's comment) and compared byte for byte since.

**`recipes-broken/`** holds one valid Recipe (`pule-me-oriz`) and one file per Recipe problem: a bad key (`Bad_Key`), an unknown property on the Recipe and on an ingredient, an unknown Food, a Food listed twice, a name already used by another Recipe, and an unknown tag.

**`plan-broken.json`** names `recipes-broken/` and has one problem per meal: an unknown recipe, a `grams` key the Recipe does not contain, `portion: 3`, an unknown meal property, and an inline meal.

**`broken.expected.txt`** is exactly what `plan-broken.json` prints, in order. The render exits non-zero.
