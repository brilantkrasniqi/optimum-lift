# Migrate the two plans to Recipes

Type: task
Status: ready-for-agent
Blocked by: 02

## What to build

Turn every inline meal in `plans/djegie-yndyre.json` and `plans/djegie-e-shpejte.json` into a Recipe, and rewrite both plans to name them. Spec Decision 6 says no number may change.

Write a throwaway migration script (do not commit it):
- **Keys:** the meal's name folded to ASCII (`ë→e`, `ç→c`), lowercased, with words joined by hyphens.
- **Same name, same ingredients:** one Recipe.
- **Same name, different grams** (five meals today): one Recipe. Take the grams from `djegie-e-shpejte` (the larger plan). The other plan gets `grams` overrides that reproduce its own numbers exactly.
- **Same name, one copy with a spice line:** the Recipe keeps the spice line. The plan without it gains it, the one allowed difference.
- **Same name, different Foods** (none expected): stop and list them in the comment rather than guessing.
- **Fields:** `slots` from every slot the meal was used in; `minutes`, `steps`, `swap` and `photo` from the meal.
- **`tags`:** set by judgment from Decision 7's list. List them in the comment for the owner to check.

Expect 58 Recipes: 56 meals in Djegie e shpejtë, plus 8 in the example, minus the 6 shared ones.

**Then prove nothing moved:**
1. `--dump` both plans and diff against `tests/baseline/`. The only allowed differences are gained spice lines, each listed in the comment.
2. Render both plans in full and check for no `ERROR` and no `WARNING` the baseline run did not have.
3. Look at the reference-size PNGs of two days per plan.
4. Delete `tests/baseline/` in this same commit.

## Acceptance criteria

- [ ] `recipes/` holds one file per distinct meal (58 expected; explain any other count). No plan contains an inline meal.
- [ ] The dumps of every Size of both plans match the baseline except for listed spice lines.
- [ ] Both plans render with no `ERROR` and no new `WARNING`.
- [ ] Every Recipe passes the validator, and every Recipe is used by at least one plan.
- [ ] `tests/baseline/` is gone.

## Comments
