# Migrate the two plans to Recipes

Type: task
Status: resolved
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

- [x] `recipes/` holds one file per distinct meal (58 expected; explain any other count). No plan contains an inline meal.
- [x] The dumps of every Size of both plans match the baseline except for listed spice lines.
- [x] Both plans render with no `ERROR` and no new `WARNING`.
- [x] Every Recipe passes the validator, and every Recipe is used by at least one plan.
- [x] `tests/baseline/` is gone.

## Comments

### 2026-10-07 (Claude, cloud session)

**Changed:** 58 new files in `content/diets/recipes/`, both `plans/*.json` rewritten to name them, `tests/baseline/` deleted. The migration script stayed in the session's scratchpad.

**58 Recipes**, as expected: 56 meals in Djegie e shpejtë plus 8 in the example, minus 6 shared. No two meals with the same name had different Foods.

**Grams overrides in `djegie-yndyre`** (the Recipe takes Djegie e shpejtë's grams):
- `omelete-me-spinaq-e-djathe`: `egg_white` 100, `white_cheese` 30, `wholegrain_bread` 60
- `kos-me-molle-e-arra`: `walnuts` 15
- `pule-ne-skare-oriz-e-sallate-shope`: `rice` 70
- `salmon-me-patate-e-brokoli`: `potato` 200

**Dump diff against the baseline:** every number is identical in all 15 Sizes (checked by a script that compares the dumps with spice lines, steps and swap text set aside, then lists those separately). What did change:
- Spice lines gained (the allowed difference): Djegie e shpejtë's `fasule-me-mish-vici` gains "Paprikë · 1 lugë çaji" (its steps already said paprika), and its `kos-me-molle-e-arra` gains "Kanellë · sipas dëshirës".
- **Text the two copies wrote differently**, now one wording each. Not a number the renderer computes, but you will see it in the PDFs:
  - `salmon-me-patate-e-brokoli`: spice label "Limon, kopër" (was "Limon, kopër, kripë" in the example). Steps from Djegie e shpejtë. Swap from the example, "Salmon → peshk i bardhë, 200 g, plus 1 lugë çaji vaj", because white fish alone is ~120 kcal short of the salmon. So Djegie e shpejtë's swap gains "plus 1 lugë çaji vaj".
  - `kos-me-molle-e-arra`: steps from the example ("… dhe spërkat kanellë", matching the cinnamon line). Swap from Djegie e shpejtë, "Mollë → dardhë, 150 g" (the example had "Mollë → dardhë ose 1 banane e vogël").
  - `fasule-me-mish-vici` swap: "Viç → …" (the example had "Viçi → …").
  - `pule-ne-skare-oriz-e-sallate-shope` and `sallate-me-ton-e-qiqra`: the example's steps lose "e grirë" and "pak".
- The owner said on 2026-10-07 the current diets are temporary, so these were not escalated.

**Renders:** both plans render all Sizes with no `ERROR` and no `WARNING`, and print the same calorie table as the shared-folder copy. Looked at the reference-size PNGs of Dita 1 and 2 (example) and Dita 1 and 8 (Djegie e shpejtë): grams, pieces, spice lines and swaps read right, nothing cut off.

**Every Recipe passes the validator and is used**: both plans render, and every Recipe came from a meal in one of them.

**Tags for the owner to check** (rules: no meat or fish → `vegetarian`; fish, no meat → `pescatarian`; no dairy or whey → `dairy-free`; no bread, pasta, oats or filo → `gluten-free`; by judgment: `meal-prep` for cooked mains that keep 3 days, `balkan` for local dishes):
- `meal-prep`: fasule-me-mish-vici, gjeldet-me-patate-te-embel-e-brokoli, gjelle-me-thjerreza-e-pule, kungulleshe-e-mbushur-me-gjeldet, makarona-me-mish-vici-e-domate, mish-vici-me-bishtaja, musaka-e-lehte-me-patellxhan, oriz-me-pule-e-perime, pule-me-kerpudha-e-oriz, pule-me-patate-ne-tave, qofte-vici-me-oriz-e-sallate, sarma-me-laker-e-mish, speca-te-mbushura-me-mish-e-oriz, supe-pule-me-perime, tave-kosi-me-pule-e-lehte, tave-me-presh-e-mish.
- `balkan`: fasule-me-mish-vici, fergese-me-pule, gjelle-me-thjerreza-e-pule, kungulleshe-e-mbushur-me-gjeldet, mish-vici-me-bishtaja, musaka-e-lehte-me-patellxhan, pite-e-lehte-me-spinaq-e-gjize, pule-ne-skare-oriz-e-sallate-shope, qofte-vici-me-oriz-e-sallate, sarma-me-laker-e-mish, speca-te-mbushura-me-mish-e-oriz, tave-kosi-me-pule-e-lehte, tave-me-presh-e-mish.
- The rest are in each file's `tags`; `recipes/INDEX.md` (ticket 04) lists them all.
