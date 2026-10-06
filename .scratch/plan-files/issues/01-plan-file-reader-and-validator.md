# Plan file reader, validator and test fixtures

Type: task
Status: claimed
Blocked by: none

## What to build

Read the spec's "For the agent building this" section first.

**`src/PlanFile/PlanFile.php`**: reads a Plan file from a string (callers read paths, uploads and stdin) and checks it against the spec's format table. Like `LibraryFile`, a file with any problem exposes no data, only `problems` (a list of strings). A valid file exposes a normalised structure: defaults filled in, strings sanitised, numbers as `int`. Use small `final readonly` classes for it (or reuse nothing from `src/Plan/`: those carry `Exercise` objects and uids, which a file does not have).

- In this order, stopping at the first that fails: size over 2 MB; UTF-16 (a `FF FE` / `FE FF` start, or NUL bytes); strip a UTF-8 BOM; `json_decode` with `JSON_THROW_ON_ERROR` (report the JSON error message); top level not an object.
- Then collect **every** problem. Each names its place in author terms: `Week 3, Workout 2 ("Upper body"), Prescription 4: unknown Exercise "barbel-bench-press".` Use 1-based numbers.
- Strict properties: an unknown property at any level is a problem (`Week 1, Workout 1, Prescription 2: unknown property "rest".`).
- Types are strict: `"sets": "3"` and `"rest_seconds": 90.5` are problems.
- Phase rules reuse the exact msgids of `PlanFields::validate()` ("A Phase must end on or after the Week it starts.", "This Plan only has %d Weeks.", "Phases overlap at Week %d.") so they need no new translations, prefixed with "Phase N: ".
- Exercise resolution needs the database, so it is a separate step: `resolveExercises(LibraryKeys $keys)` turns every key into a post ID with `LibraryKeys::find()` (which sees every status) and adds a problem for a key that is not found or whose post is not `publish` (say which: "is in the trash", "is not published"). Report each missing key once, with every place it is used. Format checks must run without this step, on a string alone.

**`src/Content/PlanFields.php`**: public constants for the limits (`SETS_MAX = 20`, `TARGET_MAX_LENGTH = 20`, `INTENSITY_MAX_LENGTH = 20`) and for every field key the importer and exporter need (the `field_ol_plan_*`, `field_ol_phase_*`, `field_ol_week_workouts`, `field_ol_workout_*`, `field_ol_prescription_*` keys `SeedCommand` uses). Use them in the field definitions. Move the input estimate out of `warnWhenNearInputLimit()` into `public function estimateInputs(int $phases, array $prescriptionsPerWorkoutPerWeek): int` (or similar) that both the notice and the importer call. No change to what is registered or saved.

**Fixtures in `tests/plan-files/`** (new folder at the repo root, not inside the plugin, so they never ship):
- `minimal.json`: 1 Week, 1 Workout, 1 Prescription, only required properties, using a real library key.
- `full.json`: 3 Weeks, 2 Workouts each, 2 Phases, every property set, a multi-line note, a `seconds` target, a `null` rest, non-ASCII text (`ë`, `ç`). Write it in the exporter's exact layout (every property, the table's order, 4-space pretty print, LF, trailing newline), because ticket 03 compares an export of it with this file byte for byte.
- `broken.json`: every problem in the acceptance list below, plus `broken.expected.txt` with the exact problem lines expected, one per line, in the order reported.
- `README.md`: one paragraph on what each file is for.

Add `./tests/plan-files:/plan-fixtures:ro` and `./content/plans:/plans` to the `wpcli` service in `docker-compose.yml`, and create `content/plans/README.md` (what the folder holds: one Plan file per Product Plan, written by Claude or exported from wp-admin; see `docs/plan-files.md`), so the mount source exists.

There is no command yet to run the validator; ticket 03's `import-plan --dry-run` is how it runs. Until then, check it with a throwaway `wp eval-file` script on the owner's PC that prints `PlanFile`'s problems for each fixture. Do not commit that script.

## Acceptance criteria

- [ ] `minimal.json` and `full.json` pass; the normalised `minimal.json` has every default from the spec's table.
- [ ] `broken.json` produces exactly `broken.expected.txt`. It covers: wrong `format`; `version` 2; empty title; unknown difficulty; a Phase ending after the last Week; overlapping Phases; a Week with no Workouts; a Workout with no name; `sets: 0`; `sets: "3"`; a 21-character target; `target_type: "minutes"`; `rest_seconds: -5`; an unknown property; a malformed key (`"Bench Press"`); a well-formed key that does not exist (twice, in two Workouts, reported once with both places); a key of a trashed Exercise (document in the fixture README which library Exercise to trash for the test, and restore it after).
- [ ] A 3 MB file, a file that is not JSON, and a UTF-16 file give one problem each; `minimal.json` saved with a UTF-8 BOM passes.
- [ ] `PlanFields` registers identical field groups: `wp eval 'echo md5(serialize(acf_get_local_fields("group_ol_training_plan")));'` gives the same hash before and after.
- [ ] The Plan edit screen's max_input_vars warning still appears for `wp ol-plans seed --weeks=40` with the local limit lowered (or compare the estimate for an existing Plan before and after the move).
- [ ] `npm run lint:php` and `npm run analyse:php` pass.

## Comments
