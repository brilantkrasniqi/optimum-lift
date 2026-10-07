# Recipes in the renderer

Type: task
Status: resolved
Blocked by: 01

## What to build

The spec's "Recipe file format" and "Plan meal format" sections are the contract. Read Decisions 2 to 8 first.

**`content/diets/lib/recipes.js`:** loads every `recipes/*.json` and validates it. It exports what `render.js` needs now and what `catalogue.js` (ticket 04) will need.
- The key is the file name without `.json`. It must match `^[a-z0-9]+(-[a-z0-9]+)*$`.
- Check every rule in the Recipe table. Unknown properties are errors at every level, inside ingredients too.
- No Food twice in one Recipe, except `spice` lines.
- `name` is unique across Recipes.
- Every `food` exists in `foods.json`.
- Collect **every** problem before stopping, and name the place in author terms: `Recipe "omelete-me-spinaq-e-djathe", ingredient 3: unknown food "spinaq"`.

**Plans name Recipes.** In `render.js`, before any scaling, resolve each plan meal into the meal object the rest of the renderer already uses (`slot`, `time`, `minutes`, `name`, `photo`, `ingredients`, `steps`, `swap`):
1. Take the Recipe's ingredients and multiply each `grams` by `portion` (no rounding here).
2. Apply `grams` overrides as absolute grams at the reference size. `0` removes that ingredient.
3. Scale the `swap` text's grams by `portion` here. The size factor is applied later, as today.
4. Hand the result to the existing `scaleMeal()`, unchanged.

**Plan errors** are named by day and meal: `Day 3 ("E mërkurë"), lunch: unknown recipe "pule-skare"`. They cover:
- an unknown recipe key;
- a `grams` key that is not in the Recipe;
- `portion` outside 0.5–2;
- an unknown property on a meal;
- an inline meal (a meal with `ingredients` and no `recipe`): "write this meal as a Recipe and name it".

**Warnings:**
- A plan uses a Recipe in a slot that is not in its `slots`.
- The existing missing-photo warning, now for the Recipe's photo.

**`--recipes <dir>`** points the renderer at another recipes folder (default `recipes/`), for the fixtures below.

**Fixtures in `content/diets/tests/fixtures/`:**
- `recipes/`: three small Recipes, one with a spice line and one with a unit Food.
- `plan-ok.json`: two days that use `portion`, a `grams` override and a `0` drop.
- `plan-broken.json` plus `recipes-broken/` holding every problem listed above, with `broken.expected.txt` giving the exact `ERROR` lines in the order printed.
- `README.md`: one paragraph per file.

The two real plans fail after this ticket (their meals are inline). Ticket 03 migrates them next. Say so in the commit message.

## Acceptance criteria

- [x] `node render.js tests/fixtures/plan-ok.json --recipes tests/fixtures/recipes --dump` gives the grams you work out by hand for the `portion`, the override and the dropped Food. Write the hand calculation in the ticket comment.
- [x] `plan-broken.json` prints exactly `broken.expected.txt` and exits non-zero. Its problems include: an unknown recipe; an override key not in the Recipe; `portion: 3`; an unknown meal property; an inline meal; and, in `recipes-broken/`, a bad key, an unknown Recipe property, an unknown Food, a Food twice, a duplicate name and an unknown tag.
- [x] A Recipe used in the wrong slot gives a `WARNING`, not an error.
- [x] Swap grams scale with `portion` and the Size: a Recipe swap of "40 g" at `portion: 1.5` reads "60 g" at the reference factor 1 (check with a fixture Size or a unit test).

## Comments

### 2026-10-07 (Claude, cloud session)

**Changed:** new `content/diets/lib/recipes.js`, `content/diets/tests/fixtures/` (with `README.md`), `content/diets/tests/run.js` (`npm test`); `render.js` (`--recipes`, resolves meals before scaling, photo warning names the Recipe).

- `loadRecipes()` checks every Recipe in the folder, used or not. A Recipe with errors stays known by key, so a plan naming it is not also told "unknown recipe".
- Spice lines (Foods with kcal 0) need a `label`; two spice lines with the same label count as listed twice.
- A `grams` override on a Food that has no grams in the Recipe (a spice) is an error too.
- Swap grams are scaled by `portion` and rounded to whole grams, then by the Size as before.
- With any `ERROR` from Recipes or the plan, the render prints warnings and errors and stops before building Sizes.

**Verified:**
- `node render.js tests/fixtures/plan-ok.json --recipes tests/fixtures/recipes --dump` (fixture Size of exactly 80 kg, factor 1). By hand, from `foods.json`:
  - Day 1 breakfast, `tost-me-veze` at `portion` 1.5: egg 100 × 1.5 = 150 g = 3 pieces (143 × 1.5 = 214.5 kcal); bread 60 × 1.5 = 90 g = 3 slices (247 × 0.9 = 222.3). Meal 436.8 kcal. Dump: 150 g/3, 90 g/3, 436.8 ✓. Swap "40 g" reads "60 g" ✓.
  - Day 1 lunch, `pule-me-oriz` with `{"rice": 85}`: chicken 150 g (180), rice 85 g (360 × 0.85 = 306), oil 10 g (88.4), salad 100 g (18). 592.4 kcal ✓.
  - Day 2 lunch with `{"olive_oil": 0}`: the oil line is gone; 180 + 252 + 18 = 450 kcal ✓.
  - Day 2 snack, `kos-me-fruta` at 0.5: yogurt 100 g (73), berries 50 g (25), honey 7 g = 1 tsp (21.3). 119.3 kcal ✓.
- `plan-broken.json` with `recipes-broken/` prints exactly `broken.expected.txt` (12 `ERROR` lines) and exits 1.
- `tost-me-veze` at dinner prints `WARNING Day 2 ("Dita 2"), dinner: Recipe "tost-me-veze" is written for breakfast, not dinner` and exits 0.
- `npm test` runs all of the above: 5 ok.
- The two real plans now fail (inline meals). Ticket 03 migrates them.
