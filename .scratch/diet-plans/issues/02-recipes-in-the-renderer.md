# Recipes in the renderer

Type: task
Status: ready-for-agent
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

- [ ] `node render.js tests/fixtures/plan-ok.json --recipes tests/fixtures/recipes --dump` gives the grams you work out by hand for the `portion`, the override and the dropped Food. Write the hand calculation in the ticket comment.
- [ ] `plan-broken.json` prints exactly `broken.expected.txt` and exits non-zero. Its problems include: an unknown recipe; an override key not in the Recipe; `portion: 3`; an unknown meal property; an inline meal; and, in `recipes-broken/`, a bad key, an unknown Recipe property, an unknown Food, a Food twice, a duplicate name and an unknown tag.
- [ ] A Recipe used in the wrong slot gives a `WARNING`, not an error.
- [ ] Swap grams scale with `portion` and the Size: a Recipe swap of "40 g" at `portion: 1.5` reads "60 g" at the reference factor 1 (check with a fixture Size or a unit test).

## Comments
